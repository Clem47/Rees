<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'value',
                ChoiceType::class,
                [  'choices'  => [
                '🌑🌑🌑🌑🌑' => 0,
                '🌗🌑🌑🌑🌑' => 0.5,
                '🌕🌑🌑🌑🌑' => 1,
                '🌕🌗🌑🌑🌑' => 1.5,
                '🌕🌕🌑🌑🌑' => 2,
                '🌕🌕🌗🌑🌑' => 2.5,
                '🌕🌕🌕🌑🌑' => 3,
                '🌕🌕🌕🌗🌑' => 3.5,
                '🌕🌕🌕🌕🌑' => 4,
                '🌕🌕🌕🌕🌗' => 4.5,
                '🌕🌕🌕🌕🌕' => 5]
                ,'required' => true,
                'label' => 'Valeur',]
            )

            ->add(
                'comment',
                TextareaType::class,
                ['required'   => false,
                'label' => 'Commentaire',
                'attr' => ['style' => 'height : auto']]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => Rating::class,
            ]
        );
    }
}
