<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
                'label' => 'Message',
                'translation_domain' => 'form',
                'required' => true,
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