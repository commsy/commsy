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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Calendars;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\CalendarsFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class CalendarControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser(
            $this->account->getContextId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Kalender-Raum');
    }

    public function testEditPageRendersListOfCalendars(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/calendar/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateCalendar(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/calendar/edit");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('calendar_edit[new]')->form();
        $form['calendar_edit[title]'] = 'Neuer Kalender';
        $form['calendar_edit[color]'] = '#abcdef';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->roomId}/calendar/edit");
    }

    public function testEditExistingCalendar(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $calendar = CalendarsFactory::createOne([
            'room' => $em->find(\App\Entity\Room::class, $this->roomId),
            'title' => 'Vorhanden',
        ]);

        $this->client->request('GET', "/room/{$this->roomId}/calendar/edit/{$calendar->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testExternalUrlWebcalsIsRewrittenToHttps(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $calendar = CalendarsFactory::createOne([
            'room' => $em->find(\App\Entity\Room::class, $this->roomId),
            'title' => 'Extern',
        ]);
        $calendarId = $calendar->getId();

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/calendar/edit/{$calendarId}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('calendar_edit[update]')->form();
        $form['calendar_edit[title]'] = 'Extern';
        $form['calendar_edit[color]'] = '#123456';
        $form['calendar_edit[external_url]'] = 'webcals://example.com/cal.ics';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->roomId}/calendar/edit");

        // Verify the webcals:// prefix has been rewritten to https://
        $em->clear();
        /** @var Calendars $updated */
        $updated = $em->find(Calendars::class, $calendarId);
        self::assertSame('https://example.com/cal.ics', $updated->getExternalUrl());
    }
}
