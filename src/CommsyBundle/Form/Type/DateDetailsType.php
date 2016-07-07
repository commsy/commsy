<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;

use CommsyBundle\Form\Type\Event\AddRecurringFieldListener;

class DateDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', DateTimeSelectType::class, array(
                'constraints' => array(
                ),
                'label' => 'startdate',
                'attr' => array(
                    'placeholder' => 'startdate',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'date'
            ))
            ->add('end', DateTimeSelectType::class, array(
                'constraints' => array(
                ),
                'label' => 'enddate',
                'attr' => array(
                    'placeholder' => 'enddate',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'date',
            ))
            ->add('place', TextType::class, array(
                'label' => 'place',
                'attr' => array(
                    'placeholder' => 'place',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'date',
                'required' => false,
            ))
            ->add('color', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => array(
                    'cs-date-color-no-color' => 'cs-date-color-no-color',
                    'cs-date-color-01' => 'cs-date-color-01',
                    'cs-date-color-02' => 'cs-date-color-02',
                    'cs-date-color-03' => 'cs-date-color-03',
                    'cs-date-color-04' => 'cs-date-color-04',
                    'cs-date-color-05' => 'cs-date-color-05',
                    'cs-date-color-06' => 'cs-date-color-06',
                    'cs-date-color-07' => 'cs-date-color-07',
                    'cs-date-color-08' => 'cs-date-color-08',
                    'cs-date-color-09' => 'cs-date-color-09',
                    'cs-date-color-10' => 'cs-date-color-10',
                ),
                'label' => 'color',
                'translation_domain' => 'date',
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('recurring_select', ChoiceType::class, array(
                'choices'  => array(
                    'RecurringNoneType' => 'RecurringNoneType',
                    'RecurringDailyType' => 'RecurringDailyType',
                    'RecurringWeeklyType' => 'RecurringWeeklyType',
                ),
                'label' => 'recurring date',
                'choice_translation_domain' => true,
                'required' => false,
                'translation_domain' => 'date'
            ))
            ->addEventSubscriber(new AddRecurringFieldListener())
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
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
        return 'date';
    }
}