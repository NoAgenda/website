<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('', name: 'security_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $repository,
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly MailerInterface $mailer,
    ) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->authChecker->isGranted('ROLE_USER')) {
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
        if ($this->authChecker->isGranted('ROLE_USER')) {
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
            $user = (new User())
                ->setUsername($form->get('username')->getData())
                ->setPlainPassword($form->get('password')->getData())
                ->setEmail($form->get('email')->getData())
            ;

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $messages = ['success' => ['Your account has been created.']];

            // reset form values
            $form = $this->createRegistrationForm();
        }

        return $this->render('security/login.html.twig', [
            'lastUsername' => $lastUsername,
            'authenticationError' => $error,

            'registrationForm' => $form->createView(),
            'registrationMessages' => $messages,
        ]);
    }

    #[Route('/forgot_password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->authChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('account_index');
        }

        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $submittedToken = $request->request->get('_token');

        $messages = [];

        if ($this->isCsrfTokenValid('forgot-password', $submittedToken)) {
            $username = $request->request->get('_username');

            $user = $this->repository->findOneBy(['username' => $username]);

            if (!$user) {
                $messages = ['danger' => ['That username is not known.']];
            }
            else {
                $messages = ['success' => ['An email was sent to the registered email address.']];

                if ($user->getEmail()) {
                    $user->generateActivationToken();

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

    #[Route('/reset_password/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->authChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('account_index');
        }

        $submittedToken = $request->request->get('_token');

        $messages = [];

        if ($this->isCsrfTokenValid('reset-password', $submittedToken)) {
            $password = $request->request->get('_password');
            $passwordConfirmation = $request->request->get('_password_confirmation');

            $user = $this->repository->findOneBy(['activationToken' => $token]);

            if (!$user || !$user->activationTokenIsValid()) {
                $messages = ['danger' => ['Invalid token.']];
            }
            else {
                if ($password != $passwordConfirmation) {
                    $messages = ['danger' => ['The passwords did not match.']];
                }
                else {
                    $user->setPlainPassword($password);
                    $user->clearActivationToken();

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Password was succesfully reset.');

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

    private function createRegistrationForm(): FormInterface
    {
        return $this->createForm(UserRegistrationType::class, null, [
            'action' => $this->generateUrl('security_registration'),
        ]);
    }

    private function sendResetPasswordEmail(User $user): void
    {
        if (!$user->getEmail()) {
            // If the user doesn't have an email, act like the email was sent for security purposes
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address($_SERVER['MAILER_FROM'], $_SERVER['MAILER_FROM_AUTHOR']))
            ->to(new Address($user->getEmail(), $user->getUsername()))
            ->subject('Reset Password')
            ->htmlTemplate('email/reset_password.html.twig')
            ->context([
                'user' => $user,
                'remote_address' => $this->container->get('request_stack')->getCurrentRequest()->getClientIp(),
                'activation_url' => $this->generateUrl(
                    'security_reset_password',
                    ['token' => $user->getActivationToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        $this->mailer->send($message);
    }
}
