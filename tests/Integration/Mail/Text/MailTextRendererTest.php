<?php

declare(strict_types=1);

namespace Tests\Integration\Mail\Text;

use App\Mail\Text\MailTextCatalog;
use App\Mail\Text\MailTextRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailTextRendererTest extends KernelTestCase
{
    public function testDefaultPathIsTheTranslatedMailKey(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $catalog = self::getContainer()->get(MailTextCatalog::class);

        $values = ['VALUE_ONE', 'VALUE_TWO'];

        // renderRaw() is the default lookup before the (separately tested) paragraph normalisation
        foreach ($catalog->all() as $definition) {
            foreach (['project', 'community', 'grouproom', 'other'] as $roomType) {
                foreach (['de', 'en'] as $locale) {
                    $arguments = ['room_type' => $roomType, 'p1' => $values[0], 'p2' => $values[1]];
                    $expected = $translator->trans($definition->key, $arguments, 'mail', $locale);

                    $actual = $renderer->renderRaw($definition->key, $definition->legacyMessageId, $roomType, $locale, $values, []);

                    self::assertSame($expected, $actual, "default mismatch: {$definition->key} / $roomType / $locale");
                }
            }
        }
    }

    public function testEditableTemplateUsesNamedTokensAndRoomTypeName(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);

        $statusUser = $renderer->templateFor('MAIL_BODY_USER_STATUS_USER', 'de');
        self::assertStringContainsString('{accountId}', $statusUser);
        self::assertStringContainsString('{roomTitle}', $statusUser);
        self::assertStringContainsString('{roomTypeName}', $statusUser);
        self::assertStringNotContainsString('{p1}', $statusUser);
        self::assertStringNotContainsString('Projektraum', $statusUser);

        self::assertSame('Hallo {recipientName},', $renderer->templateFor('MAIL_BODY_HELLO', 'de'));
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
            $renderer->render('mail.salutation', 'MAIL_BODY_HELLO', 'project', 'de', ['Anna Beispiel'], $overrides)
        );

        // {roomTypeName} is resolved from the room type, not supplied by the author
        self::assertSame(
            'Ihre Kennung abeispiel im Projektraum "Mein Kurs".',
            $renderer->render('mail.body.status_user', 'MAIL_BODY_USER_STATUS_USER', 'project', 'de', ['abeispiel', 'Mein Kurs'], $overrides)
        );
        self::assertSame(
            'Ihre Kennung abeispiel im Gemeinschaftsraum "Mein Kurs".',
            $renderer->render('mail.body.status_user', 'MAIL_BODY_USER_STATUS_USER', 'community', 'de', ['abeispiel', 'Mein Kurs'], $overrides)
        );
    }

    public function testOverridePathStillSubstitutesLegacyPercentTokens(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(MailTextRenderer::class);

        // an override not yet migrated to named tokens must keep working
        $overrides = ['MAIL_BODY_HELLO' => ['de' => 'Hallo %1, willkommen!']];

        self::assertSame(
            'Hallo Anna Beispiel, willkommen!',
            $renderer->render('mail.salutation', 'MAIL_BODY_HELLO', 'project', 'de', ['Anna Beispiel'], $overrides)
        );
    }
}
