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
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\Tree\Tree;
use App\Utils\Tree\TreeBuilderInterface;
use App\Utils\Tree\TreeMode;
use cs_environment;
use cs_item;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
    public ?int $versionId = null;

    #[LiveProp]
    public ?Categories $formData = null;

    public function __construct(
        private readonly ItemService $itemService,
        private readonly LabelService $labelService,
        private readonly CategoryService $categoryService,
        private readonly RoomService $roomService,
        private readonly TreeBuilderInterface $treeBuilder,
        private readonly Security $security,
        readonly LegacyEnvironment $environment
    ) {
        $this->legacyEnvironment = $environment->getEnvironment();
    }

    /**
     * Editing an entry's categories requires the right to edit that entry.
     *
     * Checked against the signed `itemId` prop, not against an action
     * argument: the client controls arguments, so a check on one of those
     * would not be a check on the entry actually being changed.
     */
    private function denyUnlessItemEditable(): void
    {
        if (!$this->security->isGranted(ItemVoter::EDIT, $this->itemId)) {
            throw new AccessDeniedException();
        }
    }

    #[PostMount]
    public function init(): void
    {
        $legacyBaseItem = $this->itemService->getItem($this->itemId);
        $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        // If the item is a draft and categories are mandatory, start in embedded edit mode
        if ($legacyBaseItem->isDraft() && $legacyRoom->withTags() && $legacyRoom->isTagMandatory()) {
            $this->editMode = $this->embedded = true;
        }
    }

    protected function instantiateForm(): FormInterface
    {
        if ($this->formData == null) {
            $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);
            $this->formData = new Categories()->setCategories($this->labelService->getLinkedCategoryIds($legacyItem));
        }

        $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        // TODO: This is just a workaround to prevent a legacy default to the portal id when
        // quering for categories resulting in an empty response
        $this->legacyEnvironment->setCurrentContextID($legacyItem->getContextID());

        $availableCategories = $this->labelService->getCategories($legacyItem->getContextID());
        return $this->createForm(ItemCategoryType::class, $this->formData, [
            'mandatoryCategories' => $legacyRoom->withTags() && $legacyRoom->isTagMandatory(),
            'availableCategories' => $availableCategories,
        ]);
    }

    #[LiveAction]
    public function addCategory(CategoryService $categoryService): void
    {
        $this->denyUnlessItemEditable();

        $this->submitForm(false);

        /** @var Categories $dto */
        $dto = $this->getForm()->getData();

        if ($dto->getNewCategory() && $this->isGranted(CategoryVoter::EDIT)) {
            $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);
            $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

            if (!$legacyRoom->withTags()) {
                throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
            }

            // Create new tag
            $newTag = $categoryService->addTag($dto->getNewCategory(), $legacyRoom->getItemID());

            // Add the new tag to the selected categories
            $currentCategories = $this->labelService->getLinkedCategoryIds($legacyItem);
            $currentCategories[] = $newTag->getItemID();

            // Update item
            $legacyItem->setTagListByID($currentCategories);
            $legacyItem->save();

            $this->formValues['newCategory'] = '';
            $this->resetForm();
        }
    }

    #[LiveAction]
    public function enableEditMode(): void
    {
        $this->editMode = true;
    }

    public function getItem(): cs_item
    {
        return $this->itemService->getTypedItem($this->itemId, $this->versionId);
    }

    public function getTree(TreeMode $mode): Tree
    {
        $tree = $this->treeBuilder->createTree($mode);

        $availableCategories = $this->categoryService->getTags($this->getItem()->getContextID());
        $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);

        $tree->setData($availableCategories);

        if ($mode === TreeMode::VIEW) {
            $tree->setHighlightedIds($this->labelService->getLinkedCategoryIds($legacyItem));
        } else {
            $tree->setCheckboxSelector('input[name="item_category[categories][]"]');
        }

        return $tree;
    }

    #[LiveAction]
    #[LiveListener('DraftEdit:save')]
    public function save(
        EventDispatcherInterface $eventDispatcher,
        ReaderService $readerService,
        #[LiveArg]
        bool $fromButton = false
    ): void
    {
        $this->denyUnlessItemEditable();

        $this->submitForm();

        /** @var Categories $categories */
        $categories = $this->getForm()->getData();
        $categoryCollection = new ArrayCollection($categories->getCategories());

        // Are categories enabled?
        $legacyItem = $this->itemService->getTypedItem($this->itemId, $this->versionId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        if (!$legacyRoom->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
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
        } else {
            $readerService->markItemAsRead($legacyItem);
        }

        $tag2tagManager = $this->legacyEnvironment->getTag2TagManager();
        $tag2tagManager->resetCachedChildrenIdArray();

        $tagManager = $this->legacyEnvironment->getTagManager();
        $tagManager->resetCache();

        $this->resetForm();
    }

    #[LiveAction]
    public function cancel(): void
    {
        $this->editMode = false;
    }
}
