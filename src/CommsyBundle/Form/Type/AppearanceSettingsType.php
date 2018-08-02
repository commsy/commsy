<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AppearanceSettingsType extends AbstractType
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
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
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($options['roomId']);

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $options = $form->getConfig()->getOptions();

                if (!empty($form->getConfig()->getOptions()['themes'])) {
                    $form->add('theme', ChoiceType::class, array(
                        'required' => true,
                        'choices' => array_combine($options['themes'], $options['themes']),
                        'constraints' => array(
                            new NotBlank(),
                        ),
                        'attr' => array(
                            'data-themeurl' => $options['themeBackgroundPlaceholder'],
                        ),
                    ));
                }
            })
            ->add('dates_status', ChoiceType::class, array(
                'expanded' => true,
                'multiple' => false,
                'choices' => array(
                    'listview' => 'normal',
                    'weekview' => 'calendar',
                    'monthview' => 'calendar_month'
                ),
            ))
            ->add(
                $builder->create('room_image', FormType::class, array('required' => false))
                ->add('choice', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Theme image' => 'default_image',
                        'Custom image' => 'custom_image',
                    ),
                ))
                ->add('room_image_upload', FileType::class, array(
                    'attr' => array(
                        'required' => false,
                        'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                    ),
                    //'image_path' => 'webPath',
                ))
                ->add('room_image_data', HiddenType::class, array(
                ))
                // ->add('repeat_x', CheckboxType::class, array(
                //     'label_attr' => array('class' => 'uk-form-label'),
                //     'value' => 'repeat_x',
                // ))

                // ->add('scroll_image', CheckboxType::class, array(
                //     'label_attr' => array('class' => 'uk-form-label'),
                //     'value' => 'scroll_image',
                // ))

                // ->add('delete_custom_image', CheckboxType::class, array(
                //     'label_attr' => array('class' => 'uk-form-label'),
                //     'value' => 'delete_bg_image',
                // ))
            )
            ->add(
                $builder->create('room_logo', FormType::class, array('required' => false))
                ->add('activate', CheckboxType::class, [
                     'label_attr' => array('class' => 'uk-form-label'),
                ])
                ->add('room_logo_upload', FileType::class, array(
                    'attr' => array(
                        'required' => false,
                        'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                    ),
                ))
                ->add('room_logo_data', HiddenType::class, [])
            )
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                )                
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
            ->setRequired(['roomId', 'themes', 'uploadUrl', 'themeBackgroundPlaceholder'])
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
        return 'appearance_settings';
    }
}
