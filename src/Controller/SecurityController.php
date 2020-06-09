<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Updates\ResetPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @Route("", name="security_")
 */
class SecurityController extends Controller
{
    private $authChecker;
    private $passwordEncoder;
    private $authenticationUtils;
    private $entityManager;
    private $repository;
    private $resetPasswordUpdater;

    public function __construct(
        AuthorizationCheckerInterface $authChecker,
        UserPasswordEncoderInterface $passwordEncoder,
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        UserRepository $repository,
        ResetPasswordUpdater $resetPasswordUpdater
    )
    {
        $this->authChecker = $authChecker;
        $this->passwordEncoder = $passwordEncoder;
        $this->authenticationUtils = $authenticationUtils;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->resetPasswordUpdater = $resetPasswordUpdater;
    }

    /**
     * @Route("/login", name="login", methods="GET|POST")
     */
    public function login(Request $request): Response
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

    /**
     * @Route("/logout", name="logout", methods="GET")
     */
    public function logout(): Response
    {
        throw new AccessDeniedHttpException();
    }

    /**
     * @Route("/register", name="registration", methods="GET|POST")
     */
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
            $user = (new User)
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

    /**
     * @Route("/forgot_password", name="forgot_password", methods="GET|POST")
     */
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

                    $this->resetPasswordUpdater->update($user);
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

    /**
     * @Route("/reset_password/{token}", name="reset_password", methods="GET|POST")
     */
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
        $formOptions = [
            'constraints' => new UniqueEntity([
                'fields' => 'username',
            ]),
            'data_class' => User::class,
        ];

        return $this->createFormBuilder(null, $formOptions)
            ->setAction($this->generateUrl('security_registration'))
            ->add('username', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Length(['min' => 3, 'max' => 24]),
                    // new ContainsAlphanumeric,
                ],
                'label' => 'Username',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'The passwords did not match.',
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm password'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'required' => false,
            ])
            ->getForm()
        ;
    }
}
