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

use App\Entity\AccountIndex;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountIndexType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ids', Types\CollectionType::class, [
                'entry_type' => Types\CheckboxType::class,
                'required' => false,
            ])
            ->add('indexViewAction', Types\ChoiceType::class, [
                'choices' => [
                    'No action' => 0,
                    '-----------------' => 16,
                    'Delete user id(s)' => 1,
                    'Lock user id(s)' => 2,
                    'Activate user id(s)' => 3,
                    'Email change login' => 4,
                    '------------------' => 17,
                    'Satus user' => 5,
                    'Status moderator' => 6,
                    '-------------------' => 18,
                    'Make contact' => 7,
                    'Remove contact' => 8,
                    '--------------------' => 19,
                    'Send mail' => 9,
                    '---------------------' => 20,
                    'Hide mail all wrks' => 13,
                    'Show mail all wrks' => 15,
                ],
                'required' => true,
                'label' => 'Action',
                'translation_domain' => 'portal',
            ])
            ->add('execute', Types\SubmitType::class, [
                'label' => 'Execute',
                'translation_domain' => 'portal',
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
            'data_class' => AccountIndex::class,
            'translation_domain' => 'portal',
        ]);
    }
}
