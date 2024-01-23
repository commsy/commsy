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

use App\Security\Authorization\Voter\CategoryVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemCatsBuzzType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {}

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
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $options['categories'],
                'choice_label' => function ($choice, $key, $value) {
                    // remove the trailing category ID from $key (which was used in LabelService->transformTagArray() to uniquify the key)
                    $label = implode('_', explode('_', $key, -1));

                    return $label;
                },
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => $options['categoryConstraints'],
            ])
            ->add('hashtags', ChoiceType::class, [
                'placeholder' => false,
                'choices' => $options['hashtags'],
                'label' => 'hashtags',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => $options['hashtagConstraints'],
            ])
            ->add('newHashtag', TextType::class, [
                'attr' => [
                    'placeholder' => $options['placeholderText'],
                ],
                'label' => 'newHashtag',
                'required' => false,
            ])
            ->add('newHashtagAdd', ButtonType::class, [
                'attr' => [
                    'id' => 'addNewHashtag',
                    'data-cs-add-hashtag' => $options['hashtagEditUrl'],
                ],
                'label' => 'addNewHashtag',
                'translation_domain' => 'form',
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            // Only add the form for new categories if the user is allowed to create them
            if ($this->security->isGranted(CategoryVoter::EDIT)) {
                $form = $event->getForm();

                $form->add('newCategory', TextType::class, [
                    'attr' => [
                        'placeholder' => $options['placeholderTextCategories'],
                    ],
                    'label' => 'newCategory',
                    'required' => false,
                ]);
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
            ->setDefaults([
                'translation_domain' => 'item',
            ])
            ->setRequired([
                'categories',
                'categoryConstraints',
                'hashtags',
                'hashtagConstraints',
                'hashtagEditUrl',
                'placeholderText',
                'placeholderTextCategories',
            ])
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
        return 'itemLinks';
    }
}
