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

use CommsyBundle\Form\Type\Custom\DateTimeType;

class AnnouncementType extends AbstractType
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
                'label' => 'title',
                'attr' => array(
                    'placeholder' => 'title',
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'announcement',
            ))
            // add custom datetime picker
            ->add('validdate', new DateTimeType(), array(
                'label' => 'valid until',
                'translation_domain' => 'announcement'
            ))
            ->add('permission', 'checkbox', array(
                'label' => 'permission',
                'required' => false,
                'translation_domain' => 'form',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ))
            ->add('cancel', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array())
        ;
    }

    public function getName()
    {
        return 'announcement';
    }
}