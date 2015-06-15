<?php

namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => false,
                'attr' => array(
                    'placeholder' => 'New Category',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'category',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Add',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function getName()
    {
        return 'tag';
    }
}