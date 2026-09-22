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
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Builds the export document for a room.
 *
 * Knows neither fields nor rubrics: a field joins the export by carrying the
 * `export` group, a rubric by registering a {@see RubricExportSource}.
 */
final readonly class RoomExportBuilder
{
    /**
     * Raised on any change an importer would notice.
     */
    public const FORMAT_VERSION = 1;

    /**
     * @param iterable<RubricExportSource> $rubrics
     */
    public function __construct(
        private ExportSerializer $serializer,
        private RoomFileCollector $fileCollector,
        #[AutowireIterator('app.export.rubric_source')]
        private iterable $rubrics,
    ) {
    }

    public function build(Room $room): RoomExportDocument
    {
        $rubrics = [];
        $versionByItemId = [];

        foreach ($this->rubrics as $source) {
            $entries = [];

            foreach ($source->entriesFor($room) as $entry) {
                $versionByItemId[$entry->getItemId()] = $entry->getVersionId();
                $serialized = $this->serializer->toArray($entry);

                foreach ($source->subEntriesOf($entry) as $key => $subEntries) {
                    $serialized[$key] = [];

                    foreach ($subEntries as $subEntry) {
                        $versionByItemId[$subEntry->getItemId()] = $subEntry->getVersionId();
                        $serialized[$key][] = $this->serializer->toArray($subEntry);
                    }
                }

                $entries[] = $serialized;
            }

            $rubrics[$source->rubric()->value] = $entries;
        }

        // By rubric rather than in whatever order the sources were registered:
        // two exports of an unchanged room should be comparable.
        ksort($rubrics);

        $files = $this->fileCollector->collect($versionByItemId);

        $content = [
            'formatVersion' => self::FORMAT_VERSION,
            'room' => $this->serializer->toArray($room),
            // Kept in one place rather than spread over the top level: an
            // importer can walk the rubrics without knowing which exist.
            'rubrics' => $rubrics,
            'files' => array_map($this->fileEntry(...), $files),
        ];

        return new RoomExportDocument($content, $files);
    }

    /**
     * Attachments stand once, with the path they have inside the archive.
     * Entries point at them by id.
     *
     * A file whose content is gone keeps its place in the list without a path:
     * the entry that carries it should not point into nothing, and the archive
     * is not asked to hold what it does not have.
     *
     * @return array{id: int, name: string, size: int, path: string|null}
     */
    private function fileEntry(ExportFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'size' => $file->size,
            'path' => $file->available ? $file->archivePath : null,
        ];
    }
}
