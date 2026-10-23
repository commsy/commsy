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

use App\Entity\Room;
use App\Room\Vocabulary\VocabularyKind;
use App\Serializer\Schema\FieldDescriber;
use App\Serializer\Schema\FieldDescription;
use App\Serializer\Schema\SchemaReferences;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds to a room what its entries point at: its categories, its hashtags, its
 * calendars, its licences and what a task's status number means here — each
 * kind of vocabulary under its field, from the kind itself.
 *
 * None of them is a column on the room. Each stands once here and entries
 * reference it by id; the interface serves the same data objects, a kind at a
 * time.
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

    /**
     * @param iterable<VocabularyKind> $vocabulary
     */
    public function __construct(
        #[AutowireIterator('app.room.vocabulary')]
        private readonly iterable $vocabulary,
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

        foreach ($this->vocabulary as $kind) {
            $normalized[$kind->field()] = $this->normalizer->normalize($kind->of($data), $format, $context);
        }

        return $normalized;
    }

    public function describe(string $class, SchemaReferences $references): ?FieldDescription
    {
        if (!is_a($class, Room::class, true)) {
            return null;
        }

        $fields = [];
        foreach ($this->vocabulary as $kind) {
            $fields[$kind->field()] = [
                'type' => 'array',
                'items' => $references->to($kind->termClass()),
                'description' => $kind->description(),
            ];
        }

        return new FieldDescription($fields);
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
}
