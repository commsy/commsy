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

namespace App\Mail;

use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\String\u;

/**
 * Resolves a mail body/subject text the way the legacy cs_translator::getEmailMessage()
 * did, but backed by the Symfony translator.
 *
 * Behaviour mirror of the legacy path:
 *  - a portal admin override (portal->getEmailTextArray(), keyed by the legacy MsgID
 *    and language) wins over the translated default;
 *  - the default lives in the "mail" (intl-icu) domain; room-type dependent texts use
 *    an ICU `{roomType, select, ...}` instead of the legacy _PR/_GR/_GP/_PO key suffixes;
 *  - positional params map to legacy %1..%6 in overrides and to ICU {p1..p6} in defaults;
 *  - getEmailMessage() applied a CKEditor paragraph normalisation to the final text;
 *    resolve() reproduces it, resolveRaw() skips it (mirroring getEmailMessageInLang()).
 */
final readonly class MailTextResolver
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * Resolve with the CKEditor paragraph normalisation (legacy getEmailMessage()).
     *
     * @param string                              $key         translation key in the "mail" domain
     * @param string                              $legacyMsgId MsgID the portal override is stored under
     * @param string                              $roomType    project|community|grouproom|other (ICU select, matches the mail domain's room_type convention)
     * @param list<string>                        $params      positional params (%1.. / {p1..})
     * @param array<string, array<string, string>> $overrides   portal->getEmailTextArray()
     */
    public function resolve(string $key, string $legacyMsgId, string $roomType, string $locale, array $params = [], array $overrides = []): string
    {
        return $this->normalizeParagraphs($this->resolveRaw($key, $legacyMsgId, $roomType, $locale, $params, $overrides));
    }

    /**
     * Resolve without paragraph normalisation (legacy getEmailMessageInLang()).
     *
     * @param list<string>                        $params
     * @param array<string, array<string, string>> $overrides
     */
    public function resolveRaw(string $key, string $legacyMsgId, string $roomType, string $locale, array $params = [], array $overrides = []): string
    {
        $override = $overrides[$legacyMsgId][mb_strtoupper($locale, 'UTF-8')]
            ?? $overrides[$legacyMsgId][mb_strtolower($locale, 'UTF-8')]
            ?? null;

        if (!empty($override)) {
            return $this->replaceLegacyParams($override, $params);
        }

        $arguments = ['room_type' => $roomType];
        foreach ($params as $index => $value) {
            $arguments['p'.($index + 1)] = $value;
        }

        return $this->translator->trans($key, $arguments, 'mail', $locale);
    }

    /**
     * @param list<string> $params
     */
    private function replaceLegacyParams(string $text, array $params): string
    {
        foreach ($params as $index => $value) {
            $text = str_replace('%'.($index + 1), (string) $value, $text);
        }

        return $text;
    }

    private function normalizeParagraphs(string $text): string
    {
        return u($text)
            ->replace("\n", '<br/>')
            ->replace('<p>', '<br/><br/>')
            ->replace('</p>', '')
            ->trimPrefix('<br/><br/>')
            ->trimSuffix('<br/><br/>')
            ->toString();
    }
}
