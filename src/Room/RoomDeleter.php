<?php

namespace App\Room;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Owns end-to-end deletion of exactly one room type (project, community,
 * group, private, user). Parallel to {@see \App\Rubric\RubricDeleter},
 * but for container entities rather than their content.
 */
#[AutoconfigureTag('app.room.deleter')]
interface RoomDeleter
{
    /**
     * The room type this deleter is responsible for.
     */
    public function roomType(): RoomType;

    /**
     * Soft-deletes the room and, depending on options, cascades into
     * sub-rooms. Idempotent.
     *
     * @param int                 $roomId    the id of the room being deleted
     * @param int                 $deleterId the id of the user performing the deletion
     * @param RoomDeletionOptions $opts      controls silence, cascade, and reason
     */
    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void;

    /**
     * Physically removes a previously soft-deleted room and its rubric
     * content. Invoked by {@see RoomHardDeleter} past the grace period.
     * MUST NOT dispatch lifecycle events.
     */
    public function hardDeleteRoom(int $roomId): void;
}
