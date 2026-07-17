<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Text;

use App\Mail\Text\MailTextCatalog;
use App\Mail\Text\MailTextOverrideConverter;
use PHPUnit\Framework\TestCase;

final class MailTextOverrideConverterTest extends TestCase
{
    private MailTextCatalog $catalog;

    protected function setUp(): void
    {
        $this->catalog = new MailTextCatalog();
    }

    public function testConvertsLegacyPercentTokensToNamed(): void
    {
        $legacy = [
            'MAIL_BODY_HELLO' => ['de' => 'Hallo %1,'],
            'MAIL_BODY_USER_STATUS_USER' => ['de' => 'Kennung %1 im Raum "%2".', 'en' => 'Account %1 in "%2".'],
        ];

        self::assertSame([
            'MAIL_BODY_HELLO' => ['de' => 'Hallo {recipientName},'],
            'MAIL_BODY_USER_STATUS_USER' => ['de' => 'Kennung {accountId} im Raum "{roomTitle}".', 'en' => 'Account {accountId} in "{roomTitle}".'],
        ], MailTextOverrideConverter::toNamed($this->catalog, $legacy));
    }

    public function testToNamedIsIdempotent(): void
    {
        $named = ['MAIL_BODY_HELLO' => ['de' => 'Hallo {recipientName},']];

        self::assertSame($named, MailTextOverrideConverter::toNamed($this->catalog, $named));
    }

    public function testToLegacyReverses(): void
    {
        $named = ['MAIL_BODY_USER_STATUS_USER' => ['de' => 'Kennung {accountId} im Raum "{roomTitle}".']];

        self::assertSame(
            ['MAIL_BODY_USER_STATUS_USER' => ['de' => 'Kennung %1 im Raum "%2".']],
            MailTextOverrideConverter::toLegacy($this->catalog, $named)
        );
    }

    public function testLeavesUnknownMessageIdsUntouched(): void
    {
        $other = ['SOME_OTHER_KEY' => ['de' => 'unverändert %1']];

        self::assertSame($other, MailTextOverrideConverter::toNamed($this->catalog, $other));
    }
}
