<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AdditionalSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function __construct(EntityManager $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

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
            ->add(
                $builder->create('structural_auxilaries', FormType::class, array('required' => false))
                ->add(
                    $builder->create('buzzwords', FormType::class, array())
                    ->add('activate', CheckboxType::class, array(
                        'required' => false,
                        'label_attr' => array('class' => 'uk-form-label'),
                        'value' => 'yes',
                    ))
                    ->add('mandatory', CheckboxType::class, array(
                        'required' => false,
                        'label_attr' => array('class' => 'uk-form-label'),
                        'value' => 'yes',
                    ))
                )
                ->add(
                    $builder->create('categories', FormType::class, array())
                    ->add('activate', CheckboxType::class, array(
                            'required' => false,
                            'label_attr' => array('class' => 'uk-form-label'),
                            'value' => 'yes',
                    ))
                    ->add('mandatory', CheckboxType::class, array(
                            'required' => false,
                            'label_attr' => array('class' => 'uk-form-label'),
                            'value' => 'yes',
                    ))
                    ->add('edit', CheckboxType::class, array(
                            'required' => false,
                            'label_attr' => array('class' => 'uk-form-label'),
                            'value' => 'yes',
                    ))
                )
            )
            ->add(
                $builder->create('tasks', FormType::class, array('required' => false, 'compound' => true))
                ->add('status', TextType::class, array(
                    'required' => false,
                ))
                ->add('status_option', ButtonType::class, array(
                ))
            )
            ->add(
                $builder->create('template', FormType::class, array())
                ->add('status', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('template_availability', ChoiceType::class, array(
                    'required' => false,
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => array(
                        'All commsy users' => 0,
                        'All workspace users' => 1,
                        'Workspace mods only' => 2,
                    ),
                ))
                ->add('template_description', TextareaType::class, array(
                    'required' => false,
                    'attr' => array(
                        'style' => 'width: 90%',
                    ),
                ))
            )
            /*
            ->add('room_status', CheckboxType::class, array(
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            */
            ->add(
                $builder->create('terms', FormType::class, array('required' => false))
                ->add('status', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Yes' => 'yes',
                        'No' => 'no',
                    ),
                    'data' => 'no',
                ))
                ->add('language', ChoiceType::class, array(
                    'required' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => array(
                        'German' => 'german',
                        'English' => 'english',
                    ),
                ))
                ->add('agb_text_de', CKEditorType::class, array(
                    'required' => false,
                    'inline' => false,
                ))
                ->add('agb_text_en', CKEditorType::class, array(
                    'required' => false,
                    'inline' => false,
                ))
            )
            ->add('save', SubmitType::class, array(
                'position' => 'last',
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
            ->setRequired(['roomId'])
            ->setDefaults(array('translation_domain' => 'settings'))
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
        return 'additional_settings';
    }
}
