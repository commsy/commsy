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

    /**
     * Translation key (portal domain) for the admin-facing label shown in the editor's insert
     * menu. The text itself lives in translations/portal.{de,en}.xlf -- never inline here.
     */
    public function labelKey(): string
    {
        return 'mail_text.placeholder.'.$this->value;
    }

    /**
     * Translation key (portal domain) for the example value used in the editor's live preview.
     * The text itself lives in translations/portal.{de,en}.xlf -- never inline here.
     */
    public function sampleKey(): string
    {
        return 'mail_text.sample.'.$this->value;
    }
}
