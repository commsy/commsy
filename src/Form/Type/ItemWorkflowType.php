<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemWorkflowType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('workflowTrafficLight', ChoiceType::class, ['placeholder' => false, 'choices' => ['3_none' => '3_none', '0_green' => '0_green', '1_yellow' => '1_yellow', '2_red' => '2_red'], 'label' => 'workflowStatus', 'translation_domain' => 'item', 'required' => false, 'expanded' => true, 'multiple' => false])
            ->add('workflowResubmission', CheckboxType::class, ['label' => 'workflowResubmission', 'translation_domain' => 'item', 'required' => false, 'attr' => ['data-uk-toggle' => '{target:\'#workflowResubmission\'}'], 'label_attr' => ['class' => 'uk-form-label']])
            ->add('workflowResubmissionDate', DateTimeType::class, ['label' => 'workflowResubmissionDate', 'translation_domain' => 'item', 'input' => 'datetime', 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'required' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}']])
            ->add('workflowResubmissionWho', ChoiceType::class, ['placeholder' => false, 'choices' => ['creator' => 'creator', 'modifier' => 'modifier'], 'label' => 'workflowResubmissionWho', 'translation_domain' => 'item', 'required' => false, 'expanded' => true, 'multiple' => false])
            ->add('workflowResubmissionWhoAdditional', TextType::class, ['label' => 'workflowWhoAdditional', 'translation_domain' => 'item', 'required' => false])
            ->add('workflowResubmissionTrafficLight', ChoiceType::class, ['placeholder' => false, 'choices' => ['3_none' => 'none', '0_green' => 'valid', '1_yellow' => 'draft', '2_red' => 'invalid'], 'label' => 'workflowStatusResubmissionDate', 'translation_domain' => 'item', 'required' => false, 'expanded' => true, 'multiple' => false])
            ->add('workflowValidity', CheckboxType::class, ['label' => 'workflowValidity', 'translation_domain' => 'item', 'required' => false, 'attr' => ['data-uk-toggle' => '{target:\'#workflowValidity\'}'], 'label_attr' => ['class' => 'uk-form-label']])
            ->add('workflowValidityDate', DateTimeType::class, ['label' => 'workflowValidityDate', 'translation_domain' => 'item', 'input' => 'datetime', 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'required' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}']])
            ->add('workflowValidityWho', ChoiceType::class, ['placeholder' => false, 'choices' => ['creator' => 'creator', 'modifier' => 'modifier'], 'label' => 'workflowValidityWho', 'translation_domain' => 'item', 'required' => false, 'expanded' => true, 'multiple' => false])
            ->add('workflowValidityWhoAdditional', TextType::class, ['label' => 'workflowWhoAdditional', 'translation_domain' => 'item', 'required' => false])
            ->add('workflowValidityTrafficLight', ChoiceType::class, ['placeholder' => false, 'choices' => ['3_none' => 'none', '0_green' => 'valid', '1_yellow' => 'draft', '2_red' => 'invalid'], 'label' => 'workflowStatusValidityDate', 'translation_domain' => 'item', 'required' => false, 'expanded' => true, 'multiple' => false])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'save', 'translation_domain' => 'form'])
            ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form'])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
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
    public function getBlockPrefix(): string
    {
        return 'itemWorkflow';
    }
}
