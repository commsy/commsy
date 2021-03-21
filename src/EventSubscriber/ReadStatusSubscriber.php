<?php


namespace App\EventSubscriber;


use App\Event\ReadStatusPreChangeEvent;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReadStatusSubscriber implements EventSubscriberInterface
{
    /**
     * @var ItemService $itemService
     */
    private $itemService;

    /**
     * @var ReaderService $readerService
     */
    private $readerService;

    public function __construct(ItemService $itemService, ReaderService $readerService)
    {
        $this->itemService = $itemService;
        $this->readerService = $readerService;
    }

    public static function getSubscribedEvents()
    {
        return [
            ReadStatusPreChangeEvent::class => 'onReadStatusPreChange',
        ];
    }

    public function onReadStatusPreChange(ReadStatusPreChangeEvent $event)
    {
        $itemId = $event->getItemId();

        $item = $this->itemService->getItem($itemId);
        if (!$item) {
            return;
        }

        // for annotations, invalidate the read status cache of their linked (hosting) item
        if ($item->getItemType() === CS_ANNOTATION_TYPE) {
            /** @var \cs_annotation_item $annotation */
            $annotation = $this->itemService->getTypedItem($itemId);
            $linkedItem = $annotation->getLinkedItem();
            if ($linkedItem) {
                $item = $linkedItem;
            }
        }

        $this->readerService->invalidateCachedReadStatusForItem($item);
    }
}