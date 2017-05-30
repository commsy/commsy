<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;

use CommsyBundle\Form\Type\Event\AddRecurringFieldListener;

class DateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'title',
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'material',
            ))
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
                'required' => false,
            ))
            ->add('place', TextType::class, array(
                'label' => 'place',
                'attr' => array(
                    'placeholder' => 'place',
                    'class' => 'uk-form-width-medium',
                ),
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
                'required' => false,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('permission', CheckboxType::class, array(
                'label' => 'permission',
                'label_attr' => array('class' => 'uk-form-label'),
                'required' => false,
                'translation_domain' => 'form',
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'hidden',
                'label_attr' => array('class' => 'uk-form-label'),
                'required' => false,
                'translation_domain' => 'form',
            ))
            ->add('hiddendate', DateTimeSelectType::class, array(
                'label' => 'hidden until',
                'label_attr' => array('class' => 'uk-form-label'),
                'translation_domain' => 'form',
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $date = $event->getData();
                $form = $event->getForm();

                if ($date['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }
            })
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
            ->setRequired(array('placeholderText'))
            ->setDefaults(array('translation_domain' => 'date'))
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