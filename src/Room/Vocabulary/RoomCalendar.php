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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Room;
use App\State\VocabularyProvider;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A calendar of a room. The subscription URL of an external calendar is not
 * part of it: it is a way in, not content, and some carry a token.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/calendars',
            uriVariables: ['roomId' => new Link(fromClass: Room::class, identifiers: ['itemId'])],
        ),
    ],
    // The export's shape, a null included.
    normalizationContext: ['groups' => ['export'], 'skip_null_values' => false],
    security: "is_granted('ROLE_API_READ')",
    provider: VocabularyProvider::class,
    // Never a page: a tree cannot be cut into pages, and the other lists are short.
    paginationEnabled: false,
)]
#[Exclude]
final readonly class RoomCalendar
{
    public function __construct(
        #[ApiProperty(identifier: true)]
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
