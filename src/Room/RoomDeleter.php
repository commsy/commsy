<?php

namespace App\Room;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A RoomDeleter owns the end-to-end deletion of exactly one room type
 * (project, community, group room, private room, user room).
 *
 * Parallel to {@see \App\Rubric\RubricDeleter}, but for the container
 * entities rather than the content items inside them. Cleanup of the
 * rubric items themselves is delegated to {@see RoomContentDeleter},
 * which in turn uses the registered `RubricDeleter`s.
 *
 * The interface distinguishes soft- and hard-delete explicitly rather
 * than smuggling the mode through a flag: soft-delete runs the full
 * event/mail/ES pipeline, hard-delete is a bulk purge of already
 * soft-deleted rows invoked by {@see RoomHardDeleter}.
 */
#[AutoconfigureTag('app.room.deleter')]
interface RoomDeleter
{
    /**
     * The room type this deleter is responsible for. Dispatch sites
     * ({@see \App\Controller\CancellableLockAndDeleteController} and
     * friends) already know the type — either from the legacy room item
     * (`$roomItem->getType()`) or from the `items.type` column they
     * read anyway — and pick the matching deleter by comparing against
     * this key. No per-deleter `supports()` probe is needed.
     */
    public function roomType(): RoomType;

    /**
     * Soft-deletes the room and, depending on {@see RoomDeletionOptions},
     * cascades into sub-rooms. Dispatches the appropriate lifecycle
     * events unless silenced. Idempotent: calling twice on an already
     * soft-deleted room is a no-op.
     *
     * @param int                 $roomId    the id of the room being deleted
     * @param int                 $deleterId the id of the user performing the deletion
     *                                       (for the `deleter_id` stamp on all cascaded rows)
     * @param RoomDeletionOptions $opts      controls silence, cascade, and reason
     */
    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void;

    /**
     * Physically removes a previously soft-deleted room and all its
     * rubric content (bulk DELETEs by context_id rather than item-by-item).
     *
     * Invoked by {@see RoomHardDeleter} on rows whose `deletion_date`
     * is older than the configured grace period. Implementations MUST
     * assume the content is already soft-deleted and MUST NOT dispatch
     * lifecycle events — ES / mails have already fired during soft-delete.
     */
    public function hardDeleteRoom(int $roomId): void;
}
