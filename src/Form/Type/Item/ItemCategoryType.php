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

namespace App\Form\Type\Item;

use App\Form\Model\Categories;
use App\Security\Authorization\Voter\CategoryVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Count;

class ItemCategoryType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = $options['availableCategories'];

        $builder
            ->add('categories', ChoiceType::class, [
                'label' => false,
                'choices' => $categories,
                'choice_label' => function ($choice, $key, $value) {
                    // remove the trailing category ID from $key (which was used in LabelService->transformTagArray() to uniquify the key)
                    return implode('_', explode('_', $key, -1));
                },
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => $options['mandatoryCategories'] ?
                    [new Count(min: 1, minMessage: 'Please select at least one category')] :
                    [],
                'attr' => [
                    'class' => 'uk-hidden',
                ]
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            // Only add the form for new categories if the user is allowed to create them
            if ($this->security->isGranted(CategoryVoter::EDIT)) {
                $form = $event->getForm();

                $form->add('newCategory', TextType::class, [
                    'attr' => [
                        'placeholder' => new TranslatableMessage('New category', [], 'category'),
                    ],
                    'label' => false,
                    'required' => false,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Categories::class,
            ])
            ->setRequired(['mandatoryCategories', 'availableCategories'])
            ->setAllowedTypes('mandatoryCategories', 'bool')
            ->setAllowedTypes('availableCategories', 'array')
        ;
    }
}
