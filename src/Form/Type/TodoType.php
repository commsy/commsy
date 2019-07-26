<?php
namespace App\Form\Type;

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

use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Custom\DateTimeSelectEngType;
use App\Form\Type\Custom\MandatoryCategoryMappingType;
use App\Form\Type\Custom\MandatoryHashtagMappingType;

class TodoType extends AbstractType
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
            ->add('due_date_eng', DateTimeSelectEngType::class, array(
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
            ->add('permission', CheckboxType::class, array(
                'label' => 'permission',
                'required' => false,
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'hidden',
                'required' => false,
            ))
            ->add('hiddendate', DateTimeSelectEngType::class, array(
                'label' => 'hidden until',
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $todo = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($todo['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }

                if ($todo['draft']) {
                    if ($todo['hashtagsMandatory'] && $formOptions['hashtagMappingOptions']) {
                        $form->add('hashtag_mapping', MandatoryHashtagMappingType::class, $formOptions['hashtagMappingOptions']);
                    }
                    if ($todo['categoriesMandatory'] && $formOptions['categoryMappingOptions']) {
                        $form->add('category_mapping', MandatoryCategoryMappingType::class, $formOptions['categoryMappingOptions']);
                    }
                }

            })
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
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
            ->setRequired(['placeholderText', 'statusChoices', 'hashtagMappingOptions', 'categoryMappingOptions'])
            ->setDefaults(array('translation_domain' => 'form'))
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
        return 'todo';
    }
}