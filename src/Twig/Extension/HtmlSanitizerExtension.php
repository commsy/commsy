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
declare(strict_types=1);

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Cleans HTML a member wrote before it is shown as HTML; for texts stored
 * before they were cleaned on saving.
 */
final class HtmlSanitizerExtension extends AbstractExtension
{
    public function __construct(private readonly LegacyEnvironment $legacyEnvironment)
    {
    }

    public function getFilters(): array
    {
        return [new TwigFilter('sanitizeHtml', $this->sanitize(...))];
    }

    public function sanitize(?string $html): string
    {
        return (string) $this->legacyEnvironment->getEnvironment()->getTextConverter()->sanitizeFullHTML((string) $html);
    }
}
