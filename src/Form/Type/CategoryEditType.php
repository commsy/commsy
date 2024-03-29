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

class CategoryEditType extends AbstractType
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
        $choices = $this->buildChoices($options['categories']);

        $builder
            ->add('category', CategoryType::class, ['choices' => $choices, 'choice_label' => function ($choice, $key, $value) {
                // remove the trailing category ID from $key (which was used in buildChoices() to uniquify the key)
                $label = implode('_', explode('_', $key, -1));

                return $label;
            }, 'multiple' => true, 'expanded' => true, 'label' => false])

            ->add('structure', Types\HiddenType::class, [
            ])

            ->add('update', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
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
            ->setRequired(['categories'])
        ;
    }

    private function buildChoices($categories)
    {
        $choices = [];

        foreach ($categories as $category) {
            // NOTE: in order to form unique array keys, we append the category ID to the category title;
            // the category ID will be stripped again from the title via the `choice_label` field option
            $choices[$category['title'].'_'.$category['item_id']] = $category['item_id'];

            if (!empty($category['children'])) {
                $choices[$category['title'].'_sub_'.$category['item_id']] = $this->buildChoices($category['children']);
            }
        }

        return $choices;
    }
}
