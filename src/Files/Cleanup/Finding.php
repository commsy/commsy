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

namespace App\Files\Cleanup;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One thing the file audit found: a row of the `files` table, or a file or
 * directory relative to the files directory.
 */
#[Exclude]
final readonly class Finding
{
    /**
     * @param list<string> $files for a directory: the files the audit found
     *                            in it, relative to the files directory
     */
    public function __construct(
        public FindingKind $kind,
        public ?int $fileId,
        public ?string $path,
        public int $bytes,
        public string $reason,
        public array $files = [],
    ) {
    }
}
