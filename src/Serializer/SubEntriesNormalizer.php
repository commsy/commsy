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

use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Serializer\Schema\FieldDescriber;
use App\Serializer\Schema\FieldDescription;
use App\Serializer\Schema\SchemaReferences;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Hangs under an entry what its rubric keeps beneath it — a material's sections,
 * the comments on any entry — for the REST interface. The export document gets
 * the same from its builder, which has to walk the sub-entries anyway to collect
 * their attachments.
 *
 * Each sub-entry is normalized with the context this was called with, not the
 * one it hands on: the "already called" marker of a decorating normalizer would
 * otherwise travel into the sub-entry and cost it its own categories, hashtags
 * and attachments. That is also why this runs first in the chain — a marker set
 * by another decorator ahead of it would arrive here and travel the same way.
 */
// Not shared: whichever serializer it is given to points it back at itself, and
// the export and the interface each need it pointing at their own chain.
#[Autoconfigure(shared: false)]
#[AutoconfigureTag('serializer.normalizer', ['priority' => 100])]
final class SubEntriesNormalizer implements NormalizerInterface, NormalizerAwareInterface, FieldDescriber
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'SUB_ENTRIES_NORMALIZER_ALREADY_CALLED';

    /**
     * @param iterable<RubricExportSource> $sources
     */
    public function __construct(
        #[AutowireIterator('app.export.rubric_source')]
        private readonly iterable $sources,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var ExportableEntry $data */
        $source = $this->sourceFor($data);

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context + [self::ALREADY_CALLED => true]);

        foreach ($source?->subEntriesOf($data) ?? [] as $key => $subEntries) {
            $normalized[$key] = [];

            foreach ($subEntries as $subEntry) {
                $normalized[$key][] = $this->normalizer->normalize($subEntry, $format, $context);
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ExportableEntry
            && !($context[self::ALREADY_CALLED] ?? false)
            && in_array('export', $context['groups'] ?? [], true)
            && null !== $this->sourceFor($data);
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Not cacheable: support depends on the context, not the class.
        return [ExportableEntry::class => false];
    }

    /**
     * The export document carries the same sub-entries, put there by its
     * builder, so this describes both.
     */
    public function describe(string $class, SchemaReferences $references): ?FieldDescription
    {
        $source = $this->sourceForClass($class);
        if (null === $source) {
            return null;
        }

        $fields = [];
        foreach ($source->subEntryTypes() as $key => $type) {
            $fields[$key] = [
                'type' => 'array',
                'items' => $references->to($type->class),
                'description' => $type->description,
            ];
        }

        return new FieldDescription($fields);
    }

    private function sourceFor(ExportableEntry $entry): ?RubricExportSource
    {
        return $this->sourceForClass($entry::class);
    }

    private function sourceForClass(string $class): ?RubricExportSource
    {
        foreach ($this->sources as $source) {
            if (is_a($class, $source->entryClass(), true)) {
                return $source;
            }
        }

        return null;
    }
}
