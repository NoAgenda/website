<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class TimestampType extends AbstractType
{
    public function __construct(
        private readonly TimestampTransformer $timestampTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->timestampTransformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['episode'] = $options['episode'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['autocomplete' => 'off', 'placeholder' => '0:00'],
            'empty_data' => '',
        ]);

        $resolver->setRequired(['episode']);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
