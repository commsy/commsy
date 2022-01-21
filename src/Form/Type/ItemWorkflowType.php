<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ItemWorkflowType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('workflowTrafficLight', ChoiceType::class, array(
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
            ->add('workflowResubmission', CheckboxType::class, array(
                'label'    => 'workflowResubmission',
                'translation_domain' => 'item',
                'required' => false,
                'attr' => array(
                    'data-uk-toggle' => '{target:\'#workflowResubmission\'}'
                ),
                'label_attr' => array(
                    'class' => 'uk-form-label'
                )
            ))
            ->add('workflowResubmissionDate', DateTimeType::class, array(
                'label' => 'workflowResubmissionDate',
                'translation_domain' => 'item',
                'input'  => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
            ))
            ->add('workflowResubmissionWho', ChoiceType::class, array(
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
            ->add('workflowResubmissionWhoAdditional', TextType::class, array(
                'label' => 'workflowWhoAdditional',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowResubmissionTrafficLight', ChoiceType::class, array(
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
            ->add('workflowValidity', CheckboxType::class, array(
                'label'    => 'workflowValidity',
                'translation_domain' => 'item',
                'required' => false,
                'attr' => array(
                    'data-uk-toggle' => '{target:\'#workflowValidity\'}'
                ),
                'label_attr' => array(
                    'class' => 'uk-form-label'
                )
            ))
            ->add('workflowValidityDate', DateTimeType::class, array(
                'label' => 'workflowValidityDate',
                'translation_domain' => 'item',
                'input'  => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
            ))
            ->add('workflowValidityWho', ChoiceType::class, array(
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
            ->add('workflowValidityWhoAdditional', TextType::class, array(
                'label' => 'workflowWhoAdditional',
                'translation_domain' => 'item',
                'required' => false,
            ))
            ->add('workflowValidityTrafficLight', ChoiceType::class, array(
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
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
            ))
        ;
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'itemWorkflow';
    }
}