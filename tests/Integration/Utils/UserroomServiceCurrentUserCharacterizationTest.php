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
use App\Entity\User;
use App\Utils\UserroomService;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 3.5 — characterization of UserroomService::currentDeleterId(),
 * the sole getCurrentUserItem callsite in UserroomService (line 56).
 *
 * The method is private and stamps the deleter_id on soft-deletes
 * triggered from this service. The exact seam line is pinned via
 * reflection.
 *
 * Schritt 4 / Welle A note: currentDeleterId() now sources from
 * CurrentUserResolver instead of the legacy current user item. Per the
 * agreed discipline the assertions are unchanged — only the priming was
 * adjusted from "set legacy currentUserItem" to "set security token +
 * push the request" (the resolver's real inputs). Both assertions stay
 * green, which is the proof the migration was behaviour-neutral.
 */
#[WithStory(AccountStory::class)]
final class UserroomServiceCurrentUserCharacterizationTest extends KernelTestCase
{
    private UserroomService $userroomService;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->userroomService = self::getContainer()->get(UserroomService::class);
        $this->account = AccountStory::get('account');
    }

    public function testCurrentDeleterIdIsTheCurrentUserItemId(): void
    {
        $room = RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
        /** @var User $member */
        $member = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
        ]);

        $this->actAs($this->account, $room);

        self::assertSame($member->getItemId(), $this->currentDeleterId());
    }

    public function testCurrentDeleterIdFallsBackToZeroWithoutACurrentUser(): void
    {
        // No security token => resolver->getUser() is null => sentinel 0.
        self::getContainer()->get('security.token_storage')->setToken(null);

        self::assertSame(0, $this->currentDeleterId());
    }

    private function actAs(Account $account, Room $room): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($account, 'main', $account->getRoles()),
        );

        $request = new Request();
        $request->attributes->set('roomId', $room->getItemId());
        self::getContainer()->get(RequestStack::class)->push($request);
    }

    private function currentDeleterId(): int
    {
        $method = new ReflectionMethod($this->userroomService, 'currentDeleterId');

        return $method->invoke($this->userroomService);
    }
}
