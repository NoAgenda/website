<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/account", name="account_")
 */
class AccountController extends Controller
{
    private $passwordEncoder;
    private $entityManager;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager
    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("", name="index", methods="GET")
     */
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    /**
     * @param User $user
     *
     * @Route("/email", name="email", methods="GET|POST")
     */
    public function email(Request $request, ?UserInterface $user): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'New email address (or leave empty to clear)',
                'required' => false,
                'attr' => [
                    'placeholder' => $user->getEmail(),
                ],
            ])
            ->add('submit', SubmitType::class)
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user->setEmail($email);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your email address has been updated.');
        }

        return $this->render('account/email.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param User $user
     *
     * @Route("/password", name="password", methods="GET|POST")
     */
    public function password(Request $request, ?UserInterface $user): Response
    {
        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The given passwords didn\'t match.',
                'first_options'  => ['label' => 'New password'],
                'second_options' => ['label' => 'Confirm new password'],
            ])
            ->add('oldPassword', PasswordType::class)
            ->add('submit', SubmitType::class)
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $isPasswordValid = $this->passwordEncoder->isPasswordValid($user, $oldPassword);

            if (!$isPasswordValid) {
                $form->get('oldPassword')->addError(new FormError('Incorrect password.'));
            }

            if ($form->isValid()) {
                $password = $form->get('password')->getData();
                $user->setPlainPassword($password);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Your password has been updated.');
            }
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param User $user
     *
     * @Route("/status", name="status", methods="GET|POST")
     */
    public function status(Request $request, ?UserInterface $user): Response
    {
        $submittedToken = $request->request->get('token');

        if ($this->isCsrfTokenValid('update-status', $submittedToken)) {
            $action = $request->request->get('action');

            if ($action == 'hide' && !$user->isHidden()) {
                $user->hide();
            }

            if ($action == 'expose' && $user->isHidden()) {
                $user->expose();
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('account/status.html.twig');
    }
}
