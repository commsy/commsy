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
 * Contract of a single customizable mail text: its modern translation key, the legacy
 * MsgID its override is still stored under, and which placeholders it offers.
 *
 * Two placeholder views are kept deliberately separate:
 *  - {@see $positionalParams} is the exact legacy "%1".."%6" order. It drives both the
 *    migration of existing stored overrides (index 0 = %1) and the argument array the
 *    renderer builds at send time.
 *  - {@see availablePlaceholders()} is what the admin may insert in the editor. For a
 *    room-type aware text it additionally offers {@see MailPlaceholder::RoomTypeName},
 *    which legacy used to bake into the text variants (_PR/_GR/_GP) but is now a token.
 */
final readonly class MailTextDefinition
{
    /**
     * @param string                $key             modern translation key in the "mail" domain (e.g. "mail.body.account_delete")
     * @param string                $legacyMessageId legacy MsgID the override is stored under (e.g. "MAIL_BODY_USER_ACCOUNT_DELETE")
     * @param string                $label           admin-facing name of the mail text (localized via the form translation domain)
     * @param list<MailPlaceholder> $positionalParams legacy positional params, index 0 = %1
     * @param bool                  $roomTypeAware   whether the default text varies by room type (ICU room_type select)
     */
    public function __construct(
        public string $key,
        public string $legacyMessageId,
        public string $label,
        public array $positionalParams,
        public bool $roomTypeAware,
    ) {
    }

    /**
     * Placeholders the admin may insert in the editor: the positional params plus the
     * room-type noun token for room-type aware texts.
     *
     * @return list<MailPlaceholder>
     */
    public function availablePlaceholders(): array
    {
        $placeholders = $this->positionalParams;

        if ($this->roomTypeAware && !in_array(MailPlaceholder::RoomTypeName, $placeholders, true)) {
            $placeholders[] = MailPlaceholder::RoomTypeName;
        }

        return $placeholders;
    }
}
