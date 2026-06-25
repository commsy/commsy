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
 * Renders a customizable mail text from its modern definition.
 *
 * Successor to App\Mail\MailTextResolver. Two sources, as before:
 *  - a portal/room override (stored, in the new named-token format) wins;
 *  - otherwise the default from the "mail" (intl-icu) domain.
 *
 * The crucial difference is the placeholder format. The override is rendered with a plain
 * named-token substitution ("{recipientName}" -> value), deliberately NOT through ICU:
 * admin free text contains apostrophes and stray braces that would break an ICU parse, and
 * a flat override never needs select/plural. ICU stays where we control the text -- the
 * system default in the xlf (which may use {room_type, select, ...}). The room type itself
 * is never the author's concern: it is supplied as the {roomTypeName} value.
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
     * Render with the CKEditor paragraph normalisation (mirrors the legacy getEmailMessage()).
     *
     * @param list<string>                         $values       positional values matching the definition's positionalParams
     * @param array<string, array<string, string>> $overrides    portal/room MAIL_TEXT_ARRAY (named-token format)
     * @param array<string, mixed>                 $rubricConfig context getRubricTranslationArray() for per-context room-type renames
     */
    public function render(string $legacyMessageId, string $roomType, string $locale, array $values = [], array $overrides = [], array $rubricConfig = []): string
    {
        return $this->normalizeParagraphs($this->renderRaw($legacyMessageId, $roomType, $locale, $values, $overrides, $rubricConfig));
    }

    /**
     * Render without paragraph normalisation (mirrors the legacy getEmailMessageInLang()).
     *
     * @param list<string>                         $values
     * @param array<string, array<string, string>> $overrides
     * @param array<string, mixed>                 $rubricConfig
     */
    public function renderRaw(string $legacyMessageId, string $roomType, string $locale, array $values = [], array $overrides = [], array $rubricConfig = []): string
    {
        $definition = $this->catalog->byLegacyId($legacyMessageId);

        $override = $overrides[$legacyMessageId][mb_strtoupper($locale, 'UTF-8')]
            ?? $overrides[$legacyMessageId][mb_strtolower($locale, 'UTF-8')]
            ?? null;

        if (is_string($override) && '' !== $override) {
            return $this->substituteTokens($override, $this->namedArguments($definition, $roomType, $locale, $values, $rubricConfig));
        }

        if (null === $definition) {
            return '';
        }

        $arguments = ['room_type' => $roomType];
        foreach ($values as $index => $value) {
            $arguments['p'.($index + 1)] = $value;
        }

        return $this->translator->trans($definition->key, $arguments, 'mail', $locale);
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
        $text = $this->renderRaw($legacyMessageId, 'project', $locale, $tokens, []);

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
    private function namedArguments(?MailTextDefinition $definition, string $roomType, string $locale, array $values, array $rubricConfig): array
    {
        $arguments = [];

        if (null !== $definition) {
            foreach ($definition->positionalParams as $index => $placeholder) {
                $arguments[$placeholder->value] = (string) ($values[$index] ?? '');
            }
        }

        $arguments[MailPlaceholder::RoomTypeName->value] = $this->roomTypeNameResolver->nominative($roomType, $locale, $rubricConfig);

        return $arguments;
    }

    /**
     * @param array<string, string> $namedArguments
     */
    private function substituteTokens(string $text, array $namedArguments): string
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
