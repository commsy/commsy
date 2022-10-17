<?php

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthWorkspaceMembershipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auth_membership_enabled', CheckboxType::class, [
                'label' => 'portal.auth.membership_enabled',
                'required' => false,
            ])
            ->add('auth_membership_identifier', TextType::class, [
                'label' => 'portal.auth.membership_identifier',
                'help' => 'portal.auth.membership_identifier_help',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
