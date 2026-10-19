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

namespace Tests\Integration\Services;

use App\Entity\Calendars;
use App\Entity\Room;
use App\Entity\User;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Factory\CalendarsFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Importing iCalendar data into a room calendar (CalendarsService::importEvents,
 * the date import page and the subscribed-calendar sync).
 */
#[WithStory(AccountStory::class)]
final class CalendarImportTest extends KernelTestCase
{
    private const string FEED = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//CommSy Test//EN
        BEGIN:VEVENT
        UID:future@example.org
        DTSTAMP:20260101T080000Z
        CREATED:20260101T080000Z
        DTSTART:20300303T100000Z
        DTEND:20300303T113000Z
        SUMMARY:Zukunft
        LOCATION:Saal
        DESCRIPTION:Beschreibung
        END:VEVENT
        BEGIN:VEVENT
        UID:past@example.org
        DTSTAMP:20190101T080000Z
        CREATED:20190101T080000Z
        DTSTART:20200303T100000Z
        DTEND:20200303T113000Z
        SUMMARY:Vergangen
        END:VEVENT
        END:VCALENDAR
        ICS;

    private Connection $connection;
    private cs_environment $legacyEnvironment;
    private CalendarsService $calendarsService;
    private Room $room;
    private User $roomUser;
    private Calendars $calendar;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
        $this->legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();
        $this->calendarsService = $container->get(CalendarsService::class);

        $account = AccountStory::get('account');
        $portal = $account->getPortal();
        $this->room = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $this->roomUser = RoomUserFactory::createOne(['account' => $account, 'room' => $this->room, 'status' => 2]);
        $this->calendar = CalendarsFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => 'Abonniert',
            'defaultCalendar' => true,
        ]);

        $this->legacyEnvironment->setCurrentPortalID((int) $portal?->getId());
        $this->legacyEnvironment->setCurrentContextID($this->room->getItemId());
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($this->room->getItemId());
        $userManager->setUserIDLimit($this->roomUser->getUserId());
        $userManager->select();
        /** @var cs_user_item $userItem */
        $userItem = $userManager->get()->getFirst();
        $this->legacyEnvironment->setCurrentUserItem($userItem);
        $request = new Request();
        $request->attributes->set('roomId', $this->room->getItemId());
        $container->get(RequestStack::class)->push($request);
    }

    public function testImportingTheSameFeedAgainKeepsOneDatePerEvent(): void
    {
        self::assertTrue($this->calendarsService->importEvents(self::FEED, $this->calendar, true));
        self::assertTrue($this->calendarsService->importEvents(self::FEED, $this->calendar, true));

        self::assertSame(['future@example.org' => 1, 'past@example.org' => 1], $this->liveDatesByUid());
    }

    /**
     * @return array<string, int>
     */
    private function liveDatesByUid(): array
    {
        $rows = $this->connection->fetchAllKeyValue(
            'SELECT uid, COUNT(*) FROM dates WHERE context_id = ? AND deletion_date IS NULL GROUP BY uid ORDER BY uid',
            [$this->room->getItemId()],
        );

        return array_map('intval', $rows);
    }
}
