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

use App\Form\Type\Custom\Select2ChoiceType;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class XhrCategorizeActionOptionsType extends AbstractType
{
    private \cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, private CategoryService $categoryService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

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
        /** @var \cs_tag2tag_manager $tag2TagManager */
        $tag2TagManager = $this->legacyEnvironment->getTag2TagManager();

        $builder
            ->add('choices', Select2ChoiceType::class, [
                'label' => $options['label'],
                'required' => false,
                'choices' => $options['choices'],
                'choice_label' => function ($choice, $key, $value) use ($tag2TagManager) {
                    // remove the trailing category ID from $key (which was used in LabelService->transformTagArray() to uniquify the key)
                    $displayName = implode('_', explode('_', $key, -1));

                    // append the name(s) of this category's parent categories
                    $parentCategoryIds = $tag2TagManager->getFatherItemIDArray($value);
                    if (!empty($parentCategoryIds)) {
                        // create array of category names from category IDs
                        $parentCategoryNames = array_map(function (int $categoryId) {
                            /** @var \cs_tag_item $categoryItem */
                            $categoryItem = $this->categoryService->getTag($categoryId);

                            return $categoryItem->getTitle();
                        }, $parentCategoryIds);

                        // the prefix helps to indent sub-categories visually
                        $prefix = str_repeat('- ', is_countable($parentCategoryIds) ? count($parentCategoryIds) : 0);

                        // display name examples for 2nd- and 3rd-level categories:
                        // - Subcategory 1 (Category 1)
                        // -- Subsubcategory 1 (Category 1 > Subcategory 1)
                        $displayName = $prefix.$displayName.' ('.implode(' > ', array_reverse($parentCategoryNames)).')';
                    }

                    return $displayName;
                },
                'multiple' => true,
                'attr' => [
                    'class' => 'uk-width-1-1',
                ],
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
        $resolver
            ->setRequired(['label', 'choices'])
            ->setDefaults([
                'translation_domain' => 'room',
                'csrf_protection' => false,
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
    public function getBlockPrefix()
    {
        return 'xhr_action';
    }
}
