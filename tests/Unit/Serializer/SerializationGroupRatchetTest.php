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

namespace Tests\Unit\Serializer;

use App\Entity\Room;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

/**
 * The REST interface and the room export share their data objects: the entity
 * carries both `api` and `export` on a field that goes both ways. The two
 * groups run in parallel rather than one implying the other, so a field states
 * where it goes instead of relying on knowledge that `api` also means export.
 *
 * The cost of that explicitness is drift: a field added with `api` alone is
 * missing from the export, and nothing notices until a migration comes up
 * short. This test is the ratchet against it — for the classes both surfaces
 * share, `api` requires `export`. The reverse does not hold: export-only
 * fields are ordinary (a room's archived flag, a file's path inside the
 * archive), and no such field must ever reach the interface by accident.
 *
 * A field that deliberately goes to the interface only belongs in
 * {@see self::apiOnlyExemptions()} with its reason, so the decision is recorded
 * rather than looking like an oversight.
 */
class SerializationGroupRatchetTest extends TestCase
{
    /**
     * Classes serialized by both the interface and the export. Rubric entities
     * join this list as their phase lands.
     *
     * @var list<class-string>
     */
    private const SHARED = [
        Room::class,
    ];

    /**
     * Fields that carry `api` on purpose without `export`, as
     * `'Class::field' => 'reason'`.
     *
     * @return array<string, string>
     */
    private static function apiOnlyExemptions(): array
    {
        return [
            // 'App\Entity\Foo::bar' => 'why this one stays off the export',
        ];
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function sharedClassProvider(): iterable
    {
        foreach (self::SHARED as $class) {
            yield $class => [$class];
        }
    }

    /**
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sharedClassProvider')]
    public function testEveryInterfaceFieldAlsoGoesToTheExport(string $class): void
    {
        $missing = [];
        $exemptions = self::apiOnlyExemptions();

        foreach ($this->groupsPerField($class) as $field => $groups) {
            if (!in_array('api', $groups, true)) {
                continue;
            }

            if (in_array('export', $groups, true)) {
                continue;
            }

            if (isset($exemptions[$class.'::'.$field])) {
                continue;
            }

            $missing[] = $field;
        }

        $this->assertSame([], $missing, sprintf(
            '%s exposes these fields over the interface but not to the export: %s. '
            .'Add the export group, or record the exception in %s::apiOnlyExemptions() with a reason.',
            $class,
            implode(', ', $missing),
            self::class
        ));
    }

    /**
     * A shared class with no export field at all has lost its annotations
     * rather than gained an exemption.
     *
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sharedClassProvider')]
    public function testSharedClassContributesToTheExport(string $class): void
    {
        $exported = array_filter(
            $this->groupsPerField($class),
            static fn (array $groups): bool => in_array('export', $groups, true)
        );

        $this->assertNotEmpty($exported, sprintf(
            '%s is listed as shared but no field carries the export group.',
            $class
        ));
    }

    /**
     * Keeps the exemption list honest: an entry for a field that no longer
     * exists, or that meanwhile carries `export`, is stale and would quietly
     * widen the ratchet.
     */
    public function testExemptionsAreStillNeeded(): void
    {
        $exemptions = self::apiOnlyExemptions();

        if ([] === $exemptions) {
            $this->expectNotToPerformAssertions();

            return;
        }

        foreach (array_keys($exemptions) as $entry) {
            [$class, $field] = explode('::', $entry);

            $groups = $this->groupsPerField($class);

            $this->assertArrayHasKey($field, $groups, sprintf(
                'Exemption %s names a field that does not exist any more.',
                $entry
            ));

            $this->assertNotContains('export', $groups[$field], sprintf(
                'Exemption %s is stale: the field carries the export group now.',
                $entry
            ));
        }
    }

    /**
     * @param class-string $class
     *
     * @return array<string, list<string>>
     */
    private function groupsPerField(string $class): array
    {
        $metadata = (new ClassMetadataFactory(new AttributeLoader()))->getMetadataFor($class);

        $groups = [];
        foreach ($metadata->getAttributesMetadata() as $field => $attribute) {
            $groups[$field] = $attribute->getGroups();
        }

        return $groups;
    }
}
