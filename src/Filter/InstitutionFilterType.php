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

namespace App\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstitutionFilterType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                ],
                'label' => 'Restrict',
                'translation_domain' => 'form',
            ])
            ->add('hide-deactivated-entries', Filters\ChoiceFilterType::class, [
                'choices' => [
                    'only activated' => 'only_activated',
                    'only deactivated' => 'only_deactivated',
                    'no restrictions' => 'all',
                ],
                'translation_domain' => 'form',
                'placeholder' => false,
            ]);

        if ($options['hasCategories']) {
            $builder->add('category', CategoryFilterType::class, [
                'label' => false,
            ]);
        }

        if ($options['hasHashtags']) {
            $builder->add('hashtag', HashTagFilterType::class, [
                'label' => false,
            ]);
        }
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method' => 'get',
            ])
            ->setRequired([
                'hasHashtags',
                'hasCategories',
            ]);
    }
}
