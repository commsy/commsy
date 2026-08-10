<?php

declare(strict_types=1);

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

namespace App\Legacy;

use App\Message\ReindexItem;
use App\Message\RemoveItemFromIndex;
use App\Search\ItemIndexer;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Lets `cs_item` queue index updates without reaching for persisters itself.
 *
 * The legacy side fetches this service from the container and calls it; everything
 * below the seam is ordinary wiring. Only the item's identity is queued — the worker
 * reads the current state, so this may run before the caller's own writes settle.
 */
readonly class LegacyIndexDispatcher
{
    public function __construct(
        private ItemIndexer $itemIndexer,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function reindex(int $itemId, string $itemType): void
    {
        if (!$this->itemIndexer->isReindexable($itemType)) {
            return;
        }

        $this->messageBus->dispatch(new ReindexItem($itemId, $itemType));
    }

    public function remove(int $itemId, string $itemType): void
    {
        if (!$this->itemIndexer->isRemovable($itemType)) {
            return;
        }

        $this->messageBus->dispatch(new RemoveItemFromIndex($itemId, $itemType));
    }
}
