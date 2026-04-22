<?php

namespace App\Room;

/**
 * Immutable options controlling a room soft-delete. Defaults match a
 * user-triggered UI delete (mails sent, sub-rooms cascade).
 */
final readonly class RoomDeletionOptions
{
    public function __construct(
        /** Suppresses WorkspaceDeletedEvent dispatch. */
        public bool $silent = false,
        /** Recurse into sub-rooms. */
        public bool $cascadeSubRooms = true,
        /** Informational — does not change the structural cascade. */
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
     * Returns a silent copy — used by parent-room cascades to avoid one
     * moderation mail per cascaded sub-room.
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
