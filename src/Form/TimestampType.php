<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimestampType extends AbstractType
{
    public function __construct(
        private readonly TimestampTransformer $timestampTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->timestampTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['autocomplete' => 'off', 'placeholder' => '0:00'],
            'empty_data' => '',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
