<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAccount;
use App\Entity\UserToken;
use App\Form\UserRegistrationType;
use App\Repository\UserAccountRepository;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('', name: 'security_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserAccountRepository $userAccountRepository,
        private readonly UserTokenRepository $userTokenRepository,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly MailerInterface $mailer,
    ) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('account_index');
        }

        // get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'lastUsername' => $lastUsername,
            'authenticationError' => $error,

            'registrationForm' => $this->createRegistrationForm()->createView(),
            'registrationMessages' => [],
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
            return $this->redirectToRoute('account_index');
        }

        // get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        // handle registration form
        $form = $this->createRegistrationForm();

        $form->handleRequest($request);

        $messages = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $userAccount = (new UserAccount())
                ->setUser(new User())
                ->setUsername($form->get('username')->getData())
                ->setEmail($form->get('email')->getData())
                ->setPlainPassword($form->get('password')->getData());

            $this->entityManager->persist($userAccount);
            $this->entityManager->flush();

            $messages = ['success' => ['Your account has been created.']];

            // reset form values
            $form = $this->createRegistrationForm();
        }

        return $this->render('security/registration.html.twig', [
            'lastUsername' => $lastUsername,
            'authenticationError' => $error,

            'registrationForm' => $form->createView(),
            'registrationMessages' => $messages,
        ]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('account_index');
        }

        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $submittedToken = $request->request->get('_token');

        $messages = [];

        if ($this->isCsrfTokenValid('forgot-password', $submittedToken)) {
            $username = $request->request->get('_username');

            $user = $this->userAccountRepository->findOneBy(['username' => $username]);

            if (!$user) {
                $messages = ['danger' => ['That username is not known.']];
            } else {
                $messages = ['success' => ['An email was sent to the registered email address.']];

                if ($user->getEmail()) {
                    $user->generateResetPasswordToken();

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->sendResetPasswordEmail($user);
                }
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'lastUsername' => $lastUsername,
            'authenticationError' => false,

            'forgotPasswordMessages' => $messages,

            'registrationForm' => $this->createRegistrationForm()->createView(),
            'registrationMessages' => [],
        ]);
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->getUser()?->isRegistered()) {
            return $this->redirectToRoute('account_index');
        }

        $messages = [];

        if ($this->isCsrfTokenValid('reset_password', $request->request->get('_csrf_token'))) {
            $password = $request->request->get('_password');
            $passwordConfirmation = $request->request->get('_password_confirmation');

            $user = $this->userAccountRepository->findOneBy(['resetPasswordToken' => $token]);

            if (!$user || !$user->isResetPasswordTokenValid()) {
                $messages = ['danger' => ['Invalid token.']];
            } else {
                if ($password != $passwordConfirmation) {
                    $messages = ['danger' => ['The passwords did not match.']];
                } else {
                    $user->setPlainPassword($password);
                    $user->clearResetPasswordToken();

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Your password was updated.');

                    return $this->redirectToRoute('security_login');
                }
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'lastUsername' => null,
            'authenticationError' => false,

            'resetPasswordMessages' => $messages,
            'resetPasswordToken' => $token,

            'registrationForm' => $this->createRegistrationForm()->createView(),
            'registrationMessages' => [],
        ]);
    }

    #[Route('/generate-token', name: 'generate_token', methods: ['POST'])]
    public function token(Request $request): Response
    {
        $response = new JsonResponse();

        if ($this->getUser()?->isRegistered()) {
            return $response;
        }

        $publicToken = $request->cookies->get('auth_token') ?? $request->cookies->get('guest_token');

        if (null !== $this->userTokenRepository->findOneBy(['publicToken' => $publicToken])) {
            return $response;
        }

        $token = (new UserToken())
            ->setUser(new User())
            ->addIpAddress($request->getClientIp());

        $this->userTokenRepository->persist($token, true);

        $response->headers->setCookie(new Cookie('auth_token', $token->getPublicToken(), strtotime('+33 months')));

        return $response;
    }

    private function createRegistrationForm(): FormInterface
    {
        return $this->createForm(UserRegistrationType::class, null, [
            'action' => $this->generateUrl('security_registration'),
        ]);
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
