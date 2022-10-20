<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class EpisodeChapterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Enter the chapter title (optional):',
                'constraints' => [new Length(['max' => 128])],
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('startsAt', TimestampType::class, [
                'required' => true,
                'label' => 'Select the starting time of the chapter:',
                'episode' => $options['episode'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => EpisodeChapter::class,
        ]);

        $resolver->setRequired(['episode']);
    }
}
