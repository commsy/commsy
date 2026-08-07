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

namespace Tests\Integration\Twig\Components;

use App\Entity\Portal;
use App\Form\Model\MailText;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Integration\Concerns\PrimesSession;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see \App\Twig\Components\MailTextComponent}: portal admin inline editor for
 * customizable email templates. Mount with a Portal + MailText model and verify the
 * form renders, the placeholder badges show, and the live preview substitutes the
 * sample values without error.
 */
#[WithStory(AccountStory::class)]
final class MailTextComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use PrimesSession;

    private Portal $portal;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primeSession();

        $portal = AccountStory::get('account')->getPortal();
        self::assertNotNull($portal, 'AccountStory must provision a portal');
        $this->portal = $portal;
    }

    public function testMountsWithPortalAndFreshMailTextModel(): void
    {
        $component = $this->createLiveComponent(
            name: 'mail_text',
            data: [
                'portal' => $this->portal,
                'mailText' => new MailText(),
            ],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertSame($this->portal, $component->component()->portal);
        self::assertInstanceOf(MailText::class, $component->component()->mailText);
    }

    public function testRendersPlaceholderBadgesAndLivePreviewForSelectedText(): void
    {
        $mailText = (new MailText())
            ->setMailText('MAIL_BODY_USER_STATUS_USER')
            ->setContentGerman('Ihre Kennung {accountId} im {roomTypeName} "{roomTitle}".')
            ->setContentEnglish('Your account {accountId} in the {roomTypeName} "{roomTitle}".');

        $component = $this->createLiveComponent(
            name: 'mail_text',
            data: [
                'portal' => $this->portal,
                'mailText' => $mailText,
            ],
        );

        $rendered = (string) $component->render();

        // placeholder badge label for the selected text
        self::assertStringContainsString('Kennung', $rendered);
        // live preview rendered with sample values: {accountId} -> sample, {roomTypeName} -> Projektraum
        self::assertStringContainsString('abeispiel', $rendered);
        self::assertStringContainsString('Projektraum', $rendered);

        // the insert action must use the same (dash-normalised) identifier as the controller,
        // otherwise Stimulus never fires it and clicking a badge inserts nothing
        self::assertStringContainsString('data-controller="mailtexts-editor', $rendered);
        self::assertStringContainsString('data-action="mailtexts-editor#insert"', $rendered);
        self::assertStringContainsString('data-mailtexts-editor-token-param="{accountId}"', $rendered);
    }

    /**
     * The actions write portal-wide mail texts, so the moderator role is
     * established on every call rather than assumed from the page that
     * embedded the component.
     */
    #[DataProvider('guardedActions')]
    public function testActionsAreDeniedWithoutPortalModeratorRights(string $action, array $args = []): void
    {
        $component = $this->createLiveComponent(
            name: 'mail_text',
            data: [
                'portal' => $this->portal,
                'mailText' => new MailText(),
            ],
        );

        $this->expectException(AccessDeniedException::class);
        $component->call($action, $args);
    }

    public static function guardedActions(): iterable
    {
        yield 'select' => ['select'];
        // Arguments are resolved before the security listener runs, so the
        // LiveArg has to be supplied for the check to be what fails.
        yield 'resetContent' => ['resetContent', ['lang' => 'de']];
        yield 'save' => ['save'];
    }
}
