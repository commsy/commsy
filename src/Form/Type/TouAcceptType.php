<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TouAcceptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accept', SubmitType::class, [
                'translation_domain' => 'tou',
                'attr' => [
                    'class' => 'uk-button-success',
                ]
            ])
            ->add('decline', SubmitType::class, [
                'translation_domain' => 'tou',
                'attr' => [
                    'class' => 'uk-button-danger',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
