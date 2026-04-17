<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Repository\RoomRepository;
use App\Room\RoomStatus;
use App\Services\LegacyEnvironment;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class ContextControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private int $privateRoomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $this->portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        // private room is created lazily on first portal entry
        $this->client->request('GET', "/helper/portal/{$this->portalId}/enter");

        /** @var RoomRepository $roomRepository */
        $roomRepository = self::getContainer()->get(RoomRepository::class);
        $privateRoom = $roomRepository->findOnePrivateByPortalIdAndAccount(
            $this->portalId,
            $this->account
        );
        $this->assertNotNull($privateRoom, 'private room should exist after portal entry');
        $this->privateRoomId = $privateRoom->getItemId();
    }

    public function testRequestFormRendersForNonMember(): void
    {
        $targetRoomId = $this->createTargetRoom();

        $this->client->request(
            'GET',
            "/room/{$this->privateRoomId}/context/{$targetRoomId}/request"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRequestRedirectsWhenRoomIsLocked(): void
    {
        $targetRoomId = $this->createTargetRoom();

        // lock the target room via legacy API
        /** @var LegacyEnvironment $legacyEnvironment */
        $legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class);
        $env = $legacyEnvironment->getEnvironment();
        $roomItem = $env->getRoomManager()->getItem($targetRoomId);
        $roomItem->lock(RoomStatus::LOCKED);
        $roomItem->save();

        $this->client->request(
            'GET',
            "/room/{$this->privateRoomId}/context/{$targetRoomId}/request"
        );

        // controller short-circuits to the room detail page
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $status);
        $this->assertLessThan(400, $status);
    }

    public function testRequestGuestIsRejected(): void
    {
        // logout the regular user and make the request anonymously; the
        // portal's guest access (if enabled) will make the user a legacy guest,
        // and the controller will throw createAccessDeniedException.
        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $targetRoomId = $this->createTargetRoom();

        $this->client->request(
            'GET',
            "/room/{$this->privateRoomId}/context/{$targetRoomId}/request"
        );

        // anonymous user gets rejected (redirect to login or 403)
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 401 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    private function createTargetRoom(): int
    {
        $targetRoom = RoomFactory::createOne([
            'contextId' => $this->portalId,
            'portal' => $this->account->getPortal(),
            'type' => 'project',
            'title' => 'Ziel-Raum',
        ]);

        return $targetRoom->getItemId();
    }
}
