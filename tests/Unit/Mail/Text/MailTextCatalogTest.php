<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Text;

use App\Mail\Text\MailPlaceholder;
use App\Mail\Text\MailTextCatalog;
use PHPUnit\Framework\TestCase;

final class MailTextCatalogTest extends TestCase
{
    public function testEveryPlaceholderTokenIsAValidIcuArgument(): void
    {
        foreach (MailPlaceholder::cases() as $placeholder) {
            self::assertMatchesRegularExpression('/^[a-zA-Z][a-zA-Z0-9]*$/', $placeholder->value, "ICU named args must be plain identifiers: {$placeholder->value}");
            self::assertSame('{'.$placeholder->value.'}', $placeholder->token());
            // label/sample are translation keys (text lives in translations/portal.{de,en}.xlf)
            self::assertSame('mail_text.placeholder.'.$placeholder->value, $placeholder->labelKey());
            self::assertSame('mail_text.sample.'.$placeholder->value, $placeholder->sampleKey());
        }
    }

    public function testEveryPlaceholderKeyExistsInThePortalTranslations(): void
    {
        $xlf = file_get_contents(dirname(__DIR__, 4).'/translations/portal.de.xlf');
        self::assertNotFalse($xlf);

        foreach (MailPlaceholder::cases() as $placeholder) {
            self::assertStringContainsString('<source>'.$placeholder->labelKey().'</source>', $xlf, "placeholder label has no portal translation: {$placeholder->labelKey()}");
            self::assertStringContainsString('<source>'.$placeholder->sampleKey().'</source>', $xlf, "placeholder sample has no portal translation: {$placeholder->sampleKey()}");
        }
    }

    public function testDefinitionsAreWellFormedAndLookupsAreConsistent(): void
    {
        $catalog = new MailTextCatalog();
        self::assertNotEmpty($catalog->all());

        foreach ($catalog->all() as $definition) {
            self::assertNotSame('', $definition->key);
            self::assertNotSame('', $definition->legacyMessageId);
            self::assertNotEmpty($definition->positionalParams);

            self::assertSame($definition, $catalog->byKey($definition->key));
            self::assertSame($definition, $catalog->byLegacyId($definition->legacyMessageId));
        }

        self::assertNull($catalog->byKey('mail.does_not_exist'));
        self::assertNull($catalog->byLegacyId('MAIL_BODY_DOES_NOT_EXIST'));
    }

    public function testRoomTypeNameIsOfferedOnlyForRoomTypeAwareTexts(): void
    {
        $catalog = new MailTextCatalog();

        $salutation = $catalog->byKey('mail.salutation');
        self::assertNotNull($salutation);
        self::assertFalse($salutation->roomTypeAware);
        self::assertNotContains(MailPlaceholder::RoomTypeName, $salutation->availablePlaceholders());

        $goodbye = $catalog->byKey('mail.goodbye');
        self::assertNotNull($goodbye);
        self::assertTrue($goodbye->roomTypeAware);
        self::assertContains(MailPlaceholder::RoomTypeName, $goodbye->availablePlaceholders());
        // RoomTypeName is an editor convenience, not a legacy positional param
        self::assertNotContains(MailPlaceholder::RoomTypeName, $goodbye->positionalParams);
    }

    public function testEveryDefinitionKeyExistsInTheMailTranslations(): void
    {
        $xlf = file_get_contents(dirname(__DIR__, 4).'/translations/mail+intl-icu.de.xlf');
        self::assertNotFalse($xlf);

        foreach ((new MailTextCatalog())->all() as $definition) {
            self::assertStringContainsString('<source>'.$definition->key.'</source>', $xlf, "catalog key has no mail translation: {$definition->key}");
        }
    }
}
