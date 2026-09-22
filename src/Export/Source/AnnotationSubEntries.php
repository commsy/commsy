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

namespace App\Export\Source;

use App\Export\ExportableEntry;
use App\Repository\AnnotationsRepository;

/**
 * Comments hang under whatever entry they were written on, whichever rubric it
 * belongs to. Every source hands its entries through here rather than each one
 * asking for comments in its own words.
 */
final readonly class AnnotationSubEntries
{
    public function __construct(
        private AnnotationsRepository $annotations,
    ) {
    }

    /**
     * @return array<string, list<ExportableEntry>>
     */
    public function of(ExportableEntry $entry): array
    {
        return ['annotations' => $this->annotations->findForEntry($entry->getItemId())];
    }
}
