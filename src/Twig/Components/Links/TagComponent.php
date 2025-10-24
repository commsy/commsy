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
use App\Form\Model\Tags;
use App\Form\Type\Item\ItemTagsType;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\RoomService;
use cs_label_item;
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
final class TagComponent extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public bool $editMode = false;

    #[LiveProp]
    public bool $embedded = false;

    #[LiveProp]
    public int $itemId;

    #[LiveProp]
    public int $versionId;

    #[LiveProp]
    public ?Tags $formData = null;

    public function __construct(
        private readonly ItemService $itemService,
        private readonly RoomService $roomService,
    ) {
    }

    #[PostMount]
    public function loadTags(): void
    {
        if ($this->formData == null) {
            $legacyItem = $this->itemService->getTypedItem($this->itemId);
            $tagNames = (new ArrayCollection(iterator_to_array($legacyItem->getBuzzwordList())))
                ->map(fn (cs_label_item $label) => $label->getName())
            ;
            $this->formData = (new Tags())->setTags($tagNames->toArray());
        }

        $legacyBaseItem = $this->itemService->getItem($this->itemId);
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        // If the item is a draft and tags are mandatory, start in embedded edit mode
        if ($legacyBaseItem->isDraft() && $legacyRoom->withBuzzwords() && $legacyRoom->isBuzzwordMandatory()) {
            $this->editMode = $this->embedded = true;
        }
    }

    protected function instantiateForm(): FormInterface
    {
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        return $this->createForm(ItemTagsType::class, $this->formData, [
            'roomId' => $legacyItem->getContextID(),
            'mandatoryTags' => $legacyRoom->withBuzzwords() && $legacyRoom->isBuzzwordMandatory()
        ]);
    }

    public function getTags(): iterable
    {
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        return $legacyItem->getBuzzwordList();
    }

    #[LiveAction]
    public function enableEditMode(): void
    {
        $this->editMode = true;
    }

    #[LiveAction]
    #[LiveListener('DraftEdit:save')]
    public function save(
        EventDispatcherInterface $eventDispatcher,
        LabelService $labelService,
        #[LiveArg]
        bool $fromButton = false
    ): void
    {
        $this->submitForm();

        /** @var Tags $tags */
        $tags = $this->getForm()->getData();
        $tagCollection = new ArrayCollection($tags->getTags());

        // Are buzzwords enabled?
        $legacyItem = $this->itemService->getTypedItem($this->itemId);
        $legacyRoom = $this->roomService->getRoomItem($legacyItem->getContextID());

        if (!$legacyRoom->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        // Create new buzzwords currently not persisted
        $roomTags = new ArrayCollection($labelService->getHashtagItems($legacyRoom->getItemID())->to_array());
        $missingTagNames = $tagCollection->filter(fn (string $name) =>
            !$roomTags->exists(fn (int $key, cs_label_item $label) => $label->getName() === $name)
        );

        $idsToStore = [];
        foreach ($missingTagNames as $missingTagName) {
            if (!empty($missingTagName)) {
                $idsToStore[] = $labelService
                    ->getNewHashtag($missingTagName, $legacyRoom->getItemID())
                    ->getItemID();
            }
        }

        // Get all buzzword id's that should be linked
        $currentTagIds = $roomTags->filter(fn (cs_label_item $label) =>
            $tagCollection->exists(fn (int $key, string $name) => $label->getName() === $name)
        )->map(fn (cs_label_item $label) => $label->getItemID());
        $idsToStore = array_merge($idsToStore, $currentTagIds->toArray());

        // Update item
        $legacyItem->setBuzzwordListByID($idsToStore);
        $legacyItem->save();

        $this->editMode = false;

        // If the save action was not triggered by the tag save button, but from the "DraftEdit:save" event
        // send back a saved event
        if (!$fromButton) {
            $eventDispatcher->dispatch(new CommsyEditEvent($legacyItem), CommsyEditEvent::EDIT);
            $this->emit('Links:TagComponent:saved', componentName: 'Items:DraftEdit');
        }
    }

    #[LiveAction]
    public function cancel(): void
    {
        $this->editMode = false;
    }
}
