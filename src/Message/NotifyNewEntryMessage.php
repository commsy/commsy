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

use App\Enum\NotificationAction;

/**
 * Signal that a feed-relevant entry was created or edited in a room.
 *
 * Carries the item-derived snapshot captured in-request by the subscriber, so
 * the (async) handler needs no legacy item lookup and never depends on a
 * request-scoped legacy environment. {@see $action} says whether the event was
 * a create or an edit; {@see $occurredAt} is the event time and makes the
 * handler idempotent against messenger retries (a real edit happens at a new
 * time and is logged as another event). Routed to the async transport by the
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
        public bool $isDeactivated = false,
        public NotificationAction $action = NotificationAction::Created,
        public ?\DateTimeImmutable $occurredAt = null,
    ) {
    }
}
