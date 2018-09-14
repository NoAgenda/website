<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EpisodePartSuggestionType extends AbstractType
{
    private $episodePartTransformer;
    private $timestampTransformer;

    public function __construct(EpisodePartTransformer $episodePartTransformer, TimestampTransformer $timestampTransformer)
    {
        $this->episodePartTransformer = $episodePartTransformer;
        $this->timestampTransformer = $timestampTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('part', HiddenType::class, [
                'required' => true,
            ])
            ->add('position', ChoiceType::class, [
                'choices' => self::getPositionChoices(),
                'expanded' => true,
                'required' => true,
            ])
            ->add('name', TextType::class)
            ->add('startsAt', TextType::class)
        ;

        //$builder->get('part')->addModelTransformer($this->episodePartTransformer);
        $builder->get('startsAt')->addModelTransformer($this->timestampTransformer);
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
