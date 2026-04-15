<?php

namespace App\Room;

/**
 * Classifies *why* a room is being deleted.
 *
 * Informs downstream concerns (logging, mail templates, silent-vs-loud
 * behaviour) without polluting the {@see RoomDeleter} interface with a
 * growing list of boolean flags.
 */
enum RoomDeletionReason: string
{
    /** Explicit user action via the UI (room admin clicks "delete"). */
    case UserAction = 'user_action';

    /** The room was auto-abandoned after a prolonged period of inactivity. */
    case AutoAbandon = 'auto_abandon';

    /** Triggered as part of an account merge where a redundant room is removed. */
    case AccountMerge = 'account_merge';

    /** Triggered as part of an account deletion (user gone → user-room gone). */
    case AccountDelete = 'account_delete';

    /** Physical removal after the soft-delete grace period (hard delete). */
    case CronHardDelete = 'cron_hard_delete';

    /** One-off database fix scripts (orphaned / untitled group rooms). */
    case DbFix = 'db_fix';
}
