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
 * What an export archive is made of: the document, and the attachments it
 * names.
 *
 * The two travel together because the same pass over the room's entries
 * produces both — the document cannot name a file the archive does not carry.
 */
#[Exclude]
final readonly class RoomExportDocument
{
    /**
     * @param array<string, mixed> $content
     * @param list<ExportFile>     $files
     */
    public function __construct(
        public array $content,
        public array $files,
    ) {
    }
}
