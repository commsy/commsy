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

namespace App\Room;

use cs_room_item;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Central dispatcher that routes a room-delete call to the concrete
 * {@see RoomDeleter} implementation registered for the room's type.
 *
 * Call sites that already know the exact type (e.g.
 * {@see \App\Utils\UserroomService} always dealing with user rooms)
 * keep depending on the concrete deleter directly. The registry exists
 * for the few generic call paths where the room type is only known at
 * runtime — the auto-abandon subscriber, the profile-delete controller,
 * the cancellable-lock-and-delete controller — so these can express
 * "soft-delete whatever room this is" without a switch statement per
 * caller.
 *
 * All registered `RoomDeleter`s are indexed by their {@see RoomDeleter::roomType()}
 * at construction. Looking up an unknown type throws — the registry is
 * meant to catch configuration drift early, not silently swallow a room
 * type the pipeline does not own (portals, `myroom`, `server`).
 */
readonly class RoomDeleterRegistry
{
    /** @var array<string, RoomDeleter> */
    private array $deletersByType;

    /** @param iterable<RoomDeleter> $deleters */
    public function __construct(
        #[AutowireIterator('app.room.deleter')]
        iterable $deleters,
    ) {
        $map = [];
        foreach ($deleters as $deleter) {
            $map[$deleter->roomType()->value] = $deleter;
        }
        $this->deletersByType = $map;
    }

    /**
     * Returns the deleter responsible for {@see $type}. Throws when no
     * deleter is registered for the type so that misrouted calls fail
     * loudly instead of leaving a room undeleted.
     */
    public function forType(RoomType $type): RoomDeleter
    {
        if (!isset($this->deletersByType[$type->value])) {
            throw new LogicException(sprintf(
                'No RoomDeleter registered for room type "%s". This indicates a missing tagged service or a type that is intentionally not managed by the deletion pipeline (portal, myroom, server).',
                $type->value
            ));
        }

        return $this->deletersByType[$type->value];
    }

    /**
     * Convenience entry for callers that already hold a legacy room
     * item — reads the type off the item and dispatches in one step.
     */
    public function softDeleteLegacyRoom(
        cs_room_item $room,
        int $deleterId,
        RoomDeletionOptions $opts,
    ): void {
        $type = RoomType::tryFromLegacyString($room->getType());
        if ($type === null) {
            throw new LogicException(sprintf(
                'Cannot dispatch room delete: legacy room item with id %d has unsupported type "%s".',
                (int) $room->getItemID(),
                $room->getType()
            ));
        }

        $this->forType($type)->softDeleteRoom((int) $room->getItemID(), $deleterId, $opts);
    }
}
