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
use App\Utils\UserService;
use App\Utils\UserroomService;
use cs_environment;
use cs_user_item;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 3.5 — characterization of UserroomService::currentDeleterId(),
 * the sole getCurrentUserItem callsite in UserroomService (line 56).
 *
 * The method is private and stamps the deleter_id on soft-deletes
 * triggered from this service. We pin the exact seam line via
 * reflection because that is precisely the line Schritt 4 reroutes
 * onto CurrentUserResolver — characterizing it directly is faithful
 * and avoids brittle end-to-end deletion choreography.
 *
 * Reuse-priority suite: UserroomService is a caller in lock 1 and 3.
 */
#[WithStory(AccountStory::class)]
final class UserroomServiceCurrentUserCharacterizationTest extends KernelTestCase
{
    private UserroomService $userroomService;
    private UserService $userService;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->userroomService = self::getContainer()->get(UserroomService::class);
        $this->userService = self::getContainer()->get(UserService::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    public function testCurrentDeleterIdIsTheCurrentUserItemId(): void
    {
        $room = RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
        RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
        ]);

        $userItem = $this->userService->getUserInContext($this->account, $room->getItemId());
        self::assertInstanceOf(cs_user_item::class, $userItem);
        $this->legacyEnvironment->setCurrentUserItem($userItem);

        self::assertSame($userItem->getItemID(), $this->currentDeleterId());
    }

    public function testCurrentDeleterIdFallsBackToZeroWithoutACurrentUser(): void
    {
        // The legacy env hands out an empty cs_user_item (itemID 0) when
        // no user was primed — the `?: 0` sentinel path.
        $empty = new cs_user_item($this->legacyEnvironment);
        $this->legacyEnvironment->setCurrentUserItem($empty);

        self::assertSame(0, $this->currentDeleterId());
    }

    private function currentDeleterId(): int
    {
        $method = new ReflectionMethod($this->userroomService, 'currentDeleterId');

        return $method->invoke($this->userroomService);
    }
}
