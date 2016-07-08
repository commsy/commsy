<?php
namespace CommsyBundle\Form\Type\Recurring;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;

class RecurringYearlyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('month', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'month',
                'attr' => array(
                    'placeholder' => 'title',
                    'class' => '',
                ),
                'translation_domain' => 'date',
            ))
            ->add('recurrenceMonth', ChoiceType::class, array(
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
                'label' => 'recurrenceMonth',
                'translation_domain' => 'date',
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ))
            ->add('untilDate', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'untilDate',
                'attr' => array(
                    'placeholder' => 'title',
                    'class' => '',
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
        return 'recurring_daily';
    }
}