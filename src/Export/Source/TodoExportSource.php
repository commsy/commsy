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

use App\Entity\Todos;
use App\Entity\Room;
use App\Entity\Step;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Export\SubEntryType;
use App\Repository\TodosRepository;
use App\Repository\StepRepository;
use App\Rubric\RubricType;

/**
 * The room's tasks.
 */
final readonly class TodoExportSource implements RubricExportSource
{
    public function __construct(
        private TodosRepository $todos,
        private StepRepository $steps,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Todo;
    }

    public function entryClass(): string
    {
        return Todos::class;
    }

    public function entriesFor(Room $room, int $offset = 0, ?int $limit = null): iterable
    {
        return $this->todos->findForExport($room->getItemId(), $offset, $limit);
    }

    public function countFor(Room $room): int
    {
        return $this->todos->countForExport($room->getItemId());
    }

    public function find(int $itemId): ?ExportableEntry
    {
        return $this->todos->findOneForExport($itemId);
    }

    /**
     * A task can be broken into steps, each with its own booked time.
     */
    public function subEntriesOf(ExportableEntry $entry): array
    {
        if (!$entry instanceof Todos) {
            return [];
        }

        return $this->annotations->of($entry)
            + ['steps' => $this->steps->findForExport($entry->getItemId())];
    }

    public function subEntryTypes(): array
    {
        return $this->annotations->types()
            + ['steps' => new SubEntryType(Step::class, 'The steps of the task, oldest first.')];
    }
}
