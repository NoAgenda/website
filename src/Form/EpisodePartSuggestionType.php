<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EpisodePartSuggestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('position', ChoiceType::class, [
                'choices' => self::getPositionChoices(),
                'expanded' => true,
            ])
            ->add('name', TextType::class)
            ->add('startsAt', TextType::class)
        ;
    }

    public static function getPositionChoices(): array
    {
        static $positions = [
            'before' => 'There was another chapter played before this',
            'after' => 'There was another chapter played after this',
        ];

        return array_flip($positions);
    }
}
