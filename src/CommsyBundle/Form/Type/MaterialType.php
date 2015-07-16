<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class MaterialType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Material title',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'material',
            ))
            ->add('description', 'textarea', array(
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Description',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'material',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Save',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // $resolver
        //     ->setRequired(array('roomId', 'uploadUrl'))
        // ;
    }

    public function getName()
    {
        return 'material';
    }
}