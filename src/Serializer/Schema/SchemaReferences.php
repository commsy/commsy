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
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Lets a describer point at other definitions of the schema being built: a
 * class whose schema the factory builds, or a shape the describer names.
 */
#[Exclude]
final readonly class SchemaReferences
{
    /**
     * @param array<string, mixed> $serializerContext
     */
    public function __construct(
        private SchemaFactoryInterface $factory,
        private Schema $schema,
        private string $format,
        private array $serializerContext,
    ) {
    }

    /**
     * A reference to the schema of $class, built into the same definitions.
     *
     * @param class-string $class
     *
     * @return array{'$ref': string}
     */
    public function to(string $class): array
    {
        $subSchema = new Schema($this->schema->getVersion());
        $subSchema->setDefinitions($this->schema->getDefinitions());

        $built = $this->factory->buildSchema(
            $class,
            $this->format,
            Schema::TYPE_OUTPUT,
            null,
            $subSchema,
            $this->serializerContext + [SchemaFactoryInterface::FORCE_SUBSCHEMA => true],
        );

        return ['$ref' => $built['$ref']];
    }

    /**
     * A reference to a shape of the describer's own, defined once under $name.
     *
     * @param array<string, mixed> $shape
     *
     * @return array{'$ref': string}
     */
    public function define(string $name, array $shape): array
    {
        $definitions = $this->schema->getDefinitions();
        if (!isset($definitions[$name])) {
            $definitions[$name] = new ArrayObject($shape);
        }

        return $this->named($name);
    }

    /**
     * A reference to $name, for a shape that contains itself.
     *
     * @return array{'$ref': string}
     */
    public function named(string $name): array
    {
        // Where definitions live differs between JSON Schema and OpenAPI; the
        // reference the factory gave this schema says which applies.
        $reference = (string) ($this->schema['$ref'] ?? $this->schema['items']['$ref']);

        return ['$ref' => substr($reference, 0, (int) strrpos($reference, '/') + 1).$name];
    }
}
