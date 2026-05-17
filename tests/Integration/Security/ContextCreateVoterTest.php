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
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Authorization\Voter\ContextCreateVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Proves ContextCreateVoter reproduces the legacy decision table on the
 * REAL production shape: every read site (ProjectController::list/create,
 * Dashboard/RoomController) runs in portal context (the portal id is
 * passed as roomId), so the relevant membership is the account's
 * PORTAL user — that is where PortalSettingsController persists
 * IS_ALLOWED_TO_CREATE_CONTEXT. The setting is therefore set on the
 * portal membership here, and the request is primed as portal context.
 *
 * Exhaustive table coverage lives in the pure ContextCreateVoterDecide
 * test; this suite is the end-to-end net for the ambient wiring plus the
 * persisted-extras coercion on real DB data.
 */
#[WithStory(AccountStory::class)]
final class ContextCreateVoterTest extends KernelTestCase
{
    private AuthorizationCheckerInterface $authChecker;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->authChecker = self::getContainer()->get('security.authorization_checker');
        $this->account = AccountStory::get('account');
    }

    public function testGuestIsDenied(): void
    {
        $this->actInPortalContext($this->account, login: false);

        self::assertFalse($this->can());
    }

    public function testStatusZeroPortalMembershipIsDenied(): void
    {
        $this->portalUser($this->account)->setStatus(0);
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertFalse($this->can());
    }

    public function testRootIsAllowed(): void
    {
        $portalUser = $this->portalUser($this->account);
        $portalUser->setStatus(3);
        $portalUser->setUserId('root');
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertTrue($this->can());
    }

    public function testPortalModeratorIsAllowed(): void
    {
        $this->portalUser($this->account)->setStatus(3);
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertTrue($this->can());
    }

    public function testPerUserExtraMinusOneDenies(): void
    {
        $portalUser = $this->portalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setExtras(['IS_ALLOWED_TO_CREATE_CONTEXT' => '-1']);
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertFalse($this->can());
    }

    public function testPerUserExtraNonStandardAllows(): void
    {
        $portalUser = $this->portalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setExtras(['IS_ALLOWED_TO_CREATE_CONTEXT' => '1']);
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertTrue($this->can());
    }

    public function testPersistedBooleanFalseStillAllows(): void
    {
        // legacy quirk on real persisted data: bool false -> "" -> allow
        $portalUser = $this->portalUser($this->account);
        $portalUser->setStatus(2);
        $portalUser->setExtras(['IS_ALLOWED_TO_CREATE_CONTEXT' => false]);
        $this->flush();
        $this->actInPortalContext($this->account);

        self::assertTrue($this->can());
    }

    public function testDefaultStandardFollowsAuthSourceCreateRoomTrue(): void
    {
        $this->portalUser($this->account)->setStatus(2);
        $this->flush();
        $this->actInPortalContext($this->account);

        // AccountStory portal uses AuthSourceLocalFactory (createRoom=true)
        self::assertTrue($this->can());
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
        $this->portalUser($noCreateAccount)->setStatus(2);
        $this->flush();
        $this->actInPortalContext($noCreateAccount);

        self::assertFalse($this->can());
    }

    // ---- helpers

    private function can(): bool
    {
        return $this->authChecker->isGranted(ContextCreateVoter::CONTEXT_CREATE);
    }

    /**
     * Primes the real production shape: logs the account in and pushes a
     * request whose context is the PORTAL (the portal id is passed as
     * roomId, exactly how the room-creation flows are linked). This makes
     * CurrentUserResolver resolve the portal membership and RequestContext
     * report a portal context. Does NOT create or enter a room.
     */
    private function actInPortalContext(Account $account, bool $login = true): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            $login
                ? new UsernamePasswordToken($account, 'main', $account->getRoles())
                : null,
        );

        $request = new Request();
        $request->attributes->set('roomId', $account->getPortal()?->getId());
        self::getContainer()->get(RequestStack::class)->push($request);
    }

    private function portalUser(Account $account): User
    {
        $user = self::getContainer()->get(UserRepository::class)
            ->findByAccountIdAndContext($account->getId(), (int) $account->getPortal()?->getId());
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function flush(): void
    {
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }
}
