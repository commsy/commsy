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

use App\Entity\Calendars;
use App\Entity\Labels;
use App\Entity\License;
use App\Entity\Room;
use App\Repository\CalendarsRepository;
use App\Repository\LabelRepository;
use App\Repository\LicenseRepository;
use App\Repository\TagRepository;
use App\Rubric\Todo\TodoStatus;
use App\Serializer\Schema\FieldDescriber;
use App\Serializer\Schema\FieldDescription;
use App\Serializer\Schema\SchemaReferences;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds to a room what its entries point at: its categories, its hashtags, its
 * calendars, its licences and what a task's status number means here.
 *
 * None of them is a column on the room. Categories are `tags` linked into a
 * tree by `tag2tag`, hashtags are `labels` of type buzzword, calendars and
 * licences tables of their own. Each stands once here and entries reference it
 * by id.
 *
 * Decorates rather than replaces — the room's own fields still come from the
 * object normalizer, which this hands back to once per object.
 */
// Not shared: whichever serializer it is given to points it back at itself, and
// the export and the interface each need it pointing at their own chain.
#[Autoconfigure(shared: false)]
final class RoomStructureNormalizer implements NormalizerInterface, NormalizerAwareInterface, FieldDescriber
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ROOM_STRUCTURE_NORMALIZER_ALREADY_CALLED';


    public function __construct(
        private readonly TagRepository $tags,
        private readonly LabelRepository $labels,
        private readonly CalendarsRepository $calendars,
        private readonly LicenseRepository $licenses,
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
        $normalized['calendars'] = $this->calendarsFor($data->getItemId());
        $normalized['licenses'] = $this->licensesFor($data->getItemId());
        $normalized['todoStatuses'] = $this->todoStatusesFor($data);

        return $normalized;
    }

    public function describe(string $class, SchemaReferences $references): ?FieldDescription
    {
        if (!is_a($class, Room::class, true)) {
            return null;
        }

        $list = static fn (array $item, string $description): array => [
            'type' => 'array',
            'items' => $item,
            'description' => $description,
        ];
        $shape = static fn (array $properties): array => [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => array_keys($properties),
            'properties' => $properties,
        ];
        $id = ['type' => 'integer'];
        $title = ['type' => 'string'];

        $category = $references->define('Category-export', $shape([
            'id' => $id,
            'title' => $title,
            'children' => $list($references->named('Category-export'), 'Its subcategories.'),
        ]));

        return new FieldDescription([
            'categories' => $list($category, 'The room\'s categories as a tree; entries point at them by id.'),
            'hashtags' => $list(
                $shape(['id' => $id, 'title' => $title]),
                'The room\'s hashtags, by title; entries point at them by id.'
            ),
            'calendars' => $list($shape([
                'id' => $id,
                'title' => $title,
                'color' => ['type' => ['string', 'null']],
                'isDefault' => ['type' => 'boolean', 'description' => 'The room\'s default calendar.'],
            ]), 'The room\'s calendars; dates point at them by calendarId.'),
            'licenses' => $list($shape([
                'id' => $id,
                'title' => ['type' => 'string', 'description' => 'The name of the licence.'],
                'content' => ['type' => ['string', 'null'], 'description' => 'The licence text, as HTML.'],
            ]), 'The room\'s licences; materials point at them by licenseId.'),
            'todoStatuses' => $list($shape([
                'id' => $id,
                'name' => [
                    'type' => 'string',
                    'description' => 'not_started, in_progress, done, or the name of a status of the room\'s own.',
                ],
            ]), 'What the status numbers of the room\'s tasks mean.'),
        ]);
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

    /**
     * The subscription URL of an external calendar stays out: it is a way in,
     * not content, and some carry a token that would travel with the archive.
     *
     * @return list<array{id: int, title: string, color: string|null, isDefault: bool}>
     */
    private function calendarsFor(int $roomId): array
    {
        $calendars = array_map(
            static fn (Calendars $calendar): array => [
                'id' => (int) $calendar->getId(),
                'title' => (string) $calendar->getTitle(),
                'color' => $calendar->getColor(),
                'isDefault' => $calendar->getDefaultCalendar(),
            ],
            $this->calendars->findByRoomId($roomId)
        );

        usort($calendars, fn (array $a, array $b): int => $this->titles->compare($a['title'], $b['title']));

        return $calendars;
    }

    /**
     * What the number on a task means.
     *
     * Three statuses are CommSy's own, the rest a room adds for itself and
     * explains in its own wording. Without this list a bare number says nothing.
     *
     * @return list<array{id: int, name: string}>
     */
    private function todoStatusesFor(Room $room): array
    {
        $statuses = [];
        foreach (TodoStatus::cases() as $status) {
            $statuses[$status->value] = ['id' => $status->value, 'name' => $status->slug()];
        }

        foreach ($room->getExtraTodoStatuses() as $id => $title) {
            $statuses[(int) $id] = ['id' => (int) $id, 'name' => (string) $title];
        }

        ksort($statuses);

        return array_values($statuses);
    }

    /**
     * Licences a room defines for its materials. They come back in the order the
     * room put them in, which is the order the material form offers them.
     *
     * @return list<array{id: int, title: string, content: string|null}>
     */
    private function licensesFor(int $roomId): array
    {
        return array_map(
            static fn (License $license): array => [
                'id' => (int) $license->getId(),
                'title' => (string) $license->getTitle(),
                'content' => $license->getContent(),
            ],
            $this->licenses->findByContextOrderByPosition($roomId)
        );
    }
}
