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

namespace Tests\Integration\Security;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 0 — characterization of the 4-branch decision table of
 * cs_user_item::isAllowedToCreateContext(), the logic the planned
 * ContextCreateVoter must reproduce 1:1.
 *
 * Decision table (legacy source):
 *   1. guest                                   -> false
 *   2. root                                    -> true   (DEFERRED, see below)
 *   3. context is portal AND moderator         -> true
 *   4. per-user extra IS_ALLOWED_TO_CREATE_CONTEXT:
 *        '-1'                                   -> false
 *        any other value != 'standard'          -> true
 *        'standard' (the default)               -> Account->AuthSource->getCreateRoom()
 *
 * CRUCIAL for the Voter — the persisted value shapes are NOT strings:
 * PortalSettingsController writes booleans via setIsAllowedToCreateContext
 * (true for 'standard'/'1', false for else). cs_user_item has no
 * declare(strict_types), so getIsAllowedToCreateContext(): string coerces
 * the raw stored bool: true -> "1", false -> "" (both empirically
 * verified). Run through the table that means:
 *   - persisted true  -> "1" -> not 'standard', not -1 -> ALLOW
 *   - persisted false -> ""  -> not 'standard', not -1 -> ALLOW  (a latent
 *     legacy quirk: storing false does NOT deny)
 * Doctrine maps `extras` as Types::ARRAY (PHP serialize), so it preserves
 * the RAW bool. The future ContextCreateVoter must therefore replicate
 * the legacy (string) coercion before comparing — a naive
 * `=== 'standard' / === -1` on the raw bool would diverge. These bool
 * cases below are the net that forces the Voter to match.
 *
 * The root branch (2) is deliberately NOT pinned here: it needs the
 * server-level `user_id='root'` row, whose setup is disproportionate —
 * same call we made for the K3-PRIO portal-moderator-positive case. The
 * future Voter test will add the root case with proper root scaffolding.
 *
 * The (string) coercion lives in the getter, so it is persistence-
 * independent (PHP serialize round-trips bool exactly). The branches are
 * therefore driven by setting the real stored value via the legacy
 * setter and calling the method directly — faithful without a DB
 * save / legacy-cache eviction.
 */
#[WithStory(AccountStory::class)]
final class ContextCreateCharacterizationTest extends KernelTestCase
{
    private cs_environment $legacyEnvironment;
    private UserService $userService;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->userService = self::getContainer()->get(UserService::class);
        $this->account = AccountStory::get('account');
    }

    public function testGuestCannotCreateContext(): void
    {
        $guest = new cs_user_item($this->legacyEnvironment);
        $guest->setStatus(0);

        self::assertFalse($guest->isAllowedToCreateContext());
    }

    public function testPortalModeratorCanCreateContext(): void
    {
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(3);

        self::assertTrue($portalUser->isAllowedToCreateContext());
    }

    public function testPerUserSettingMinusOneDeniesCreation(): void
    {
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setIsAllowedToCreateContext('-1');

        self::assertFalse($portalUser->isAllowedToCreateContext());
    }

    public function testPerUserSettingNonStandardAllowsCreation(): void
    {
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setIsAllowedToCreateContext('1');

        self::assertTrue($portalUser->isAllowedToCreateContext());
    }

    public function testPersistedBooleanTrueAllowsCreation(): void
    {
        // The actual shape PortalSettingsController writes for 'standard'/'1'.
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setIsAllowedToCreateContext(true);

        self::assertSame('1', $portalUser->getIsAllowedToCreateContext());
        self::assertTrue($portalUser->isAllowedToCreateContext());
    }

    public function testPersistedBooleanFalseStillAllowsCreation(): void
    {
        // The shape PortalSettingsController writes for "deny". Pinned
        // legacy quirk: false coerces to "" which is neither 'standard'
        // nor -1, so it STILL allows. The Voter must reproduce this.
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setIsAllowedToCreateContext(false);

        self::assertSame('', $portalUser->getIsAllowedToCreateContext());
        self::assertTrue($portalUser->isAllowedToCreateContext());
    }

    public function testDefaultStandardFollowsAuthSourceCreateRoomTrue(): void
    {
        // AccountStory's portal uses AuthSourceLocalFactory (createRoom=true).
        $this->login($this->account);
        $portalUser = $this->userService->getPortalUser($this->account);
        $portalUser->setStatus(2);

        self::assertSame('standard', $portalUser->getIsAllowedToCreateContext());
        self::assertTrue($portalUser->isAllowedToCreateContext());
    }

    public function testDefaultStandardFollowsAuthSourceCreateRoomFalse(): void
    {
        $portal = PortalFactory::createOne([
            'authSources' => [AuthSourceLocalFactory::createOne(['createRoom' => false])],
        ]);
        $noCreateAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
        ]);

        $this->login($noCreateAccount);
        $portalUser = $this->userService->getPortalUser($noCreateAccount);
        $portalUser->setStatus(2);

        self::assertSame('standard', $portalUser->getIsAllowedToCreateContext());
        self::assertFalse($portalUser->isAllowedToCreateContext());
    }

    private function login(Account $account): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($account, 'main', $account->getRoles()),
        );
    }
}
