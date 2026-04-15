<?php

namespace App\Room;

/**
 * Immutable options controlling a soft-delete of a room.
 *
 * Defaults match the behaviour of a user-triggered UI delete: moderation
 * mails are sent, sub-rooms cascade. Callers like the auto-abandon
 * subscriber or the account-merge flow flip {@see $silent} and/or
 * {@see $cascadeSubRooms} accordingly.
 */
final readonly class RoomDeletionOptions
{
    public function __construct(
        /**
         * Suppresses {@see \App\Event\Workspace\WorkspaceDeletedEvent} dispatch.
         * Use when the user is not the one intentionally deleting the room
         * (cascading sub-room deletion, auto-abandon, account merge).
         */
        public bool $silent = false,

        /**
         * Whether to recurse into sub-rooms (group rooms of a project room,
         * user rooms of a group room). Turning this off is primarily useful
         * for tests and for surgical DB-fix scripts.
         */
        public bool $cascadeSubRooms = true,

        /**
         * Why the deletion is happening. Informational — does not change
         * the structural cascade.
         */
        public RoomDeletionReason $reason = RoomDeletionReason::UserAction,
    ) {}

    public static function forUserAction(): self
    {
        return new self(reason: RoomDeletionReason::UserAction);
    }

    public static function forAutoAbandon(): self
    {
        return new self(silent: true, reason: RoomDeletionReason::AutoAbandon);
    }

    public static function forAccountMerge(): self
    {
        return new self(silent: true, reason: RoomDeletionReason::AccountMerge);
    }

    public static function forAccountDelete(): self
    {
        return new self(silent: true, reason: RoomDeletionReason::AccountDelete);
    }

    public static function forDbFix(): self
    {
        return new self(silent: true, reason: RoomDeletionReason::DbFix);
    }

    /**
     * Returns a copy with {@see $silent} set to true — used internally
     * when a parent room cascades into its sub-rooms so we do not fan out
     * one moderation mail per cascaded room.
     */
    public function asSilent(): self
    {
        if ($this->silent) {
            return $this;
        }

        return new self(
            silent: true,
            cascadeSubRooms: $this->cascadeSubRooms,
            reason: $this->reason,
        );
    }
}
