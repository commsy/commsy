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
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchItemType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phrase', Types\SearchType::class, [
                'attr' => [
                    'placeholder' => 'Search in room...',
                    'class' => 'uk-search-field',
                ],
                'required' => false,
                'translation_domain' => 'search',
            ])
//            ->add('type', Types\ChoiceType::class, [
//                'choices' => [
//                    'a' => 'Rubrik A',
//                    'b' => 'Rubrik B',
//                ]
//            ])
//            ->add('submit', Types\SubmitType::class, [
//                'attr' => [
//                    'class' => 'uk-button-primary',
//                ],
//                'label' => 'Search',
//                'translation_domain' => 'search',
//            ])
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
            ->setRequired([])
        ;
    }
}
