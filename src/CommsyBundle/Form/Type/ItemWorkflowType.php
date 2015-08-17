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

class ItemWorkflowType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('workflowTrafficLight', 'choice', array(
                'placeholder' => false,
                'choices' => array(
                    '3_none' => 'none',
                    '0_green' => 'valid',
                    '1_yellow' => 'draft',
                    '2_red' => 'invalid',
                ),
                'label' => 'workflowStatus',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('workflowResubmission', 'checkbox', array(
                'label'    => 'workflowResubmission',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowResubmissionDate', 'date', array(
                'label' => 'workflowResubmissionDate',
                'translation_domain' => 'item',
                'input'  => 'datetime',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('workflowResubmissionWho', 'choice', array(
                'placeholder' => false,
                'choices' => array(
                    'creator' => 'creator',
                    'modifier' => 'modifier',
                ),
                'label' => 'workflowResubmissionWho',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('workflowResubmissionWhoAdditional', 'text', array(
                'label' => 'workflowWhoAdditional',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowResubmissionTrafficLight', 'choice', array(
                'placeholder' => false,
                'choices' => array(
                    '3_none' => 'none',
                    '0_green' => 'valid',
                    '1_yellow' => 'draft',
                    '2_red' => 'invalid',
                ),
                'label' => 'workflowStatusResubmissionDate',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('workflowValidity', 'checkbox', array(
                'label'    => 'workflowValidity',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowValidityDate', 'date', array(
                'label' => 'workflowValidityDate',
                'translation_domain' => 'item',
                'input'  => 'datetime',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('workflowValidityWho', 'choice', array(
                'placeholder' => false,
                'choices' => array(
                    'creator' => 'creator',
                    'modifier' => 'modifier',
                ),
                'label' => 'workflowValidityWho',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('workflowValidityWhoAdditional', 'text', array(
                'label' => 'workflowWhoAdditional',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowValidityTrafficLight', 'choice', array(
                'placeholder' => false,
                'choices' => array(
                    '3_none' => 'none',
                    '0_green' => 'valid',
                    '1_yellow' => 'draft',
                    '2_red' => 'invalid',
                ),
                'label' => 'workflowStatusValidityDate',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => false
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
            ->setRequired(array())
        ;
    }

    public function getName()
    {
        return 'itemWorkflow';
    }
}