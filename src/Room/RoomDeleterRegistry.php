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
 * Dispatches a room-delete call to the {@see RoomDeleter} registered for
 * the room's type. Used by generic call paths where the room type is only
 * known at runtime. Unknown types throw.
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
     * Returns the deleter for the given type. Throws if none is registered.
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
     * Convenience entry for callers that already hold a legacy room item.
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
