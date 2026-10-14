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

namespace App\Serializer\Schema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use WeakMap;

/**
 * Completes the schema of what the export group puts out.
 *
 * API Platform builds a class's schema from the fields the class declares.
 * Output of the export group carries more than that — references, sub-entries,
 * a person in place of the user row — and each normalizer that adds something
 * describes it ({@see FieldDescriber}). This applies those descriptions, so the
 * room export's schema and the interface's documentation both say what comes
 * out rather than what the entity holds.
 *
 * Every field of that output is always present, null where nothing is stored,
 * and nothing else is: the definitions say so.
 */
#[AsDecorator(decorates: 'api_platform.json_schema.schema_factory', priority: -10)]
final class ExportSchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    private const GROUP = 'export';

    /**
     * Definitions already completed, so a class referenced from several
     * places is described once.
     *
     * @var WeakMap<ArrayObject<string, mixed>, true>
     */
    private WeakMap $completed;

    /**
     * Definitions waiting for their build to finish. A class can refer to
     * itself — a user's creator is a user — so the factory is still adding
     * fields to a definition while it builds one nested in it; completing it
     * then would have those fields land after the description.
     *
     * @var list<array{ArrayObject<string, mixed>, class-string, Schema, string}>
     */
    private array $pending = [];

    private int $depth = 0;

    private bool $completing = false;

    /**
     * @param iterable<FieldDescriber> $describers
     */
    public function __construct(
        #[AutowireDecorated]
        private readonly SchemaFactoryInterface $decorated,
        #[AutowireIterator('app.serializer.field_describer')]
        private readonly iterable $describers,
    ) {
        $this->completed = new WeakMap();

        // Nested classes are built through this as well, not only the outer one.
        if ($decorated instanceof SchemaFactoryAwareInterface) {
            $decorated->setSchemaFactory($this);
        }
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->decorated instanceof SchemaFactoryAwareInterface) {
            $this->decorated->setSchemaFactory($schemaFactory);
        }
    }

    public function buildSchema(
        string $className,
        string $format = 'json',
        string $type = Schema::TYPE_OUTPUT,
        ?Operation $operation = null,
        ?Schema $schema = null,
        ?array $serializerContext = null,
        bool $forceCollection = false,
    ): Schema {
        ++$this->depth;
        try {
            $schema = $this->decorated->buildSchema(
                $className, $format, $type, $operation, $schema, $serializerContext, $forceCollection
            );
        } finally {
            --$this->depth;
        }

        $groups = (array) ($serializerContext['groups'] ?? $operation?->getNormalizationContext()['groups'] ?? []);
        $name = $schema->getRootDefinitionKey() ?? $schema->getItemsDefinitionKey();
        $definition = null === $name ? null : ($schema->getDefinitions()[$name] ?? null);

        if (Schema::TYPE_OUTPUT === $type && [self::GROUP] === $groups && $definition instanceof ArrayObject
            && !isset($this->completed[$definition])) {
            $this->pending[] = [$definition, $className, $schema, $format];
        }

        if (0 === $this->depth && !$this->completing) {
            $this->completePending();
        }

        return $schema;
    }

    /**
     * Completes what the finished build left waiting. Describing one class can
     * build another — a sub-entry's — which then waits its turn here.
     */
    private function completePending(): void
    {
        $this->completing = true;
        try {
            while ($pending = array_shift($this->pending)) {
                [$definition, $className, $schema, $format] = $pending;
                if (!isset($this->completed[$definition])) {
                    $this->completed[$definition] = true;
                    $this->complete($definition, $className, $schema, $format);
                }
            }
        } finally {
            $this->completing = false;
        }
    }

    /**
     * @param ArrayObject<string, mixed> $definition
     * @param class-string               $className
     */
    private function complete(ArrayObject $definition, string $className, Schema $schema, string $format): void
    {
        $references = new SchemaReferences($this, $schema, $format, ['groups' => [self::GROUP]]);
        $properties = $definition['properties'] ?? [];
        $optional = [];

        foreach ($this->describers as $describer) {
            $description = $describer->describe($className, $references);
            if (null === $description) {
                continue;
            }

            if ($description->replaces) {
                $properties = [];
            }
            foreach ($description->fields as $field => $fieldSchema) {
                $properties[$field] = new ArrayObject($fieldSchema);
            }
            $optional = [...$optional, ...$description->optional];
        }

        $definition['properties'] = $properties;
        $definition['required'] = array_values(array_diff(array_keys($properties), $optional));
        $definition['additionalProperties'] = false;
    }
}
