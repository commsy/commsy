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

namespace App\EventSubscriber;

use App\Event\ReadStatusPreChangeEvent;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use cs_annotation_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ReadStatusSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ItemService $itemService,
        private ReaderService $readerService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReadStatusPreChangeEvent::class => 'onReadStatusPreChange',
        ];
    }

    public function onReadStatusPreChange(ReadStatusPreChangeEvent $event): void
    {
        $itemId = $event->getItemId();

        $item = $this->itemService->getItem($itemId);
        if (!$item) {
            return;
        }

        // for annotations, invalidate the read status cache of their linked (hosting) item
        if (CS_ANNOTATION_TYPE === $item->getItemType()) {
            /** @var cs_annotation_item $annotation */
            $annotation = $this->itemService->getTypedItem($itemId);
            $linkedItem = $annotation->getLinkedItem();
            if ($linkedItem) {
                $item = $linkedItem;
            }
        }

        $this->readerService->invalidateCachedReadStatusForItem($item);
    }
}
