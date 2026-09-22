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

namespace App\Serializer;

use App\Entity\Labels;
use App\Entity\Room;
use App\Repository\LabelRepository;
use App\Repository\TagRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds a room's categories and hashtags to its normalization.
 *
 * Neither is a column on the room: categories are `tags` linked into a tree by
 * `tag2tag`, hashtags are `labels` of type buzzword. Both are the room's own
 * vocabulary, so they stand once here and entries reference them by id.
 *
 * Decorates rather than replaces — the room's own fields still come from the
 * object normalizer, which this hands back to once per object.
 */
final class RoomStructureNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ROOM_STRUCTURE_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly TagRepository $tags,
        private readonly LabelRepository $labels,
        private readonly TitleCollator $titles,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Room $data */
        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        $normalized['categories'] = $this->tags->findCategoryTreeFor($data->getItemId());
        $normalized['hashtags'] = $this->hashtagsFor($data->getItemId());

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Room
            && !($context[self::ALREADY_CALLED] ?? false)
            && in_array('export', $context['groups'] ?? [], true);
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Not cacheable: support depends on the context, not the class.
        return [Room::class => false];
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    private function hashtagsFor(int $roomId): array
    {
        $hashtags = array_map(
            static fn (Labels $label): array => [
                'id' => (int) $label->getItemId(),
                'title' => $label->getName(),
            ],
            $this->labels->findRoomHashtags($roomId)
        );

        usort($hashtags, fn (array $a, array $b): int => $this->titles->compare($a['title'], $b['title']));

        return $hashtags;
    }
}
