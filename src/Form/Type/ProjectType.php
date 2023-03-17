<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

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
                    'data-description' => json_encode($options['descriptions'], JSON_THROW_ON_ERROR),
                ],
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
                ],
            ])
            ->add('createUserRooms', CheckboxType::class, [
                'label' => 'User room',
                'translation_domain' => 'settings',
                'mapped' => false,
                'required' => false,
                'label_attr' => ['class' => 'uk-form-label'],
                'help' => 'User room tooltip',
            ])
            ->add('userroom_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'placeholder' => 'No template',
                'required' => false,
                'mapped' => false,
                'label' => 'User room template',
                'translation_domain' => 'settings',
            ]);
        if (!empty($options['times'])) {
            $choices = [$this->translator->trans('Select some options') => ''] + $options['times'];

            $builder->add('time_interval', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $choices,
                'required' => false,
                'mapped' => false,
                'expanded' => false,
                'multiple' => true,
                'label' => $options['timesDisplay'],
                'translation_domain' => 'room',
            ]);
        }
        $builder->add('language', ChoiceType::class, ['placeholder' => false, 'choices' => ['User preferences' => 'user', 'German' => 'de', 'English' => 'en'], 'label' => 'language', 'required' => true, 'expanded' => false, 'multiple' => false, 'translation_domain' => 'room', 'choice_translation_domain' => 'settings'])
            ->add('room_description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => 'Room description...',
                ],
                'required' => false,
                'translation_domain' => 'room',
            ]);

        $constraints = [];
        if (isset($options['linkRoomCategoriesMandatory']) && $options['linkRoomCategoriesMandatory']) {
            $constraints[] = new Count(['min' => 1, 'minMessage' => 'Please select at least one category']);
        }

        $roomCategories = $options['roomCategories'];
        if (isset($roomCategories) && !empty($roomCategories) && isset($options['linkRoomCategoriesMandatory'])) {
            $builder->add('categories', ChoiceType::class, ['placeholder' => false, 'choices' => $roomCategories, 'label' => 'Room categories', 'required' => $options['linkRoomCategoriesMandatory'], 'expanded' => true, 'multiple' => true, 'translation_domain' => 'portal', 'constraints' => $constraints, 'mapped' => false]);
        }

        $builder->add('save', SubmitType::class, [
            'attr' => [
                'class' => 'uk-button-primary',
            ],
            'label' => 'save',
            'translation_domain' => 'form',
        ])
            ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form', 'validation_groups' => false]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
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
                'linkRoomCategoriesMandatory',
                'roomCategories',
            ])
            ->setDefaults([
                'translation_domain' => 'project',
            ]);
    }
}
