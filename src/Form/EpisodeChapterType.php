<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class EpisodeChapterType extends AbstractType
{
    public function __construct(
        private TimestampTransformer $timestampTransformer
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Enter the chapter title:',
                'constraints' => [new Length(['max' => 128])],
            ])
            ->add('startsAt', TextType::class, [
                'required' => true,
                'label' => 'Select the starting time of the chapter:',
                'empty_data' => '',
            ])
        ;

        $builder->get('startsAt')->addModelTransformer($this->timestampTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => EpisodeChapter::class,
        ]);
    }
}
