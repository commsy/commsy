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
 * The named placeholders that may appear in a customizable mail text.
 *
 * The backing value is the ICU named argument used both in the stored override and at
 * render time (e.g. "{recipientName}"). It replaces the legacy positional "%1".."%6":
 * a portal/room admin inserts a placeholder by meaning, never by position, and never has
 * to care which room type it resolves to -- {@see self::RoomTypeName} is filled by the
 * backend with the declined noun of the actual room.
 */
enum MailPlaceholder: string
{
    /** Full display name of the recipient ("Anna Beispiel"). */
    case RecipientName = 'recipientName';

    /** Login/user id of the affected account ("abeispiel"). */
    case AccountId = 'accountId';

    /** Title of the room / context the mail is about. */
    case RoomTitle = 'roomTitle';

    /** Declined noun of the actual room's type ("Projektraum" / "Gemeinschaftsraum" / ...), backend-resolved. */
    case RoomTypeName = 'roomTypeName';

    /** Name of the moderator / contact person sending or signing the mail. */
    case ModeratorName = 'moderatorName';

    /** Title of the account's authentication source. */
    case AuthSource = 'authSource';

    /** Number of days until the action (lock/deletion) takes effect. */
    case Days = 'days';

    /** Number of days the account/room has already been inactive. */
    case DaysInactive = 'daysInactive';

    /** Title of the portal. */
    case PortalTitle = 'portalTitle';

    /** Absolute link offered in the mail (e.g. portal entry). */
    case Link = 'link';

    /** The ICU token as it appears in the stored and rendered string. */
    public function token(): string
    {
        return '{'.$this->value.'}';
    }

    /** Admin-facing label for the editor's insert menu. */
    public function label(string $locale): string
    {
        return match ($this) {
            self::RecipientName => 'de' === $locale ? 'Empfänger:in' : 'Recipient',
            self::AccountId => 'de' === $locale ? 'Kennung' : 'User ID',
            self::RoomTitle => 'de' === $locale ? 'Raumname' : 'Workspace name',
            self::RoomTypeName => 'de' === $locale ? 'Raumart' : 'Workspace type',
            self::ModeratorName => 'de' === $locale ? 'Moderation' : 'Moderator',
            self::AuthSource => 'de' === $locale ? 'Anmeldequelle' : 'Login source',
            self::Days => 'de' === $locale ? 'Anzahl Tage' : 'Days',
            self::DaysInactive => 'de' === $locale ? 'Tage inaktiv' : 'Days inactive',
            self::PortalTitle => 'Portal',
            self::Link => 'Link',
        };
    }

    /** Example value used for the editor's live preview. */
    public function sample(string $locale): string
    {
        return match ($this) {
            self::RecipientName => 'de' === $locale ? 'Anna Beispiel' : 'Anna Example',
            self::AccountId => 'abeispiel',
            self::RoomTitle => 'de' === $locale ? 'Mein Kurs' : 'My Course',
            self::RoomTypeName => 'de' === $locale ? 'Projektraum' : 'project workspace',
            self::ModeratorName => 'M. Mustermann',
            self::AuthSource => 'CommSy',
            self::Days => '14',
            self::DaysInactive => '90',
            self::PortalTitle => 'de' === $locale ? 'CommSy Uni' : 'CommSy University',
            self::Link => 'https://commsy.example/portal/1',
        };
    }
}
