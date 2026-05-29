<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\Messages\AccountMergeConfirmMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountMergeConfirmMessageTest extends TestCase
{
    public function testMessageExposesSubjectTemplateAndParameters(): void
    {
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(42);
        $portal->method('getTitle')->willReturn('Test Portal');

        $oldAccount = $this->createMock(Account::class);
        $oldAccount->method('getUsername')->willReturn('old.bkennung');

        $newAccount = $this->createMock(Account::class);
        $newAccount->method('getUsername')->willReturn('new.bkennung');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'app_account_mergeaccountsconfirm',
                ['portalId' => 42, 'token' => 'tok-123'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://commsy.example/portal/42/account/merge/confirm/tok-123');

        $message = new AccountMergeConfirmMessage($urlGenerator, $portal, $oldAccount, $newAccount, 'tok-123');

        self::assertSame('mail.account_merge_subject', $message->getSubject());
        self::assertSame('mail/account_merge_confirm.html.twig', $message->getTemplateName());
        self::assertSame(['portal' => 'Test Portal'], $message->getTranslationParameters());

        $parameters = $message->getParameters();
        self::assertSame('old.bkennung', $parameters['oldUsername']);
        self::assertSame('new.bkennung', $parameters['newUsername']);
        self::assertSame($portal, $parameters['portal']);
        self::assertSame(
            'https://commsy.example/portal/42/account/merge/confirm/tok-123',
            $parameters['confirmUrl']
        );
    }
}
