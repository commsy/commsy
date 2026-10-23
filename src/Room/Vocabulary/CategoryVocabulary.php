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
use App\Repository\TagRepository;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Categories are `tags` linked into a tree by `tag2tag`, ordered by title at
 * every level.
 */
#[AsTaggedItem(priority: 50)]
final readonly class CategoryVocabulary implements VocabularyKind
{
    public function __construct(private TagRepository $tags)
    {
    }

    #[Override]
    public function field(): string
    {
        return 'categories';
    }

    #[Override]
    public function termClass(): string
    {
        return RoomCategory::class;
    }

    #[Override]
    public function description(): string
    {
        return 'The room\'s categories as a tree; entries point at them by id.';
    }

    /**
     * @return list<RoomCategory>
     */
    #[Override]
    public function of(Room $room): array
    {
        return $this->branch($this->tags->findCategoryTreeFor($room->getItemId()));
    }

    /**
     * @param list<array<string, mixed>> $branch
     *
     * @return list<RoomCategory>
     */
    private function branch(array $branch): array
    {
        return array_map(
            fn (array $category): RoomCategory => new RoomCategory(
                (int) $category['id'],
                (string) $category['title'],
                $this->branch($category['children']),
            ),
            $branch
        );
    }
}
