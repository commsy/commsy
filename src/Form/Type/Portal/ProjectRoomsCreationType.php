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
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectRoomsCreationType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('projectRoomCreationStatus', Types\ChoiceType::class, [
                'label' => 'Room creation',
                'expanded' => true,
                'choices' => [
                    'Project room creation on portal' => 'portal',
                    'Project room creation in community rooms only' => 'communityroom',
                ],
            ])
            ->add('defaultProjectTemplateID', Types\ChoiceType::class, [
                'choices' => $options['templateChoices'] ?? [],
                'placeholder' => false,
                'required' => false,
                'label' => 'Default template',
                'help' => 'Default template help text',
            ])
            ->add('projectRoomLinkStatus', Types\ChoiceType::class, [
                'label' => 'Mandatory assignment',
                'expanded' => true,
                'choices' => [
                    'Yes' => 'mandatory',
                    'No' => 'optional',
                ],
                'choice_translation_domain' => 'form',
                'help' => 'Mandatory room assignment help text',
            ])
            ->add('projectShowDeactivatedEntriesTitle', Types\ChoiceType::class, [
                'label' => 'portal.form.workspaces.show_deactivated_entries_label',
                'expanded' => true,
                'choices' => [
                    'portal.form.workspaces.show_deactivated_entries_label.show_title' => true,
                    'portal.form.workspaces.show_deactivated_entries_label.hide_title' => false,
                ],
            ])
            ->add('save', Types\SubmitType::class, [
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
        $resolver
            ->setRequired([
                'templateChoices',
            ])
            ->setDefaults([
                'data_class' => Portal::class,
                'translation_domain' => 'portal',
            ]);
    }
}
