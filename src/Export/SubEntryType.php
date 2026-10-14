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

namespace App\Export;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What a rubric keeps beneath its entries under one name: the class of those
 * sub-entries and what they are, for the schema of the output.
 */
#[Exclude]
final readonly class SubEntryType
{
    /**
     * @param class-string<ExportableEntry> $class
     */
    public function __construct(
        public string $class,
        public string $description,
    ) {
    }
}
