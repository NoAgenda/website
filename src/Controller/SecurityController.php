<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAccount;
use App\Entity\UserToken;
use App\Form\UserEmailType;
use App\Form\UserPasswordType;
use App\Form\UserRegistrationType;
use App\Repository\UserAccountRepository;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('', name: 'security_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserAccountRepository $userAccountRepository,
        private readonly UserTokenRepository $userTokenRepository,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenAuthenticator $tokenAuthenticator,
    ) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('security_account');
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'lastUsername' => $lastUsername,
            'authenticationError' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): Response
    {
        throw new AccessDeniedHttpException();
    }

    #[Route('/register', name: 'registration', methods: ['GET', 'POST'])]
    public function registration(Request $request): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('security_account');
        }

        $form = $this->createForm(UserRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userAccount = (new UserAccount())
                ->setUser(new User())
                ->setUsername($form->get('username')->getData())
                ->setEmail($form->get('email')->getData())
                ->setPlainPassword($form->get('password')->getData());

            $this->entityManager->persist($userAccount);
            $this->entityManager->flush();

            $this->addFlash('authentication', 'Your account has been created.');

            return $this->redirectToRoute('security_login');
        }

        return $this->renderForm('security/registration.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/account', name: 'account', methods: ['GET', 'POST'])]
    public function account(Request $request, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTERED_USER');

        $account = $user->getAccount();

        $emailForm = $this->createForm(UserEmailType::class, $account);
        $emailForm->handleRequest($request);

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $this->userAccountRepository->persist($account, true);

            $this->addFlash('email_form', 'Your email address has been updated.');

            return $this->redirectToRoute('security_account');
        }

        $passwordForm = $this->createForm(UserPasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $oldPassword = $passwordForm->get('oldPassword')->getData();

            if (!$this->passwordHasher->isPasswordValid($account, $oldPassword)) {
                $passwordForm->get('oldPassword')->addError(new FormError('Incorrect password.'));
            }

            if ($passwordForm->isValid()) {
                $password = $passwordForm->get('password')->getData();
                $account->setPlainPassword($password);

                $this->userAccountRepository->persist($account, true);

                $this->addFlash('password_form', 'Your password has been updated.');

                return $this->redirectToRoute('security_account');
            }
        }

        return $this->renderForm('security/account.html.twig', [
            'email_form' => $emailForm,
            'password_form' => $passwordForm,
        ]);
    }

    #[Route('/account/status', name: 'status', methods: ['POST'])]
    public function status(Request $request, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isCsrfTokenValid('update-status', $request->request->get('_csrf_token'))) {
            $action = $request->request->get('action');

            $user->setHidden('hide' === $action);

            $this->userRepository->persist($user, true);
        }

        return $this->redirectToRoute('security_account');
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('security_account');
        }

        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('forgot-password', $submittedToken)) {
            $username = $request->request->get('_username');
            $user = $this->userAccountRepository->findByUserInput($username);

            if (!$user) {
                $this->addFlash('forgot_password', 'We couldn\'t find an account matching that username/email address.');
            } elseif (!$user->getEmail()) {
                $this->addFlash('forgot_password', 'There is no email address associated with this account.');
            } else {
                $user->generateResetPasswordToken();

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->sendResetPasswordEmail($user);

                $this->addFlash('forgot_password', 'An email was sent to the registered email address.');
            }

            return $this->redirectToRoute('security_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('security_account');
        }

        if ($this->isCsrfTokenValid('reset_password', $request->request->get('_csrf_token'))) {
            $password = $request->request->get('_password');
            $passwordConfirmation = $request->request->get('_password_confirmation');

            $user = $this->userAccountRepository->findOneBy(['resetPasswordToken' => $token]);

            if (!$user || !$user->isResetPasswordTokenValid()) {
                $this->addFlash('reset_password', 'Invalid token.');
            } elseif ($password != $passwordConfirmation) {
                $this->addFlash('reset_password', 'The passwords did not match.');
            } else {
                $user->setPlainPassword($password);
                $user->clearResetPasswordToken();

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('authentication', 'Your password was updated.');

                return $this->redirectToRoute('security_login');
            }

            return $this->redirectToRoute('security_reset_password');
        }

        return $this->render('security/reset_password.html.twig', [
            'reset_password_token' => $token,
        ]);
    }

    #[Route('/generate-token', name: 'generate_token', methods: ['POST'])]
    public function token(Request $request): Response
    {
        $response = new JsonResponse();

        if ($this->getUser()?->isRegistered()) {
            return $response;
        }

        $this->tokenAuthenticator->generateToken($request, $response);

        return $response;
    }

    private function sendResetPasswordEmail(UserAccount $account): void
    {
        if (!$account->getEmail()) {
            // If the user doesn't have an email, act like the email was sent for security purposes
            return;
        }

        $resetUrl = $this->generateUrl(
            'security_reset_password',
            ['token' => $account->getResetPasswordToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $message = (new TemplatedEmail())
            ->from(new Address($_SERVER['MAILER_FROM'], $_SERVER['MAILER_FROM_AUTHOR']))
            ->to(new Address($account->getEmail(), $account->getUsername()))
            ->subject('Reset Password')
            ->htmlTemplate('email/reset_password.html.twig')
            ->context([
                'user' => $account,
                'remote_address' => $this->container->get('request_stack')->getCurrentRequest()->getClientIp(),
                'reset_url' => $resetUrl,
            ]);

        $this->mailer->send($message);
    }
}
