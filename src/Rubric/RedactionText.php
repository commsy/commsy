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

namespace App\Rubric;

use App\Repository\RoomRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Supplies the text that replaces a redacted entry's content.
 *
 * The text is written into the columns, not rendered from a marker: a
 * person who asked for their content to go must not have it sitting in the
 * database behind a display-time substitution.
 *
 * One wording for every reason. Naming the cause — "removed at this
 * person's request, along with all their other entries in this room" — is
 * itself information about that person, so the placeholder states what
 * happened to the entry and nothing about who caused it.
 *
 * Resolved in the room's own language, because there is no reader at
 * redaction time: the account deletion runs in a background worker. A room
 * set to follow the reader's language ("user") falls back to the
 * application default.
 */
class RedactionText
{
    public const TRANSLATION_DOMAIN = 'item';
    public const TITLE_KEY = 'item.redacted.title';
    public const DESCRIPTION_KEY = 'item.redacted.description';

    private const FALLBACK_LANGUAGE = 'de';
    private const SUPPORTED_LANGUAGES = ['de', 'en'];

    /** @var array<int, string> */
    private array $languageCache = [];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RoomRepository $roomRepository,
    ) {}

    public function title(int $contextId): string
    {
        return $this->translator->trans(
            self::TITLE_KEY,
            [],
            self::TRANSLATION_DOMAIN,
            $this->language($contextId)
        );
    }

    public function description(int $contextId): string
    {
        return $this->translator->trans(
            self::DESCRIPTION_KEY,
            [],
            self::TRANSLATION_DOMAIN,
            $this->language($contextId)
        );
    }

    private function language(int $contextId): string
    {
        if (isset($this->languageCache[$contextId])) {
            return $this->languageCache[$contextId];
        }

        $language = strtolower((string) $this->roomRepository
            ->findOneBy(['itemId' => $contextId])
            ?->getLanguage());

        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $language = self::FALLBACK_LANGUAGE;
        }

        return $this->languageCache[$contextId] = $language;
    }
}
