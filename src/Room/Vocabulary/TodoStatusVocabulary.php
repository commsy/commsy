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

namespace App\Room\Vocabulary;

use App\Entity\Room;
use App\Rubric\Todo\TodoStatus;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * What the number on a task means.
 *
 * Three statuses are CommSy's own, the rest a room adds for itself and explains
 * in its own wording. Without this list a bare number says nothing.
 */
#[AsTaggedItem(priority: 10)]
final readonly class TodoStatusVocabulary implements VocabularyKind
{
    #[Override]
    public function field(): string
    {
        return 'todoStatuses';
    }

    #[Override]
    public function termClass(): string
    {
        return RoomTodoStatus::class;
    }

    #[Override]
    public function description(): string
    {
        return 'What the status numbers of the room\'s tasks mean.';
    }

    /**
     * @return list<RoomTodoStatus>
     */
    #[Override]
    public function of(Room $room): array
    {
        $statuses = [];
        foreach (TodoStatus::cases() as $status) {
            $statuses[$status->value] = new RoomTodoStatus($status->value, $status->slug());
        }

        foreach ($room->getExtraTodoStatuses() as $id => $title) {
            $statuses[(int) $id] = new RoomTodoStatus((int) $id, (string) $title);
        }

        ksort($statuses);

        return array_values($statuses);
    }
}
