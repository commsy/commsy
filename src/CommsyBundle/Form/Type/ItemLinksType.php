<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Entity\Materials;

class ItemLinksType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('categories', 'treechoice', array(
                'placeholder' => false,
                'choices' => $options['categories'],
                'label' => 'categories',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices_as_values' => true
            ))
            ->add('hashtags', 'choice', array(
                'placeholder' => false,
                'choices' => $options['hashtags'],
                'label' => 'hashtags',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ))
            ->add('newHashtag', 'text', array(
                'label' => 'newHashtag',
                'translation_domain' => 'item',
                'required' => false
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
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('categories', 'hashtags'))
        ;
    }

    public function getName()
    {
        return 'itemLinks';
    }
}