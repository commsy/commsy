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
 * A licence a room offers for its materials.
 */
#[Exclude]
final readonly class RoomLicense
{
    public function __construct(
        #[Groups(['export'])]
        public int $id,
        #[ApiProperty(description: 'The name of the licence.')]
        #[Groups(['export'])]
        public string $title,
        #[ApiProperty(description: 'The licence text, as HTML.')]
        #[Groups(['export'])]
        public ?string $content,
    ) {
    }
}
