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
 * This first cut covers the nine texts the room moderation editor exposes (the portal
 * editor additionally exposes inactivity / password / room-lifecycle texts -- those follow
 * the exact same structure and get added once their positional params are verified against
 * their senders).
 *
 * The placeholder contracts below are taken verbatim from the (already migrated) sender
 * App\Utils\AccountMail: the salutation carries the recipient name, every status/account
 * body carries the user id + room title, and the goodbye carries the moderator + room title.
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

        $this->register(new MailTextDefinition('mail.salutation', 'MAIL_BODY_HELLO', 'Salutation', [$name], roomTypeAware: false));
        $this->register(new MailTextDefinition('mail.goodbye', 'MAIL_BODY_CIAO', 'Goodbye', [$moderator, $room], roomTypeAware: true));

        // status / account bodies: all carry (%1 = user id, %2 = room title) and vary by room type
        $this->register(new MailTextDefinition('mail.body.account_delete', 'MAIL_BODY_USER_ACCOUNT_DELETE', 'Delete user id(s)', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.account_lock', 'MAIL_BODY_USER_ACCOUNT_LOCK', 'Lock user id(s)', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_user', 'MAIL_BODY_USER_STATUS_USER', 'Activate user id(s)', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_moderator', 'MAIL_BODY_USER_STATUS_MODERATOR', 'Status moderator', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.status_read_only', 'MAIL_BODY_USER_STATUS_USER_READ_ONLY', 'Change status: read only user', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.make_contact_person', 'MAIL_BODY_USER_MAKE_CONTACT_PERSON', 'Make contact', [$account, $room], roomTypeAware: true));
        $this->register(new MailTextDefinition('mail.body.unmake_contact_person', 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', 'Remove contact', [$account, $room], roomTypeAware: true));
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
