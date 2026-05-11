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

declare(strict_types=1);

namespace App\Security\Permission\Subject;

use App\Entity\Room;
use App\Repository\RoomRepository;
use DateTimeImmutable;

/**
 * Builds an {@see ItemViewSubject} from an Item-domain entity
 * (Materials, Discussions, Announcement, …).
 *
 * Type hint is `object` rather than `Items` because — somewhat
 * surprisingly — none of the rubric-subclasses actually extend
 * {@see \App\Entity\Items} in PHP, even though the Doctrine
 * `DiscriminatorMap` lists them. They share the wire-level shape of
 * `items`-table columns via independent declarations + duck-typing on
 * `getItemId()` / `getContextId()` / `getActivationDate()` /
 * `getCreator()`. Lifting Items into a real abstract parent class is
 * pre-existing technical debt that's out of scope here.
 *
 * Inputs the subject needs from the entity:
 *  - `itemId`            : `getItemId(): int`
 *  - `contextId`         : `getContextId(): ?int`
 *  - `creatorId`         : `getCreator()?->getItemId()` — uniformly
 *                          available via {@see \App\Utils\EntityUsersTrait}
 *                          on every Item-domain subclass we've migrated.
 *  - `isDeactivated`     : `getActivationDate()` in the future
 *                          (mirrors legacy `cs_item::isNotActivated`).
 *  - `contextIsDeleted`  : Room.deletionDate IS NOT NULL — the factory
 *                          looks up the Room via {@see RoomRepository}
 *                          unless the caller hands in the matching Room
 *                          (cheap pre-loaded shortcut).
 *
 * Subclasses that pre-date the EntityUsersTrait rollout (Files,
 * Calendars, LinkItems, Assessments — partial-column entities) degrade
 * to `creatorId = null` via {@see method_exists()} rather than crashing.
 */
final readonly class ItemViewSubjectFactory
{
    public function __construct(
        private RoomRepository $roomRepository,
    ) {
    }

    /**
     * @param Items     $item             the linked / target item
     * @param Room|null $preloadedContext optional: if the caller
     *                                    already holds the Room
     *                                    representing $item->contextId,
     *                                    pass it to skip the lookup.
     */
    public function fromItem(object $item, ?Room $preloadedContext = null): ItemViewSubject
    {
        $contextId = $item->getContextId();

        return new ItemViewSubject(
            itemId: $item->getItemId(),
            contextId: $contextId,
            creatorId: $this->resolveCreatorId($item),
            isDeactivated: $this->isDeactivated($item),
            contextIsDeleted: $this->isContextDeleted($contextId, $preloadedContext),
        );
    }

    private function resolveCreatorId(object $item): ?int
    {
        // Every Item-domain entity that we migrated to EntityUsersTrait
        // exposes getCreator(): ?User. For the few subclasses without
        // that accessor (File / Calendars / LinkItems / Assessments —
        // partial-column entities) we return null — they are not
        // permission-relevant linked-item targets anyway.
        if (!method_exists($item, 'getCreator')) {
            return null;
        }
        return $item->getCreator()?->getItemId();
    }

    private function isDeactivated(object $item): bool
    {
        $activationDate = $item->getActivationDate();
        if ($activationDate === null) {
            return false;
        }
        return $activationDate > new DateTimeImmutable();
    }

    private function isContextDeleted(?int $contextId, ?Room $preloadedContext): bool
    {
        if ($contextId === null) {
            return true; // no resolvable context → treat as deleted (parity with ItemViewChecker)
        }
        if ($preloadedContext !== null && $preloadedContext->getItemId() === $contextId) {
            return $preloadedContext->getDeletionDate() !== null;
        }
        $room = $this->roomRepository->find($contextId);
        return $room === null || $room->getDeletionDate() !== null;
    }
}
