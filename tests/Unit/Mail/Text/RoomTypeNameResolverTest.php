<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Text;

use App\Mail\Text\RoomTypeNameResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoomTypeNameResolverTest extends TestCase
{
    public function testStandardNouns(): void
    {
        $resolver = $this->resolver();

        self::assertSame('Projektraum', $resolver->nominative('project', 'de'));
        self::assertSame('project workspace', $resolver->nominative('project', 'en'));
        self::assertSame('Gemeinschaftsraum', $resolver->nominative('community', 'de'));
        self::assertSame('community workspace', $resolver->nominative('community', 'en'));
        self::assertSame('Gruppenraum', $resolver->nominative('grouproom', 'de'));
        self::assertSame('group workspace', $resolver->nominative('grouproom', 'en'));
    }

    public function testPerContextRenameWins(): void
    {
        $resolver = $this->resolver();
        $config = ['PROJECT' => ['DE' => ['NOMS' => 'Kursraum'], 'EN' => ['NOMS' => 'course room']]];

        self::assertSame('Kursraum', $resolver->nominative('project', 'de', $config));
        self::assertSame('course room', $resolver->nominative('project', 'en', $config));
        // a type without an override entry still falls back to its standard noun
        self::assertSame('Gemeinschaftsraum', $resolver->nominative('community', 'de', $config));
    }

    public function testUnknownTypeYieldsEmptyString(): void
    {
        self::assertSame('', $this->resolver()->nominative('portal', 'de'));
    }

    public function testStandardNounKeysExistInMailTranslations(): void
    {
        $xlf = file_get_contents(dirname(__DIR__, 4).'/translations/mail+intl-icu.de.xlf');
        self::assertNotFalse($xlf);

        foreach (['project', 'community', 'grouproom'] as $type) {
            self::assertStringContainsString('<source>mail.room_type.'.$type.'</source>', $xlf, "room type noun has no mail translation: {$type}");
        }
    }

    /**
     * The standard nouns live in translations/mail+intl-icu.{de,en}.xlf; the stubbed translator
     * stands in for that catalog so the assertions stay value-based while exercising the key
     * mapping and the override/fallback logic.
     */
    private function resolver(): RoomTypeNameResolver
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters, ?string $domain, ?string $locale): string => match ([$id, $locale]) {
                ['mail.room_type.project', 'de'] => 'Projektraum',
                ['mail.room_type.project', 'en'] => 'project workspace',
                ['mail.room_type.community', 'de'] => 'Gemeinschaftsraum',
                ['mail.room_type.community', 'en'] => 'community workspace',
                ['mail.room_type.grouproom', 'de'] => 'Gruppenraum',
                ['mail.room_type.grouproom', 'en'] => 'group workspace',
                default => $id,
            }
        );

        return new RoomTypeNameResolver($translator);
    }
}
