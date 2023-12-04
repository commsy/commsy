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

use App\Form\Type\CategoryType;
use App\Utils\CategoryService;
use App\Utils\RoomService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFilterType extends AbstractType
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CategoryService $categoryService,
        private readonly RoomService $roomService
    ) {
    }

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
        // extract room id from request and build filter accordingly
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomId = $attributes->getInt('roomId');

                $categories = $this->categoryService->getTags($roomId);
                $formCategories = $this->transformTagArray($categories);

                $builder
                    ->add('category', CategoryType::class, ['choices' => $formCategories, 'choice_label' => function ($choice, $key, $value) {
                        // remove the trailing category (aka tag) ID from $key (which was used in transformTagArray() to uniquify the key)
                        $label = implode('_', explode('_', $key, -1));

                        return $label;
                    }, 'multiple' => true, 'expanded' => true, 'label' => false]);
            }
        }
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => false, 'validation_groups' => ['filtering']]);
    }

    private function transformTagArray($tagArray)
    {
        $array = [];

        foreach ($tagArray as $tag) {
            // NOTE: in order to form unique array keys, we append the category (aka tag) ID to the category title;
            // the category ID will be stripped again from the title via the `choice_label` field option
            $array[$tag['title'].'_'.$tag['item_id']] = $tag['item_id'];

            if (!empty($tag['children'])) {
                $array[$tag['title'].'_sub_'.$tag['item_id']] = $this->transformTagArray($tag['children']);
            }
        }

        return $array;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $showExpanded = false;
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomItem = $this->roomService->getRoomItem($attributes->getInt('roomId'));
                $showExpanded = $roomItem->isTagsShowExpanded();
            }
        }
        $view->vars['showExpanded'] = $showExpanded;
    }
}
