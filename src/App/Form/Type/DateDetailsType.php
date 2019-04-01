<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


use App\Form\Type\Custom\DateTimeSelectType;

use App\Form\Type\Event\AddRecurringFieldListener;

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
            ))
            ->add('end', DateTimeSelectType::class, array(
                'constraints' => array(
                ),
                'label' => 'enddate',
                'attr' => array(
                    'placeholder' => 'enddate',
                    'class' => 'uk-form-width-medium',
                ),
            ))
            ->add('whole_day', CheckboxType::class, array(
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
                'value' => 'yes',
            ))
            ->add('place', TextType::class, array(
                'label' => 'place',
                'attr' => array(
                    'placeholder' => 'place',
                    'class' => 'uk-form-width-medium',
                ),
                'required' => false,
            ))
            ->add('calendar', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['calendars'],
                'choice_attr' => $options['calendarsAttr'],
                'label' => 'calendar',
                'required' => true,
                'expanded' => true,
                'multiple' => false
            ))
        ;
            
        if (!isset($options['attr']['unsetRecurrence'])) {
            $builder
                ->add('recurring_select', ChoiceType::class, array(
                    'choices'  => array(
                        'RecurringDailyType' => 'RecurringDailyType',
                        'RecurringWeeklyType' => 'RecurringWeeklyType',
                        'RecurringMonthlyType' => 'RecurringMonthlyType',
                        'RecurringYearlyType' => 'RecurringYearlyType',
                    ),
                    'label' => 'recurring date',
                    'choice_translation_domain' => true,
                    'required' => false,
                ))
                ->addEventSubscriber(new AddRecurringFieldListener())
            ;
            $builder
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
        } else {
            $builder
                ->add('saveThisDate', SubmitType::class, array(
                    'attr' => array(
                        'class' => 'uk-button-primary',
                    ),
                    'label' => 'saveThisDate',
                ))
                ->add('saveAllDates', SubmitType::class, array(
                    'attr' => array(
                        'class' => 'uk-button-primary',
                    ),
                    'label' => 'saveAllDates',
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
            ->setDefaults(['translation_domain' => 'date'])
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