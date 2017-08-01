<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DateImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('calendarfile', FileType::class, array(
                'label' => 'upload',
                'attr' => array(
                    'data-uk-csupload' => '{"path": "' . $options['uploadUrl'] . '", "errorMessage": ""}',
                ),
                'required' => true,
                'translation_domain' => 'date',
                'multiple' => false,
            ))
            ->add('calendar', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['calendars'],
                'choice_attr' => $options['calendarsAttr'],
                'label' => 'calendar',
                'required' => true,
                'expanded' => false,
                'multiple' => false
            ))
            ->add('calendartitle', TextType::class, array(
                'label' => 'title',
                'attr' => array(
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'date',
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ));
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('uploadUrl', 'calendars', 'calendarsAttr'))
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
        return 'dateimport';
    }
}