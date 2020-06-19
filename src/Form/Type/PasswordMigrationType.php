<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordMigrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'login.migration_change_password_current',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'login.migration_change_password_new',
            ])
            ->add('passwordConfirm', PasswordType::class, [
                'label' => 'login.migration_change_password_confirm',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'login.migration_change_password_submit',
                'attr' => [
                    'class' => 'uk-button-primary uk-width-medium',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'login',
        ]);
    }
}
