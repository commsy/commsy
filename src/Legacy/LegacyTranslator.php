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

namespace App\Legacy;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates the legacy display message keys (the former cs_translator .dat keys) via the
 * Symfony translator's "legacy" domain. Legacy classes reach it centrally through
 * cs_environment::getSymfonyContainer()->get(LegacyTranslator::class).
 *
 * The positional arguments map to the %1..%n placeholders kept verbatim in the catalog,
 * mirroring the old getMessage()/getMessageInLang() signatures so call sites stay 1:1.
 */
final readonly class LegacyTranslator
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function translate(string $key, string ...$params): string
    {
        return $this->translator->trans($key, $this->parameters($params), 'legacy');
    }

    public function translateInLang(string $language, string $key, string ...$params): string
    {
        return $this->translator->trans($key, $this->parameters($params), 'legacy', mb_strtolower($language, 'UTF-8'));
    }

    /**
     * @param list<string> $params
     *
     * @return array<string, string>
     */
    private function parameters(array $params): array
    {
        $parameters = [];
        foreach ($params as $index => $value) {
            $parameters['%'.($index + 1)] = $value;
        }

        return $parameters;
    }
}
