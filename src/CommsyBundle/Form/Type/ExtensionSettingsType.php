<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ExtensionSettingsType extends AbstractType
{
    private $legacyEnvironment;
    private $mediaWikiEnabled;

    public function __construct(LegacyEnvironment $legacyEnvironment, $mediaWikiEnabled)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->mediaWikiEnabled = $mediaWikiEnabled;
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
            ->add('assessment', CheckboxType::class, array(
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            ->add(
                $builder->create('workflow', FormType::class, array())
                ->add('resubmission', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('validity', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add(
                    $builder->create('traffic_light', FormType::class, array())
                    ->add('status_view', CheckboxType::class, array(
                        'required' => false,
                        'label_attr' => array('class' => 'uk-form-label'),
                    ))
                    ->add('default_status', ChoiceType::class, array(
                        'label_attr' => array('class' => 'uk-form-label'),
                        'expanded' => true,
                        'multiple' => false,
                        'choices' => array(
                            'GreenSymbol' => '0_green',
                            'YellowSymbol'=> '1_yellow',
                            'RedSymbol'   => '2_red',
                            'NoDefault' => '3_none',
                        ),
                    ))
                    ->add('green_text', TextType::class, array(
                        'required' => true,
                    ))
                    ->add('yellow_text', TextType::class, array(
                        'required' => true,
                    ))
                    ->add('red_text', TextType::class, array(
                        'required' => true,
                    ))
                )

                ->add('reader', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('reader_group', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('reader_person', CheckboxType::class, array(
                    'required' => false,
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('resubmission_show_to', ChoiceType::class, array(
                    'label' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Moderators' => 'moderator',
                        'Users' => 'all',
                    ),
                ))
            )
            ->add('save', SubmitType::class, array(
                'label' => 'Save',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )                
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();

                if ($this->mediaWikiEnabled) {
                    $form->add('wikiEnabled', CheckboxType::class, array(
                        'required' => false,
                        'label_attr' => array('class' => 'uk-form-label'),
                    ));
                }
            });
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
        return 'extension_settings';
    }
}