<?php

namespace App\Form\Type\Profile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileAdditionalType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('language', ChoiceType::class, [
                'placeholder' => false,
                'choices' => [
                    'browser' => 'browser',
                    'de' => 'de',
                    'en' => 'en'
                ],
                'label' => 'language',
                'required' => false,
                'empty_data' => 'browser',
            ]);
        if ($options['emailToCommsy']) {
            $builder
                ->add('emailToCommsy', CheckboxType::class, [
                    'label' => 'Activate',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'uk-form-label',
                    ],
                    'translation_domain' => 'settings',
                ])
                ->add('emailToCommsySecret', TextType::class, [
                    'label' => 'emailToCommsySecret',
                    'required' => false,
                ]);
        }

        $builder
            ->add('portfolio', CheckboxType::class, [
                'label' => 'Activate',
                'translation_domain' => 'settings',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ]
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['emailToCommsy'])
            ->setDefaults(['translation_domain' => 'profile']);
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
        return 'room_profile';
    }

}
