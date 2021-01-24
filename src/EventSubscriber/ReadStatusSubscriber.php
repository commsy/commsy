<?php


namespace App\EventSubscriber;


use App\Event\ReadStatusWillChangeEvent;
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
            ReadStatusWillChangeEvent::class => 'onReadStatusWillChange',
        ];
    }

    public function onReadStatusWillChange(ReadStatusWillChangeEvent $event)
    {
        $itemId = $event->getItemId();

        $item = $this->itemService->getItem($itemId);
        if (!$item) {
            return;
        }

        $this->readerService->invalidateCachedReadStatusForItem($item);
    }
}