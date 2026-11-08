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
use Doctrine\DBAL\Connection;
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

    /**
     * The dashboard calendar gives the title of a date as text, as the
     * calendar of a room does; the calendar escapes it where it builds markup.
     */
    public function testTheCalendarEventsGiveTheTitleAsText(): void
    {
        $this->client->request('GET', "/room/$this->privateRoomId/date/create/now");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        $connection = self::getContainer()->get(Connection::class);
        $connection->update('items', ['draft' => 0], ['item_id' => $itemId]);
        // stored as the date form stores it
        $connection->update('dates', ['title' => htmlentities('Grill & Bier')], ['item_id' => $itemId]);
        $start = date('Y-m-d', strtotime('-7 days'));
        $end = date('Y-m-d', strtotime('+7 days'));

        // the action reads the window from the superglobal, which the test client leaves empty
        $_GET = ['start' => $start, 'end' => $end];
        try {
            $this->client->request('GET', "/room/$this->privateRoomId/date/eventsdashboard?start=$start&end=$end");
        } finally {
            $_GET = [];
        }

        $this->assertResponseIsSuccessful();
        $events = json_decode((string) $this->client->getResponse()->getContent(), true);
        $event = current(array_filter($events, static fn (array $event): bool => $event['id'] === $itemId));
        $this->assertNotFalse($event, 'the date is listed');
        $this->assertSame('Grill & Bier', $event['title']);
    }
}
