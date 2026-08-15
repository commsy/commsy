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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Builds the export document for a room.
 *
 * The fields come from the same entities and the same serialization the REST
 * interface uses — the `export` group decides what goes in. Nothing here knows
 * about individual fields, which is the point: a rubric joins the export by
 * carrying the group, not by being special-cased here.
 */
final readonly class RoomExportBuilder
{
    /**
     * Raised when the shape of the document changes in a way an importer would
     * notice. Consumers build against it, so it must not be left behind.
     */
    public const FORMAT_VERSION = 1;

    public function __construct(
        private NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Room $room): array
    {
        /** @var array<string, mixed> $normalizedRoom */
        $normalizedRoom = $this->normalizer->normalize($room, null, ['groups' => ['export']]);

        return [
            'formatVersion' => self::FORMAT_VERSION,
            'room' => $normalizedRoom,
        ];
    }
}
