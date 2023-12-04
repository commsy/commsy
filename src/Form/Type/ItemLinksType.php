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

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use cs_environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemLinksType extends AbstractType
{
    private readonly cs_environment $environment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomService $roomService,
        private readonly ItemService $itemService
    ) {
        $this->environment = $legacyEnvironment->getEnvironment();
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
        $builder
            ->add('itemsLinked', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'required' => false,
                'entry_type' => CheckboxType::class,
                'entry_options' => [
//                    'choices' => $options['itemsLinked'],
                ],
            ])
            ->add('itemsLatest', ChoiceType::class, ['placeholder' => false, 'choices' => $options['itemsLatest'], 'required' => false, 'expanded' => true, 'multiple' => true])
            ->add('categories', TreeChoiceType::class, ['placeholder' => false, 'choices' => $options['categories'], 'required' => false, 'expanded' => true, 'multiple' => true, 'constraints' => $options['categoryConstraints']])
            ->add('hashtags', ChoiceType::class, ['placeholder' => false, 'choices' => $options['hashtags'], 'label' => 'hashtags', 'required' => false, 'expanded' => true, 'multiple' => true, 'constraints' => $options['hashtagConstraints']])
            ->add('newHashtag', TextType::class, ['attr' => ['placeholder' => $options['placeholderText']], 'label' => 'newHashtag', 'required' => false])
            ->add('newHashtagAdd', ButtonType::class, ['attr' => ['id' => 'addNewHashtag', 'data-cs-add-hashtag' => $options['hashtagEditUrl']], 'label' => 'addNewHashtag', 'translation_domain' => 'form'])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'save', 'translation_domain' => 'form'])
            ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form'])
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
            ->setDefaults([
                'translation_domain' => 'item',
                'lock_protection' => true,
            ])
            ->setRequired([
                'filterRubric',
                'filterPublic',
                'items',
                'itemsLinked',
                'itemsLatest',
                'categories',
                'categoryConstraints',
                'hashtags',
                'hashtagConstraints',
                'hashtagEditUrl',
                'placeholderText',
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

    private function getTempData($filterData)
    {
        // get all items that are linked, temporary linked (i.e. already selected in the form) or can be linked
        $optionsData = [];

        if (empty($filterData['filterRubric']) || 'all' == $filterData['filterRubric']) {
            $rubricInformation = $this->roomService->getRubricInformation($this->environment->getCurrentContextId());
        } else {
            $rubricInformation = [$filterData['filterRubric']];
        }

        $itemManager = $this->environment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($this->environment->getCurrentContextId());
        $itemManager->setTypeArrayLimit($rubricInformation);

        if (isset($filterData['feedAmount'])) {
            $itemManager->setIntervalLimit($filterData['feedAmount']);
        }
        $itemManager->select();
        $itemList = $itemManager->get();

        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $this->itemService->getTypedItem($tempItem->getItemId());
            if ($tempTypedItem) {
                if ('user' != $tempTypedItem->getItemType()) {
                    $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                } else {
                    $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getFullname();
                }
            }
            $tempItem = $itemList->getNext();
        }

        if (empty($optionsData['items'])) {
            $optionsData['items'] = [];
        }

        $tempData = [];
        if (isset($filterData['items'])) {
            $tempData = $filterData['items'];
        }

        if (isset($filterData['itemsLinked'])) {
            $tempData = array_merge($tempData, $filterData['itemsLinked']);
        }

        $itemManager->reset();
        $itemLinkedList = $itemManager->getItemList($tempData);

        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $this->itemService->getTypedItem($tempLinkedItem->getItemId());
            if ('user' != $tempTypedLinkedItem->getItemType()) {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getTitle();
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
            }
            $tempLinkedItem = $itemLinkedList->getNext();
        }

        $itemManager->reset();

        // $optionsData['itemsLinked'] = $filterData['items'];
        if (empty($optionsData['itemsLinked'])) {
            $optionsData['itemsLinked'] = [];
        }

        if (isset($filterData['remove'])) {
            if (isset($optionsData['items'][$filterData['remove']])) {
                unset($optionsData['items'][$filterData['remove']]);
            }
            if (isset($optionsData['itemsLinked'][$filterData['remove']])) {
                unset($optionsData['itemsLinked'][$filterData['remove']]);
            }
        }

        return $optionsData;
    }
}
