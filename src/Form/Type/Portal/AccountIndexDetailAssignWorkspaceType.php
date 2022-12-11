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

use App\Entity\PortalUserAssignWorkspace;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountIndexDetailAssignWorkspaceType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', Types\TextType::class, [
                'label' => 'Name',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('userId', Types\TextType::class, [
                'label' => 'User ID',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('searchForWorkspace', Types\TextType::class, [
                'label' => 'Search for workspace',
                'translation_domain' => 'portal',
                'required' => false,
                'help' => 'Workspace assign help',
            ])
            ->add('search', Types\SubmitType::class, [
                'label' => 'Search',
                'translation_domain' => 'portal',
            ])
            ->add('workspaceSelection', Types\ChoiceType::class, [
                'label' => 'Select workspace',
                'expanded' => false,
                'placeholder' => false,
                'choices' => [
                    'Please choose' => '0',
                ],
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('descriptionOfParticipation', Types\TextareaType::class, [
                'label' => 'Description of participation',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'Save',
                'translation_domain' => 'portal',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PortalUserAssignWorkspace::class,
            'translation_domain' => 'portal',
        ]);
    }
}
