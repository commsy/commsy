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

use App\Entity\Labels;
use App\Entity\Room;
use App\Repository\LabelRepository;
use App\Serializer\TitleCollator;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Hashtags are `labels` of type buzzword, ordered by title.
 */
#[AsTaggedItem(priority: 40)]
final readonly class HashtagVocabulary implements VocabularyKind
{
    public function __construct(
        private LabelRepository $labels,
        private TitleCollator $titles,
    ) {
    }

    #[Override]
    public function field(): string
    {
        return 'hashtags';
    }

    #[Override]
    public function termClass(): string
    {
        return RoomHashtag::class;
    }

    #[Override]
    public function description(): string
    {
        return 'The room\'s hashtags, by title; entries point at them by id.';
    }

    /**
     * @return list<RoomHashtag>
     */
    #[Override]
    public function of(Room $room): array
    {
        $hashtags = array_map(
            static fn (Labels $label): RoomHashtag => new RoomHashtag((int) $label->getItemId(), $label->getName()),
            $this->labels->findRoomHashtags($room->getItemId())
        );

        usort($hashtags, fn (RoomHashtag $a, RoomHashtag $b): int => $this->titles->compare($a->title, $b->title));

        return $hashtags;
    }
}
