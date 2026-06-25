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

use function Symfony\Component\String\u;

/**
 * Renders a mail text from an override or the translated default. Single engine, drop-in for
 * the former App\Mail\MailTextResolver (same call signature).
 *
 * Override path: a plain named-token substitution, deliberately NOT ICU (admin free text has
 * apostrophes and stray braces that would break an ICU parse, and a flat override never needs
 * select/plural). The room type is never the author's concern -- it is supplied as the
 * {roomTypeName} value. Stored overrides use named tokens exclusively (legacy %1..%6 overrides
 * were one-off migrated by Version20260625120000).
 *
 * Default path: the mail-domain key is translated unchanged (room_type select + p1..pN), so it
 * is byte-identical to the legacy resolver and also covers keys that are not customizable.
 */
final readonly class MailTextRenderer
{
    public function __construct(
        private TranslatorInterface $translator,
        private MailTextCatalog $catalog,
        private RoomTypeNameResolver $roomTypeNameResolver,
    ) {
    }

    /**
     * @param list<string>                         $values
     * @param array<string, array<string, string>> $overrides
     * @param array<string, mixed>                 $rubricConfig
     */
    public function render(string $key, string $legacyMessageId, string $roomType, string $locale, array $values = [], array $overrides = [], array $rubricConfig = []): string
    {
        return $this->normalizeParagraphs($this->renderRaw($key, $legacyMessageId, $roomType, $locale, $values, $overrides, $rubricConfig));
    }

    /**
     * @param list<string>                         $values
     * @param array<string, array<string, string>> $overrides
     * @param array<string, mixed>                 $rubricConfig
     */
    public function renderRaw(string $key, string $legacyMessageId, string $roomType, string $locale, array $values = [], array $overrides = [], array $rubricConfig = []): string
    {
        $override = $overrides[$legacyMessageId][mb_strtoupper($locale, 'UTF-8')]
            ?? $overrides[$legacyMessageId][mb_strtolower($locale, 'UTF-8')]
            ?? null;

        if (is_string($override) && '' !== $override) {
            return $this->substitute($override, $this->namedArguments($legacyMessageId, $roomType, $locale, $values, $rubricConfig));
        }

        $arguments = ['room_type' => $roomType];
        foreach ($values as $index => $value) {
            $arguments['p'.($index + 1)] = $value;
        }

        return $this->translator->trans($key, $arguments, 'mail', $locale);
    }

    /**
     * The editable starting template for a mail text, in the named-token format: the system
     * default with each value shown as its placeholder token and the room-type noun replaced
     * by {roomTypeName}. Derived from the single xlf source, so the editor always starts from
     * the real default text -- not a hand-maintained copy.
     */
    public function templateFor(string $legacyMessageId, string $locale): string
    {
        $definition = $this->catalog->byLegacyId($legacyMessageId);
        if (null === $definition) {
            return '';
        }

        $tokens = array_map(static fn (MailPlaceholder $p): string => $p->token(), $definition->positionalParams);
        $text = $this->renderRaw($definition->key, $legacyMessageId, 'project', $locale, $tokens, []);

        if ($definition->roomTypeAware) {
            $projectNoun = $this->roomTypeNameResolver->nominative('project', $locale);
            if ('' !== $projectNoun) {
                $text = str_replace($projectNoun, MailPlaceholder::RoomTypeName->token(), $text);
            }
        }

        return $text;
    }

    /**
     * Map the positional values onto their named placeholders and add the resolved room-type noun.
     *
     * @param list<string>         $values
     * @param array<string, mixed> $rubricConfig
     *
     * @return array<string, string>
     */
    private function namedArguments(string $legacyMessageId, string $roomType, string $locale, array $values, array $rubricConfig): array
    {
        $arguments = [];

        $definition = $this->catalog->byLegacyId($legacyMessageId);
        if (null !== $definition) {
            foreach ($definition->positionalParams as $index => $placeholder) {
                $arguments[$placeholder->value] = (string) ($values[$index] ?? '');
            }
        }

        $arguments[MailPlaceholder::RoomTypeName->value] = $this->roomTypeNameResolver->nominative($roomType, $locale, $rubricConfig);

        return $arguments;
    }

    /**
     * Substitute the named tokens ("{recipientName}" -> value).
     *
     * @param array<string, string> $namedArguments
     */
    private function substitute(string $text, array $namedArguments): string
    {
        $search = [];
        $replace = [];
        foreach ($namedArguments as $name => $value) {
            $search[] = '{'.$name.'}';
            $replace[] = $value;
        }

        return str_replace($search, $replace, $text);
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
