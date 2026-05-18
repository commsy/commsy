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
use App\Utils\MailAssistant;
use cs_environment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Schloss 2 / Schritt 3.5 — characterization of the MailAssistant
 * recipient-visibility methods that branch on the legacy
 * getCurrentContextItem() (the seam Schloss 2 migrates):
 * showGroupAllRecipients() and showAllMembersRecipients().
 *
 * Reuse-priority suite: MailAssistant is the heaviest context-item
 * caller (8 calls) and is touched again by lock 3. Pinning these
 * type-driven verdicts once protects the migration.
 *
 * Scope is the deterministic, robust branches: the group-room ->
 * true and the non-room -> false outcomes are pure type predicates.
 * The project/community sub-conditions also depend on
 * withRubric() == mb_stristr(getHomeConf(), ...), which RoomFactory
 * does not control; those values are pinned as OBSERVED for the
 * factory-default home_conf (characterization pins what IS, not what
 * we wish), so the migration must keep them stable.
 */
#[WithStory(AccountStory::class)]
final class MailAssistantCurrentContextCharacterizationTest extends KernelTestCase
{
    private MailAssistant $mailAssistant;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->mailAssistant = self::getContainer()->get(MailAssistant::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    public function testGroupRoomAlwaysShowsAllMembersRecipients(): void
    {
        // showAllMembersRecipients(): the `|| isGroupRoom()` branch is a
        // pure type predicate — a group room always returns true,
        // independent of any rubric/home_conf.
        $this->enterContext($this->room('grouproom'));

        self::assertTrue($this->mailAssistant->showAllMembersRecipients(null));
    }

    public function testPrivateRoomShowsNeitherGroupAllNorAllMembers(): void
    {
        // None of the isProjectRoom / isCommunityRoom / isGroupRoom
        // branches match -> both decisions are false. Deterministic.
        $this->enterContext($this->room('privateroom'));

        self::assertFalse($this->mailAssistant->showGroupAllRecipients(null));
        self::assertFalse($this->mailAssistant->showAllMembersRecipients(null));
    }

    public function testProjectRoomGroupAllRecipientsForDefaultHomeConf(): void
    {
        // showGroupAllRecipients(): isProjectRoom && !withRubric('group').
        // OBSERVED false for a factory-default project room (its
        // default home_conf makes withRubric('group') true). Pinned as
        // the regression net — the migration must keep this stable.
        $this->enterContext($this->room('project'));

        self::assertFalse($this->mailAssistant->showGroupAllRecipients(null));
    }

    public function testCommunityRoomAllMembersRecipientsForDefaultHomeConf(): void
    {
        // showAllMembersRecipients(): isCommunityRoom && !withRubric(
        // 'project'). OBSERVED false for a factory-default community
        // room. Pinned as observed.
        $this->enterContext($this->room('community'));

        self::assertFalse($this->mailAssistant->showAllMembersRecipients(null));
    }

    // ---- helpers

    private function room(string $type): Room
    {
        return RoomFactory::new()->{$type === 'project' ? 'project'
            : ($type === 'community' ? 'community'
            : ($type === 'grouproom' ? 'groupRoom' : 'privateRoom'))}()
            ->create([
                'contextId' => $this->account->getPortal()?->getId(),
                'portal' => $this->account->getPortal(),
            ]);
    }

    private function enterContext(Room $room): void
    {
        $this->legacyEnvironment->setCurrentContextID($room->getItemId());
        // resolve + cache the legacy context item for this id
        self::assertSame(
            $room->getItemId(),
            $this->legacyEnvironment->getCurrentContextItem()->getItemID(),
        );
    }
}
