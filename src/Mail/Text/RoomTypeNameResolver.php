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
 * Resolves the {@see MailPlaceholder::RoomTypeName} token to the room type's noun, so a
 * mail-text author writes one text and never picks a room type.
 *
 * Mirrors the legacy grammar engine's `%_<TYPE>_SUB_NOMS_BIG` resolution: project and
 * community nouns may be renamed per context (the context item's RUBRIC_TRANSLATION_ARRAY,
 * exposed via getRubricTranslationArray()), otherwise the standard noun is used. The legacy
 * group-room texts hard-coded "Gruppenraum" (no rubric entry exists for it), which the
 * default below reproduces.
 *
 * Only the nominative singular is provided: the three standard nouns are all masculine, so
 * the surrounding article ("den"/"dem") is identical across them and stays literal text in
 * the author's hands. German nouns are capitalised; the English base form is lower case and
 * meant for mid-sentence use.
 */
final class RoomTypeNameResolver
{
    /** @var array<string, array<string, string>> roomType => locale => standard nominative noun */
    private const DEFAULTS = [
        'project' => ['de' => 'Projektraum', 'en' => 'project workspace'],
        'community' => ['de' => 'Gemeinschaftsraum', 'en' => 'community workspace'],
        'grouproom' => ['de' => 'Gruppenraum', 'en' => 'group workspace'],
    ];

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

        return self::DEFAULTS[$type][$lang] ?? '';
    }
}
