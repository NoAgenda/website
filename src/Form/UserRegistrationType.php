<?php

namespace App\Form;

use App\Entity\UserAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Length(['min' => 3, 'max' => 24]),
                    // new ContainsAlphanumeric,
                ],
                'label' => 'Enter a username',
                'attr' => [
                    'placeholder' => 'Username',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'The passwords did not match.',
                'first_options'  => ['label' => 'Enter your password', 'attr' => ['placeholder' => 'Password']],
                'second_options' => ['label' => 'Confirm your password', 'attr' => ['placeholder' => 'Password']],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'Enter your email address (optional)',
                'help' => 'Add your email address to retrieve your account if you lose your password.',
                'attr' => [
                    'placeholder' => 'Email Address',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create Account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAccount::class,
        ]);
    }
}
