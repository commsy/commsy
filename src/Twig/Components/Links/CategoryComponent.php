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

namespace App\Twig\Components\Links;

use App\Event\CommsyEditEvent;
use App\Form\Model\Categories;
use App\Form\Type\Item\ItemCategoryType;
use App\Security\Authorization\Voter\CategoryVoter;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\RoomService;
use cs_environment;
use cs_item;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class CategoryComponent extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    private cs_environment $legacyEnvironment;

    #[LiveProp(writable: true)]
    public bool $editMode = false;

    #[LiveProp]
    public bool $embedded = false;

    #[LiveProp]
    public int $itemId;

    #[LiveProp]
    public int $versionId;

    #[LiveProp]
    public ?Categories $formData = null;

    public function __construct(
        private readonly ItemService $itemService,
        private readonly LabelService $labelService,
        private readonly CategoryService $categoryService,
        private readonly RoomService $roomService,
        readonly LegacyEnvironment $environment,
    ) {
        $this->legacyEnvironment = $environment->getEnvironment();
    }

    #[PostMount]
    public function loadTags(): void
    {
        if ($this->formData == null) {
            $legacyItem = $this->itemService->getTypedItem($this->itemId);
            $this->formData = (new Categories())->setCategories($this->labelService->getLinkedCategoryIds($legacyItem));
        }

        $legacyBaseItem = $this->itemService->getItem($this->itemId);
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        // If the item is a draft and categories are mandatory, start in embedded edit mode
        if ($legacyBaseItem->isDraft() && $legacyRoom->withTags() && $legacyRoom->isTagMandatory()) {
            $this->editMode = $this->embedded = true;
        }
    }

    protected function instantiateForm(): FormInterface
    {
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        // TODO: This is just a workaround to prevent a legacy default to the portal id when
        // quering for categories resulting in an empty response
        $this->legacyEnvironment->setCurrentContextID($legacyItem->getContextID());

        $this->dispatchBrowserEvent('category:init');

        return $this->createForm(ItemCategoryType::class, $this->formData, [
            'roomId' => $legacyItem->getContextID(),
            'mandatoryCategories' => $legacyRoom->withTags() && $legacyRoom->isTagMandatory()
        ]);
    }

    public function getItem(): cs_item
    {
        return $this->itemService->getTypedItem($this->itemId);
    }

    public function getRoomCategories(): iterable
    {
        return $this->categoryService->getTags($this->getItem()->getContextID());
    }

    #[LiveAction]
    public function enableEditMode(): void
    {
        $this->editMode = true;
        $this->dispatchBrowserEvent('category:edit');
    }

    #[LiveAction]
    #[LiveListener('DraftEdit:save')]
    public function save(
        EventDispatcherInterface $eventDispatcher,
        CategoryService $categoryService,
        #[LiveArg]
        bool $fromButton = false
    ): void
    {
        $this->submitForm();

        /** @var Categories $categories */
        $categories = $this->getForm()->getData();
        $categoryCollection = new ArrayCollection($categories->getCategories());

        // Are categories enabled?
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        if (!$legacyRoom->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
        }

        // Create new tag currently not persisted
        if ($categories->getNewCategory() && $this->isGranted(CategoryVoter::EDIT)) {
            $categoryCollection->add($categoryService->addTag(
                $categories->getNewCategory(),
                $legacyRoom->getItemID())->getItemID()
            );
        }

        // Update item
        $legacyItem->setTagListByID($categoryCollection->toArray());
        $legacyItem->save();

        $this->editMode = false;

        // If the save action was not triggered by the categories save button, but from the "DraftEdit:save" event
        // send back a saved event
        if (!$fromButton) {
            $eventDispatcher->dispatch(new CommsyEditEvent($legacyItem), CommsyEditEvent::EDIT);
            $this->emit('Links:CategoryComponent:saved', componentName: 'Items:DraftEdit');
        }
    }

    #[LiveAction]
    public function cancel(): void
    {
        $this->editMode = false;
    }
}
