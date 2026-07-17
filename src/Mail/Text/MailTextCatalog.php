<?php

declare(strict_types=1);

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

namespace App\Mail\Text;

/**
 * Single source of truth for the customizable mail texts: which texts a portal/room admin
 * may override, their modern translation key, the legacy MsgID their override is stored
 * under, and the placeholders each offers.
 *
 * Covers the fifteen mail texts that are actually sent and have a modern default: the nine
 * account/status texts (room moderation editor + AccountMail) and the six deprovisioning /
 * inactivity texts (the AccountActivity and RoomActivity messages). The legacy editor also
 * offered password and room-lifecycle texts, but nothing sends those any more (their only
 * code reference is the cs_translator suffix table), so they are intentionally left out.
 *
 * The placeholder contracts are taken verbatim from the (already migrated) senders.
 */
final class MailTextCatalog
{
    /** @var array<string, MailTextDefinition> keyed by legacy MsgID */
    private array $byLegacyId = [];

    /** @var array<string, MailTextDefinition> keyed by modern translation key */
    private array $byKey = [];

    public function __construct()
    {
        $name = MailPlaceholder::RecipientName;
        $account = MailPlaceholder::AccountId;
        $room = MailPlaceholder::RoomTitle;
        $moderator = MailPlaceholder::ModeratorName;

        $this->register(new MailTextDefinition('mail.salutation', 'MAIL_BODY_HELLO', [$name], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.goodbye', 'MAIL_BODY_CIAO', [$moderator, $room], roomTypeAware: true));

        // status / account bodies: all carry (%1 = user id, %2 = room title) and vary by room type
        $this->register(new MailTextDefinition('mail.body.account_delete', 'MAIL_BODY_USER_ACCOUNT_DELETE', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.account_lock', 'MAIL_BODY_USER_ACCOUNT_LOCK', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_user', 'MAIL_BODY_USER_STATUS_USER', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_moderator', 'MAIL_BODY_USER_STATUS_MODERATOR', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_read_only', 'MAIL_BODY_USER_STATUS_USER_READ_ONLY', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.make_contact_person', 'MAIL_BODY_USER_MAKE_CONTACT_PERSON', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.unmake_contact_person', 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', [$account, $room], roomTypeAware: true));

        // deprovisioning / inactivity notifications: single texts (no room-type variants)
        $auth = MailPlaceholder::AuthSource;
        $days = MailPlaceholder::Days;
        $daysInactive = MailPlaceholder::DaysInactive;
        $portal = MailPlaceholder::PortalTitle;
        $link = MailPlaceholder::Link;

        $this->register(new MailTextDefinition('mail.inactivity_lock_next', 'EMAIL_INACTIVITY_LOCK_NEXT_BODY', [$name, $auth, $days, $link, $portal], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.inactivity_lock_now', 'EMAIL_INACTIVITY_LOCK_NOW_BODY', [$name, $auth, $link, $portal], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.inactivity_delete_next', 'EMAIL_INACTIVITY_DELETE_NEXT_BODY', [$name, $auth, $days, $link, $portal], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.inactivity_delete_now', 'EMAIL_INACTIVITY_DELETE_NOW_BODY', [$name, $auth, $link, $portal], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.inactivity_room_lock_upcoming', 'EMAIL_INACTIVITY_ROOM_LOCK_UPCOMING_BODY', [$room, $daysInactive, $days], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.inactivity_room_delete_upcoming', 'EMAIL_INACTIVITY_ROOM_DELETE_UPCOMING_BODY', [$room, $daysInactive, $days], roomTypeAware: false));
    }

    /**
     * All customizable mail texts.
     *
     * @return list<MailTextDefinition>
     */
    public function all(): array
    {
        return array_values($this->byKey);
    }

    public function byLegacyId(string $legacyMessageId): ?MailTextDefinition
    {
        return $this->byLegacyId[$legacyMessageId] ?? null;
    }

    public function byKey(string $key): ?MailTextDefinition
    {
        return $this->byKey[$key] ?? null;
    }

    private function register(MailTextDefinition $definition): void
    {
        $this->byLegacyId[$definition->legacyMessageId] = $definition;
        $this->byKey[$definition->key] = $definition;
    }
}
