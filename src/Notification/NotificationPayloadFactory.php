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

namespace App\Notification;

use cs_dates_item;
use cs_item;
use cs_material_item;

/**
 * Builds the display-only {@see NotificationPayload} snapshot from a freshly
 * saved legacy item, in-request — the async handler must never touch a legacy
 * item. Isolates the per-rubric legacy getters here so the rest of the
 * notifications code stays free of them; mirrors the fields the room/dashboard
 * feed shows (creator, attachments, a date's time/place, a material's author).
 */
class NotificationPayloadFactory
{
    public function fromItem(cs_item $item): NotificationPayload
    {
        // Common, rubric-independent fields. The actor id (the modificator's
        // user id) lets the panel render the actor's avatar via app_user_image.
        $creatorName = $item->getCreatorItem()?->getFullName();
        $actorId = $item->getModificatorItem()?->getItemID();
        $hasAttachments = $item->getFileList()->getCount() > 0;

        if ($item instanceof cs_dates_item) {
            return new NotificationPayload(
                creatorName: $creatorName,
                actorId: $actorId,
                place: $this->clean($item->getPlace()),
                dateStart: $this->clean($item->getDateTime_start()),
                dateEnd: $this->clean($item->getDateTime_end()),
                wholeDay: (bool) $item->isWholeDay(),
                hasAttachments: $hasAttachments,
            );
        }

        if ($item instanceof cs_material_item) {
            return new NotificationPayload(
                creatorName: $creatorName,
                actorId: $actorId,
                materialAuthor: $this->clean($item->getAuthor()),
                publishingDate: $this->clean($item->getPublishingDate()),
                hasAttachments: $hasAttachments,
            );
        }

        return new NotificationPayload(
            creatorName: $creatorName,
            actorId: $actorId,
            hasAttachments: $hasAttachments,
        );
    }

    /**
     * Normalise a legacy string getter to a non-empty value or null, dropping
     * the legacy "zero date" sentinel so the panel never renders 0000-00-00.
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }
}
