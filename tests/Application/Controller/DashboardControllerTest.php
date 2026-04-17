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
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class DashboardControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $privateRoomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        // The private room is created lazily on first portal entry. Calling the
        // helper route ensures it exists before the tests that need it.
        $this->client->request('GET', "/helper/portal/{$portalId}/enter");

        /** @var RoomRepository $roomRepository */
        $roomRepository = self::getContainer()->get(RoomRepository::class);
        $privateRoom = $roomRepository->findOnePrivateByPortalIdAndAccount(
            $portalId,
            $this->account
        );
        $this->assertNotNull($privateRoom, 'private room should exist after portal entry');
        $this->privateRoomId = $privateRoom->getItemId();
    }

    public function testOverviewRenders(): void
    {
        $this->client->request('GET', "/dashboard/{$this->privateRoomId}");

        $this->assertResponseIsSuccessful();
    }

    public function testFeedRenders(): void
    {
        $this->client->request(
            'GET',
            "/dashboard/{$this->privateRoomId}/feed/0/date"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testEditReturnsJson(): void
    {
        $this->client->request(
            'POST',
            "/dashboard/{$this->privateRoomId}/edit",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['data' => 'col-1-1-1'])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $payload);
    }

    public function testRssRenders(): void
    {
        $this->client->request('GET', "/dashboard/{$this->privateRoomId}/rss");

        $this->assertResponseIsSuccessful();
    }

    public function testMyViewsRenders(): void
    {
        $this->client->request(
            'GET',
            "/dashboard/{$this->privateRoomId}/myviews"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testExternalAccessRenders(): void
    {
        $this->client->request(
            'GET',
            "/dashboard/{$this->privateRoomId}/externalaccess"
        );

        $this->assertResponseIsSuccessful();
    }
}
