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

use App\Validator\Constraints\UniquePortfolioCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class PortfolioEditCategoryType extends AbstractType
{
    /**
     * PortfolioType constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->buildChoices($options['categories']);

        $builder
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $choices,
                'choice_label' => function ($choice, $key, $value) {
                    // remove the trailing category ID from $key (which was used in buildChoices() to uniquify the key)
                    $label = implode('_', explode('_', $key, -1));

                    return $label;
                },
                'translation_domain' => 'portfolio',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Count([
                        'min' => 1,
                        'max' => 1,
                    ]),
                    new UniquePortfolioCategory([
                        'portfolioId' => $options['portfolioId'],
                    ]),
                ],
            ])
            ->add('addCategory', SubmitType::class, [
                'label' => 'Add category',
                'validation_groups' => false,
                'translation_domain' => 'portfolio',
            ])
            ->add('title', TextType::class, ['label' => false, 'required' => false, 'attr' => [
                'placeholder' => 'New Category',
                'class' => 'uk-form-width-medium',
            ], 'translation_domain' => 'category'])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => $this->translator->trans('Insert description here', [], 'portfolio'),
                ],
                'required' => false,
                'translation_domain' => 'portfolio',
            ])
            ->add('delete-category', HiddenType::class, [
                'label' => false,
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'validation_groups' => false,
            ])
        ;

        // Event listener for modifications based on the underlying data
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            if (is_numeric($options['categoryId'])) {
                $form
                    ->add('delete', SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-danger',
                        ],
                    ])
                ;
            }
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['categories', 'categoryId', 'portfolioId'])
            ->setDefaults(['translation_domain' => 'form'])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'portfolio';
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
