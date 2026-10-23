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
 * A calendar of a room. The subscription URL of an external calendar is not
 * part of it: it is a way in, not content, and some carry a token.
 */
#[Exclude]
final readonly class RoomCalendar
{
    public function __construct(
        #[Groups(['export'])]
        public int $id,
        #[Groups(['export'])]
        public string $title,
        #[Groups(['export'])]
        public ?string $color,
        #[ApiProperty(description: 'The room\'s default calendar.')]
        #[Groups(['export'])]
        public bool $isDefault,
    ) {
    }
}
