<?php

namespace App\Form\Type;

use CommsyBundle\Form\Type\Custom\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Entity\Room;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'uk-form-width-large',
                ],
            ])
            ->add('master_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'preferred_choices' => $options['preferredChoices'],
                'placeholder' => 'No template',
                'required' => false,
                'mapped' => false,
                'label' => 'Template',
                'data' => (!empty($options['preferredChoices'])) ? $options['preferredChoices'][0] : '',
                'attr' => [
                    'data-description' => json_encode($options['descriptions']),
                ]
            ])
            ->add('template_description', TextareaType::class, [
                'attr' => [
                    'style' => 'display: none;',
                    'rows' => 3,
                    'cols' => 100,
                    'readonly' => true,
                ],
                'mapped' => false,
                'required' => false,
                'label_attr' => [
                    'style' => 'display: none;',
                ]
            ]);
            if (!empty($options['times'])) {
                $builder->add('time_interval', Select2ChoiceType::class, [
                    'choices' => $options['times'],
                    'required' => false,
                    'mapped' => false,
                    'expanded' => false,
                    'multiple' => true,
                    'label' => $options['timesDisplay'],
                    'translation_domain' => 'room',
                ]);
            }
            $builder->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => array(
                    'User preferences' => 'user',
                    'German' => 'de',
                    'English' => 'en',
                ),
                'label' => 'language',
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'translation_domain' => 'room',
                'choice_translation_domain' => 'settings',
            ))
            ->add('room_description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => 'Room description...',
                ],
                'required' => false,
                'translation_domain' => 'room',
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ));
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
                'templates',
                'descriptions',
                'preferredChoices',
                'timesDisplay',
                'times',
            ])
            ->setDefaults([
                'data_class' => Room::class,
                'translation_domain' => 'project',
            ]);
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
        return 'project';
    }
}