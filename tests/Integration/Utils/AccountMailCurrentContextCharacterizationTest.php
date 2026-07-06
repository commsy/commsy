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

namespace Tests\Integration\Utils;

use App\Entity\Account;
use App\Entity\Room;
use App\Services\LegacyEnvironment;
use App\Utils\AccountMail;
use cs_environment;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Schloss 2 / Schritt 3.5 — characterization of AccountMail, which
 * builds mail subject/body from the legacy getCurrentContextItem() /
 * getCurrentPortalItem() / getCurrentContextID() (the Schloss 2 seam).
 *
 * Robust faithful pins, not brittle full-string matches:
 *  - generateSubject() incorporates the current context room's title
 *  - an unknown action yields '' (context-independent)
 *
 * generateBody() is deliberately NOT pinned: in the test environment
 * the legacy translator returns bare message keys
 * (MAIL_BODY_USER_ACCOUNT_DELETE_PR ...) without interpolating the
 * room title / room URL, so any body assertion would pin the
 * translator-stub behaviour, not the getCurrentContextItem/
 * getCurrentContextID seam. generateSubject DOES interpolate the title
 * even in test env, so it is the faithful context-seam net here; the
 * body's context use is additionally covered at caller level during
 * the Schloss 2 migration wave.
 *
 * Reuse-priority suite (lock 2 + lock 3 caller). Must stay green when
 * the context seam is rerouted onto CurrentContextResolver.
 */
#[WithStory(AccountStory::class)]
#[RunTestsInSeparateProcesses]
final class AccountMailCurrentContextCharacterizationTest extends KernelTestCase
{
    private AccountMail $accountMail;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->accountMail = self::getContainer()->get(AccountMail::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');

        // Pin a known language so the rendered mail texts are deterministic regardless of
        // the (test-order dependent) request locale. getSelectedLanguage() reads the request
        // locale, so pin it through the same mechanism the application uses.
        $request = new Request();
        $request->setLocale('de');
        self::getContainer()->get('request_stack')->push($request);
    }

    public function testSubjectIncorporatesCurrentContextRoomTitle(): void
    {
        $room = $this->enterContext('Schloss2 AccountMail Subject Room');

        $subject = $this->accountMail->generateSubject('user-delete');

        self::assertNotSame('', $subject);
        self::assertStringContainsString($room->getTitle(), $subject);
    }

    public function testUnknownActionYieldsEmptySubject(): void
    {
        $this->enterContext('Schloss2 AccountMail Empty Room');

        self::assertSame('', $this->accountMail->generateSubject('no-such-action'));
    }

    // ---- helpers

    private function enterContext(string $title): Room
    {
        $room = RoomFactory::new()->project()->create([
            'title' => $title,
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);

        $this->legacyEnvironment->setCurrentContextID($room->getItemId());
        self::assertSame(
            $room->getItemId(),
            $this->legacyEnvironment->getCurrentContextItem()->getItemID(),
        );

        return $room;
    }
}
