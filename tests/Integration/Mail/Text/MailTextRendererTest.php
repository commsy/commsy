<?php

declare(strict_types=1);

namespace Tests\Integration\Mail\Text;

use App\Mail\MailTextResolver;
use App\Mail\Text\MailTextCatalog;
use App\Mail\Text\MailTextRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * P3 gate: the new renderer's default path (no override) must be byte-identical to the
 * current MailTextResolver for every catalog text x room type x language, and its override
 * path must substitute the named tokens (including {roomTypeName}).
 */
final class MailTextRendererTest extends KernelTestCase
{
    public function testDefaultPathMatchesLegacyResolverForEveryCatalogText(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);
        $resolver = self::getContainer()->get(MailTextResolver::class);
        $catalog = self::getContainer()->get(MailTextCatalog::class);

        $values = ['VALUE_ONE', 'VALUE_TWO'];

        foreach ($catalog->all() as $definition) {
            foreach (['project', 'community', 'grouproom', 'other'] as $roomType) {
                foreach (['de', 'en'] as $locale) {
                    $expected = $resolver->resolve($definition->key, $definition->legacyMessageId, $roomType, $locale, $values, []);
                    $actual = $renderer->render($definition->legacyMessageId, $roomType, $locale, $values, []);

                    self::assertSame($expected, $actual, "default mismatch: {$definition->key} / $roomType / $locale");
                }
            }
        }
    }

    public function testEditableTemplateUsesNamedTokensAndRoomTypeName(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);

        // room-type aware: the room noun becomes {roomTypeName}, the user id becomes {accountId}
        $statusUser = $renderer->templateFor('MAIL_BODY_USER_STATUS_USER', 'de');
        self::assertStringContainsString('{accountId}', $statusUser);
        self::assertStringContainsString('{roomTitle}', $statusUser);
        self::assertStringContainsString('{roomTypeName}', $statusUser);
        self::assertStringNotContainsString('{p1}', $statusUser);
        self::assertStringNotContainsString('Projektraum', $statusUser);

        // not room-type aware: just the recipient token, no {roomTypeName}
        $salutation = $renderer->templateFor('MAIL_BODY_HELLO', 'de');
        self::assertSame('Hallo {recipientName},', $salutation);
    }

    public function testOverridePathSubstitutesNamedTokens(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);

        $overrides = [
            'MAIL_BODY_HELLO' => ['de' => 'Hallo {recipientName}, willkommen!'],
            'MAIL_BODY_USER_STATUS_USER' => ['de' => 'Ihre Kennung {accountId} im {roomTypeName} "{roomTitle}".'],
        ];

        self::assertSame(
            'Hallo Anna Beispiel, willkommen!',
            $renderer->render('MAIL_BODY_HELLO', 'project', 'de', ['Anna Beispiel'], $overrides)
        );

        // {roomTypeName} is resolved from the room type, not supplied by the author
        self::assertSame(
            'Ihre Kennung abeispiel im Projektraum "Mein Kurs".',
            $renderer->render('MAIL_BODY_USER_STATUS_USER', 'project', 'de', ['abeispiel', 'Mein Kurs'], $overrides)
        );
        self::assertSame(
            'Ihre Kennung abeispiel im Gemeinschaftsraum "Mein Kurs".',
            $renderer->render('MAIL_BODY_USER_STATUS_USER', 'community', 'de', ['abeispiel', 'Mein Kurs'], $overrides)
        );
    }
}
