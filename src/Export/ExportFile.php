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
 * One attachment on its way into the archive.
 *
 * Carries both paths because the two sides need different ones: the archiver
 * reads from disk, the document points into the archive.
 */
#[Exclude]
final readonly class ExportFile
{
    public function __construct(
        public int $id,
        public string $name,
        public int $size,
        public string $sourcePath,
        public string $archivePath,
        /**
         * Rows outlive their content — a file whose bytes are gone is still
         * named here, so the document can say the attachment existed without
         * pointing at something the archive does not hold.
         */
        public bool $available,
    ) {
    }
}
