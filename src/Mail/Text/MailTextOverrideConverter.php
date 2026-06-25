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
 * Converts a stored mail-text override array (MAIL_TEXT_ARRAY: MsgID => lang => text)
 * between the legacy positional %1..%6 format and the named-token format, using the
 * catalog's positional mapping. Used by the one-off migration that canonicalises existing
 * overrides; idempotent, since text already in the target format has nothing to replace.
 */
final class MailTextOverrideConverter
{
    /**
     * @param array<string, array<string, string>> $mailTextArray
     *
     * @return array<string, array<string, string>>
     */
    public static function toNamed(MailTextCatalog $catalog, array $mailTextArray): array
    {
        return self::convert($catalog, $mailTextArray, named: true);
    }

    /**
     * @param array<string, array<string, string>> $mailTextArray
     *
     * @return array<string, array<string, string>>
     */
    public static function toLegacy(MailTextCatalog $catalog, array $mailTextArray): array
    {
        return self::convert($catalog, $mailTextArray, named: false);
    }

    /**
     * @param array<string, array<string, string>> $mailTextArray
     *
     * @return array<string, array<string, string>>
     */
    private static function convert(MailTextCatalog $catalog, array $mailTextArray, bool $named): array
    {
        foreach ($mailTextArray as $messageId => $byLanguage) {
            $definition = $catalog->byLegacyId((string) $messageId);
            if (null === $definition || !is_array($byLanguage)) {
                continue;
            }

            // highest index first so "%1" never matches inside a hypothetical "%10"
            $pairs = [];
            foreach ($definition->positionalParams as $index => $placeholder) {
                $pairs[] = ['%'.($index + 1), $placeholder->token()];
            }
            $pairs = array_reverse($pairs);

            foreach ($byLanguage as $language => $text) {
                if (!is_string($text)) {
                    continue;
                }
                foreach ($pairs as [$legacy, $token]) {
                    $text = $named ? str_replace($legacy, $token, $text) : str_replace($token, $legacy, $text);
                }
                $mailTextArray[$messageId][$language] = $text;
            }
        }

        return $mailTextArray;
    }
}
