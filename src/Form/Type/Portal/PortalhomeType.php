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

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PortalhomeType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('showRoomsOnHome', ChoiceType::class, [
                'label' => 'Show',
                'expanded' => true,
                'choices' => [
                    'All open workspaces' => 'normal',
                    'Only community workspaces' => 'onlycommunityrooms',
                    'Only project workspaces' => 'onlyprojectrooms',
                ],
            ])
            ->add('sortRoomsBy', ChoiceType::class, [
                'label' => 'Sort list of all rooms by',
                'expanded' => true,
                'choices' => [
                    'Activity' => 'activity',
                    'Title' => 'title',
                ],
                'choice_translation_domain' => 'portal',
            ])
            ->add('defaultFilterHideTemplates', ChoiceType::class, [
                'label' => 'portal.form.label.default_filter_hide_templates',
                'expanded' => true,
                'choices' => [
                    'settings.activated' => true,
                    'settings.deactivated' => false,
                ],
                'choice_translation_domain' => 'settings',
            ])
            ->add('defaultFilterHideArchived', ChoiceType::class, [
                'label' => 'portal.form.label.default_filter_hide_archived',
                'expanded' => true,
                'choices' => [
                    'settings.activated' => true,
                    'settings.deactivated' => false,
                ],
                'choice_translation_domain' => 'settings',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
