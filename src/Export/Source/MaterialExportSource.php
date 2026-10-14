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

use App\Entity\Room;
use App\Entity\Materials;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\MaterialsRepository;
use App\Repository\SectionRepository;
use App\Rubric\RubricType;

/**
 * The room's materials, each at the version the room shows.
 *
 * The first rubric that keeps versions at all, which is what decides its
 * attachments and hashtags: both are linked to a version, and an older one
 * carries what the material has since lost.
 */
final readonly class MaterialExportSource implements RubricExportSource
{
    public function __construct(
        private MaterialsRepository $materials,
        private SectionRepository $sections,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Material;
    }

    public function entryClass(): string
    {
        return Materials::class;
    }

    public function entriesFor(Room $room): iterable
    {
        return $this->materials->findForExport($room->getItemId());
    }

    public function find(int $itemId): ?ExportableEntry
    {
        return $this->materials->findOneForExport($itemId);
    }

    /**
     * A material is written in sections, which belong to the version they were
     * written on: editing a material writes its sections again under the new
     * version, in the order it then has.
     */
    public function subEntriesOf(ExportableEntry $entry): array
    {
        if (!$entry instanceof Materials) {
            return [];
        }

        return $this->annotations->of($entry)
            + ['sections' => $this->sections->findForExport($entry->getItemId(), $entry->getVersionId())];
    }
}
