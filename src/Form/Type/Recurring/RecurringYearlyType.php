<?php
namespace App\Form\Type\Recurring;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use App\Form\Type\Event\AddBibliographicFieldListener;

class RecurringYearlyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recurrenceDayOfMonth', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'recurrenceDayOfMonth',
                'attr' => array(
                    'style' => 'margin: 0px 3px;',
                    'size' => '2',
                ),
                'translation_domain' => 'date',
            ))
            ->add('recurrenceMonthOfYear', ChoiceType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'placeholder' => false,
                'choices' => array(
                    'january' => 'january',
                    'february' => 'february',
                    'march' => 'march',
                    'april' => 'april',
                    'may' => 'may',
                    'june' => 'june',
                    'july' => 'july',
                    'august' => 'august',
                    'september' => 'september',
                    'october' => 'october',
                    'november' => 'november',
                    'december' => 'december',
                ),
                'label' => 'recurrenceMonthOfYear',
                'translation_domain' => 'date',
                'required' => false,
                'expanded' => false,
                'multiple' => false
            ))
            ->add('untilDate', DateType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'untilDate',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'style' => 'margin: 0px 3px;',
                ),
                'translation_domain' => 'date',
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
        return 'recurring_yearly';
    }
}