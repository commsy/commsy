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

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves the {@see MailPlaceholder::RoomTypeName} token to the room type's noun, so a
 * mail-text author writes one text and never picks a room type.
 *
 * Mirrors the legacy grammar engine's `%_<TYPE>_SUB_NOMS_BIG` resolution: project and
 * community nouns may be renamed per context (the context item's RUBRIC_TRANSLATION_ARRAY,
 * exposed via getRubricTranslationArray()), otherwise the standard noun is used. The legacy
 * group-room texts hard-coded "Gruppenraum" (no rubric entry exists for it), which the
 * standard noun reproduces.
 *
 * The standard nouns are not inlined here: each room type maps to a "mail" domain key
 * (translations/mail+intl-icu.{de,en}.xlf). Only the nominative singular is provided: the
 * three standard nouns are all masculine, so the surrounding article ("den"/"dem") is
 * identical across them and stays literal text in the author's hands.
 */
final readonly class RoomTypeNameResolver
{
    /** @var array<string, string> roomType => "mail" domain translation key of the standard nominative noun */
    private const KEYS = [
        'project' => 'mail.room_type.project',
        'community' => 'mail.room_type.community',
        'grouproom' => 'mail.room_type.grouproom',
    ];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @param array<string, mixed> $rubricTranslationArray the context item's getRubricTranslationArray()
     *                                                      (TYPE => [LANG => [case => noun]]); empty for the standard nouns
     */
    public function nominative(string $roomType, string $locale, array $rubricTranslationArray = []): string
    {
        $type = strtolower($roomType);
        $lang = strtolower($locale);

        $override = $rubricTranslationArray[strtoupper($type)][strtoupper($lang)]['NOMS'] ?? null;
        if (is_string($override) && '' !== $override) {
            return $override;
        }

        $key = self::KEYS[$type] ?? null;

        return null === $key ? '' : $this->translator->trans($key, [], 'mail', $lang);
    }
}
