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

class TodoDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('due_date', DateTimeSelectType::class, array(
                'constraints' => array(
                ),
                'label' => 'due date',
                'attr' => array(
                    'placeholder' => 'due date',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'todo',
                'required' => false,
            ))
            ->add('time_planned', TextType::class, array(
                'constraints' => array(
                ),
                'label' => 'time planned',
                'attr' => array(
                ),
                'translation_domain' => 'todo',
                'required' => false,
            ))
            ->add('time_type', ChoiceType::class, array(
                'choices' => array (
                    'minutes' => '1',
                    'hours' => '2',
                    'days' => '3',
                ),
                'constraints' => array(
                ),
                'label' => 'time type',
                'attr' => array(
                ),
                'translation_domain' => 'todo',
            ))
            ->add('status', ChoiceType::class, array(
                'choices' => $options['statusChoices'],
                'constraints' => array(
                ),
                'label' => 'status',
                'attr' => array(
                ),
                'translation_domain' => 'todo',
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
            ->setRequired([
                'statusChoices',
            ])
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