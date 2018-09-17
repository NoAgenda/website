<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class EpisodePartCorrectionType extends AbstractType
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
            ->add('part', HiddenType::class)
            ->add('action', ChoiceType::class, [
                'choices' => self::getActionChoices(),
                'expanded' => true,
                'required' => true,
                'empty_data' => null,
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'constraints' => [new Length(['min' => 6, 'max' => 48])],
            ])
            ->add('startsAt', TextType::class, [
                'required' => false,
            ])
        ;

        $builder->get('part')->addModelTransformer($this->episodePartTransformer);
        $builder->get('startsAt')->addModelTransformer($this->timestampTransformer);
    }

    public static function getActionChoices(): array
    {
        static $actions = [
            'remove' => 'This chapter wasn\'t played here',
            'startsAt' => 'The starting time of this chapter is incorrect',
            'name' => 'The name of this chapter is incorrect',
        ];

        return array_flip($actions);
    }
}
