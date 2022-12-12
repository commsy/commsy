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

use App\Form\Type\Event\AddContextFieldListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ContextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
            ])
            ->add('type_select', ChoiceType::class, ['placeholder' => false, 'choices' => $options['types'], 'label' => 'context type', 'required' => true, 'expanded' => true, 'multiple' => false, 'translation_domain' => 'room'])
            ->addEventSubscriber(new AddContextFieldListener())
            ->add('language', ChoiceType::class, ['placeholder' => false, 'choices' => ['User preferences' => 'user', 'German' => 'de', 'English' => 'en'], 'required' => true, 'expanded' => false, 'multiple' => false, 'help' => 'Room language tip', 'translation_domain' => 'settings', 'choice_translation_domain' => 'settings'])
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
            $builder->add('categories', ChoiceType::class, ['placeholder' => false, 'choices' => $roomCategories, 'label' => 'Room categories', 'required' => $options['linkRoomCategoriesMandatory'], 'expanded' => true, 'multiple' => true, 'translation_domain' => 'portal', 'constraints' => $constraints]);
        }

        $builder->add('save', SubmitType::class, [
            'label' => 'save',
            'translation_domain' => 'form',
        ])
        ->add('cancel', SubmitType::class, [
            'attr' => [
                'class' => 'uk-button-default',
                'formnovalidate' => '',
            ],
            'label' => 'cancel',
            'translation_domain' => 'form',
            'validation_groups' => false,
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
            ->setRequired([
                'types',
                'templates',
                'preferredChoices',
                'timesDisplay',
                'times',
                'linkCommunitiesMandantory',
                'linkRoomCategoriesMandatory',
                'roomCategories',
            ])
            ->setDefaults([
                'translation_domain' => 'project',
            ]);
    }
}
