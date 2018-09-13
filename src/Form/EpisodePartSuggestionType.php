<?php

namespace App\Form;

use App\Entity\EpisodePart;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EpisodePartSuggestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('part', EntityType::class, [
                'class' => EpisodePart::class,
            ])
            ->add('position', ChoiceType::class, [
                'choices' => self::getPositionChoices(),
                'expanded' => true,
            ])
            ->add('name', TextType::class)
            ->add('startsAt', TextType::class)
        ;

        $builder->get('startsAt')
            ->addModelTransformer(new CallbackTransformer(
                function ($timestampAsInt) {
                    if (!$timestampAsInt) {
                        return null;
                    }

                    dump($timestampAsInt);die;
                },
                function ($timestampAsString) {
                    if (!$timestampAsString) {
                        return null;
                    }

                    if (!strpos($timestampAsString, ':')) {
                        return null;
                    }

                    $parts = explode(':', $timestampAsString);

                    if (count($parts) > 3) {
                        return null;
                    }

                    if (count($parts) === 3) {
                        list($hours, $minutes, $seconds) = $parts;
                    }
                    else if (count($parts) === 2) {
                        $hours = 0;
                        list($minutes, $seconds) = $parts;
                    }
                    else {
                        return null;
                    }

                    return ($hours * 3600) + ($minutes * 60) + $seconds;
                }
            ))
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
