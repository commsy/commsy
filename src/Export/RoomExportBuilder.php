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

use App\Entity\Room;

/**
 * Builds the export document for a room.
 *
 * Knows no individual fields: a rubric joins the export by carrying the
 * `export` group, not by being special-cased here.
 */
final readonly class RoomExportBuilder
{
    /**
     * Raised on any change an importer would notice.
     */
    public const FORMAT_VERSION = 1;

    public function __construct(
        private ExportSerializer $serializer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Room $room): array
    {
        return [
            'formatVersion' => self::FORMAT_VERSION,
            'room' => $this->serializer->toArray($room),
        ];
    }
}
