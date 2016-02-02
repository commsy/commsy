<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Validator\Constraints\NotBlank;

class SendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', 'text', [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'Subject',
                'translation_domain' => 'form',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Subject',
                ],
            ])
            ->add('message', 'ckeditor', [
                'label' => false,
                'translation_domain' => 'form',
                'required' => true,
            ])
            ->add('additional_recipients', CollectionType::class, [
                'entry_type' => EmailType::class,
                'entry_options' => [
                    'required' => false,
                    'label' => false,
                ],
                'allow_add' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('save', 'submit', [
                'label' => 'Send',
                'translation_domain' => 'form',
            ])
            ->add('cancel', 'submit', [
                'label' => 'cancel',
                'translation_domain' => 'form'
            ])
        ;
    }

    public function getName()
    {
        return 'send';
    }
}