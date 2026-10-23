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

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * What a status number of a room's tasks means.
 */
#[Exclude]
final readonly class RoomTodoStatus
{
    public function __construct(
        #[Groups(['export'])]
        public int $id,
        #[ApiProperty(description: 'not_started, in_progress, done, or the name of a status of the room\'s own.')]
        #[Groups(['export'])]
        public string $name,
    ) {
    }
}
