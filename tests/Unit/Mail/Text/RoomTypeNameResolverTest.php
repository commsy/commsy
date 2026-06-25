<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Text;

use App\Mail\Text\RoomTypeNameResolver;
use PHPUnit\Framework\TestCase;

final class RoomTypeNameResolverTest extends TestCase
{
    public function testStandardNouns(): void
    {
        $resolver = new RoomTypeNameResolver();

        self::assertSame('Projektraum', $resolver->nominative('project', 'de'));
        self::assertSame('project workspace', $resolver->nominative('project', 'en'));
        self::assertSame('Gemeinschaftsraum', $resolver->nominative('community', 'de'));
        self::assertSame('community workspace', $resolver->nominative('community', 'en'));
        self::assertSame('Gruppenraum', $resolver->nominative('grouproom', 'de'));
        self::assertSame('group workspace', $resolver->nominative('grouproom', 'en'));
    }

    public function testPerContextRenameWins(): void
    {
        $resolver = new RoomTypeNameResolver();
        $config = ['PROJECT' => ['DE' => ['NOMS' => 'Kursraum'], 'EN' => ['NOMS' => 'course room']]];

        self::assertSame('Kursraum', $resolver->nominative('project', 'de', $config));
        self::assertSame('course room', $resolver->nominative('project', 'en', $config));
        // a type without an override entry still falls back to its standard noun
        self::assertSame('Gemeinschaftsraum', $resolver->nominative('community', 'de', $config));
    }

    public function testUnknownTypeYieldsEmptyString(): void
    {
        self::assertSame('', (new RoomTypeNameResolver())->nominative('portal', 'de'));
    }
}
