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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One kind of what a room's entries point at by id: its categories, hashtags,
 * calendars, licences or task statuses.
 *
 * The room export carries each kind under its field. Kinds come in the order of
 * their tag priority, which is the order the room lists them in.
 */
#[AutoconfigureTag('app.room.vocabulary')]
interface VocabularyKind
{
    /**
     * The field of the exported room it stands under.
     */
    public function field(): string;

    /**
     * The data object of one term.
     *
     * @return class-string
     */
    public function termClass(): string;

    /**
     * What the list is, for the schema.
     */
    public function description(): string;

    /**
     * @return list<object>
     */
    public function of(Room $room): array;
}
