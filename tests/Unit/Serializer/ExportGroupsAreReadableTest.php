<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A field marked for the export must be readable, or it lands in the document
 * as null without anyone noticing: the group puts the key there, the missing
 * reader leaves it empty.
 *
 * That is not hypothetical — a comment's own text was exported as null until a
 * reader was added, the interface having read the legacy item instead.
 */
class ExportGroupsAreReadableTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function entities(): iterable
    {
        foreach (glob(__DIR__.'/../../../src/Entity/*.php') ?: [] as $file) {
            $class = 'App\\Entity\\'.basename($file, '.php');

            if (class_exists($class)) {
                yield basename($file, '.php') => [$class];
            }
        }
    }

    /**
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('entities')]
    public function testEveryExportedFieldHasAReader(string $class): void
    {
        $unreadable = [];

        foreach ((new ReflectionClass($class))->getProperties() as $property) {
            if (!$this->isExported($property) || $property->isPublic()) {
                continue;
            }

            if (!$this->hasReader($class, $property->getName())) {
                $unreadable[] = $property->getName();
            }
        }

        $this->assertSame([], $unreadable, sprintf(
            '%s marks these for the export but offers no way to read them',
            $class
        ));
    }

    private function isExported(ReflectionProperty $property): bool
    {
        foreach ($property->getAttributes(Groups::class) as $attribute) {
            /** @var Groups $groups */
            $groups = $attribute->newInstance();

            if (in_array('export', (array) $groups->groups, true)) {
                return true;
            }
        }

        return false;
    }

    private function hasReader(string $class, string $property): bool
    {
        $name = ucfirst($property);

        foreach (['get'.$name, 'is'.$name, 'has'.$name, $property] as $method) {
            if (method_exists($class, $method)) {
                return true;
            }
        }

        return false;
    }
}
