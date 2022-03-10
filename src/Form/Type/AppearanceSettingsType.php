<?php
namespace App\Form\Type;

use Sylius\Bundle\ThemeBundle\Model\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AppearanceSettingsType extends AbstractType
{
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
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $options = $form->getConfig()->getOptions();

                if (!empty($options['themes'])) {
                    $themes = $options['themes'];

                    $form->add('theme', ChoiceType::class, [
                        'required' => true,
                        'choice_loader' => new CallbackChoiceLoader(function() use ($themes) {
                            function getShortNames($themes) {
                                foreach ($themes as $theme) {
                                    /** @var Theme $theme */
                                    yield substr($theme->getName(),7);
                                }
                            }

                            $choices = ['default'];
                            $choices = array_merge($choices, iterator_to_array(getShortNames($themes)));
                            return array_combine($choices, $choices);
                        }),
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'attr' => [
                            'data-themeurl' => $options['themeBackgroundPlaceholder'],
                        ],
                    ]);
                }
            })
            ->add('dates_status', ChoiceType::class, [
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'listview' => 'normal',
                    'weekview' => 'calendar',
                    'monthview' => 'calendar_month',
                ],
            ])
            ->add(
                $builder->create('room_image', FormType::class, ['required' => false])
                ->add('choice', ChoiceType::class, [
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => [
                        'Theme image' => 'default_image',
                        'Custom image' => 'custom_image',
                    ],
                ])
                ->add('room_image_upload', FileType::class, [
                    'attr' => [
                        'required' => false,
                        'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                    ],
                ])
                ->add('room_image_data', HiddenType::class)
            )
            ->add(
                $builder->create('room_logo', FormType::class, ['required' => false])
                ->add('activate', CheckboxType::class, [
                     'label_attr' => array('class' => 'uk-form-label'),
                ])
                ->add('room_logo_upload', FileType::class, [
                    'attr' => [
                        'required' => false,
                        'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                    ],
                ])
                ->add('room_logo_data', HiddenType::class)
            )
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ]
            ])
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
            ->setDefaults(['translation_domain' => 'settings'])
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
