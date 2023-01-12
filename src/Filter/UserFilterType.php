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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
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
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'uk-button uk-button-primary'], 'label' => 'Restrict', 'translation_domain' => 'form'])
            ->add('user_search', TextType::class, [
                'label' => 'Name',
                'translation_domain' => 'room',
                'label_attr' => ['class' => 'uk-form-label'],
                'attr' => [
                    'placeholder' => 'search-user-filter-placeholder',
                    'class' => 'cs-form-horizontal-full-width',
                ],
                'required' => false,
            ])

            ->add('rubrics', RubricFilterType::class, ['label' => false])
        ;

        if ($options['hasHashtags']) {
            $builder->add('hashtag', HashTagFilterType::class, ['label' => false]);
        }

        if ($options['hasCategories']) {
            $builder->add('category', CategoryFilterType::class, ['label' => false]);
        }

        if ($options['isModerator']) {
            $statusChoices = [
                'is blocked' => '0',
                'is applying' => '1',
                'user' => '8',
                'moderator' => '3',
                'is contact' => 'is contact',
                'reading user' => '4',
            ];
        } else {
            $statusChoices = [
                'moderator' => '3',
            ];
        }
        $builder->add('user_status', ChoiceType::class, ['placeholder' => false, 'choices' => $statusChoices, 'label' => 'status', 'translation_domain' => 'user', 'required' => false, 'expanded' => false, 'multiple' => false, 'placeholder' => 'no restrictions']);
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
                'validation_groups' => ['filtering'],
                // avoid NotBlank() constraint-related message
                'method' => 'get',
            ])
            ->setRequired(['hasHashtags', 'hasCategories', 'isModerator'])
        ;
    }
}
