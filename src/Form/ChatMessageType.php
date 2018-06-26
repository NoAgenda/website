<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ChatMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('episode', HiddenType::class, [
                'required' => true,
            ])
            ->add('contents', TextareaType::class, [
                'required' => true,
            ])
            ->add('postedAt', HiddenType::class, [
                'required' => true,
            ])
        ;
    }
}