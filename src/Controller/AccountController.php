<?php

namespace App\Controller;

use App\Repository\UserAccountRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/account', name: 'account_')]
class AccountController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserAccountRepository $userAccountRepository,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTERED_USER');

        return $this->render('account/index.html.twig');
    }

    #[Route('/email', name: 'email', methods: ['GET', 'POST'])]
    public function email(Request $request, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTERED_USER');

        $account = $user->getAccount();

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'New email address (or leave empty to clear)',
                'required' => false,
                'attr' => [
                    'placeholder' => $account->getEmail(),
                ],
            ])
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $account->setEmail($email);

            $this->userAccountRepository->persist($account, true);

            $this->addFlash('success', 'Your email address has been updated.');

            return $this->redirectToRoute('account_email');
        }

        return $this->render('account/email.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param PasswordAuthenticatedUserInterface|null $user
     */
    #[Route('/password', name: 'password', methods: ['GET', 'POST'])]
    public function password(Request $request, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTERED_USER');

        $account = $user->getAccount();

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The given passwords didn\'t match.',
                'first_options'  => ['label' => 'New password'],
                'second_options' => ['label' => 'Confirm new password'],
            ])
            ->add('oldPassword', PasswordType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $isPasswordValid = $this->passwordHasher->isPasswordValid($account, $oldPassword);

            if (!$isPasswordValid) {
                $form->get('oldPassword')->addError(new FormError('Incorrect password.'));
            }

            if ($form->isValid()) {
                $password = $form->get('password')->getData();
                $account->setPlainPassword($password);

                $this->userAccountRepository->persist($account, true);

                $this->addFlash('success', 'Your password has been updated.');

                return $this->redirectToRoute('account_password');
            }
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/status', name: 'status', methods: ['GET', 'POST'])]
    public function status(Request $request, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isCsrfTokenValid('update-status', $request->request->get('_csrf_token'))) {
            $action = $request->request->get('action');

            $user->setHidden('hide' === $action);

            $this->userRepository->persist($user, true);
        }

        return $this->render('account/status.html.twig');
    }
}
