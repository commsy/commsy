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

namespace App\Message;

/**
 * Signal that a new entry was published in a room.
 *
 * Carries the item-derived snapshot captured in-request by the subscriber, so
 * the (async) handler needs no legacy item lookup and never depends on a
 * request-scoped legacy environment. Routed to the async transport by the
 * `App\Message\*` routing rule in config/packages/messenger.yaml.
 */
final readonly class NotifyNewEntryMessage
{
    public function __construct(
        public int $sourceItemId,
        public int $contextId,
        public string $sourceItemType,
        public string $title,
        public int $creatorUserItemId,
        public ?string $actorName = null,
    ) {
    }
}
