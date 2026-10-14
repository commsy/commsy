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

namespace App\Export;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use App\Entity\Room;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * The JSON Schema of the export document, generated from what produces the
 * document: the entities and their `export` group, the attributes on their
 * fields, and what each normalizer says it adds. Nothing of it is written down
 * twice, so it cannot fall behind the document it describes.
 */
final readonly class RoomExportSchema
{
    private const GROUPS = ['export'];

    /**
     * @param iterable<RubricExportSource> $rubrics
     */
    public function __construct(
        private SchemaFactoryInterface $schemaFactory,
        #[AutowireIterator('app.export.rubric_source')]
        private iterable $rubrics,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $definitions = (new Schema(Schema::VERSION_JSON_SCHEMA))->getDefinitions();

        $rubrics = [];
        foreach ($this->rubrics as $source) {
            $rubrics[$source->rubric()->value] = [
                'type' => 'array',
                'items' => $this->reference($source->entryClass(), $definitions),
            ];
        }
        ksort($rubrics);

        return $this->plain([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'CommSy room export (export.json)',
            'description' => 'The content of a CommSy room as its settings hand it out in an archive. Deleted '
                .'entries and drafts are left out, and of a material only the version the room shows. Times are '
                .'ISO 8601 with their offset; formatted text is HTML, the way CommSy stores it.',
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['formatVersion', 'room', 'rubrics', 'files'],
            'properties' => [
                'formatVersion' => [
                    'type' => 'integer',
                    'enum' => [RoomExportBuilder::FORMAT_VERSION],
                    'description' => 'The version of this format, raised on any change an importer would notice.',
                ],
                'room' => [
                    'allOf' => [$this->reference(Room::class, $definitions)],
                    'description' => 'The room, with what its entries share: they point at it by id.',
                ],
                'rubrics' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => array_keys($rubrics),
                    'properties' => $rubrics,
                    'description' => 'The room\'s entries by rubric, oldest first. Every rubric is there, '
                        .'as an empty list if need be.',
                ],
                'files' => [
                    'type' => 'array',
                    'items' => RoomExportBuilder::fileEntrySchema(),
                    'description' => 'Every file attached to an entry, each once however many entries attach it.',
                ],
            ],
            'definitions' => $definitions,
        ]);
    }

    /**
     * @param class-string                $class
     * @param ArrayObject<string, mixed> $definitions
     *
     * @return array{'$ref': string}
     */
    private function reference(string $class, ArrayObject $definitions): array
    {
        $schema = new Schema(Schema::VERSION_JSON_SCHEMA);
        $schema->setDefinitions($definitions);

        $built = $this->schemaFactory->buildSchema(
            $class, 'json', Schema::TYPE_OUTPUT, null, $schema, ['groups' => self::GROUPS]
        );

        return ['$ref' => $built['$ref']];
    }

    /**
     * The factory hands out ArrayObjects, which encode as empty objects.
     */
    private function plain(mixed $value): mixed
    {
        if ($value instanceof ArrayObject) {
            $value = $value->getArrayCopy();
        }

        return is_array($value) ? array_map($this->plain(...), $value) : $value;
    }
}
