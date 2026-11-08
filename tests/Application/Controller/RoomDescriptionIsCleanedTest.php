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

namespace Tests\Application\Controller;

use App\Entity\Account;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The description of a room is shown as HTML to everyone who looks at the
 * room, so it is cleaned like the description of any entry: when saved, and
 * when shown, for descriptions stored before.
 */
#[WithStory(AccountStory::class)]
final class RoomDescriptionIsCleanedTest extends AbstractApplicationTestCase
{
    private const string MARKUP = '<p>Worum es geht</p><img src="x" onerror="document.title=1"><script>document.title=2</script>';

    private int $portalId;
    private int $roomId;
    private Connection $db;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->portalId = $account->getContextId();
        $this->db = self::getContainer()->get(Connection::class);
        $this->loginAsUser($this->portalId, $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($this->portalId, 'Testraum');
    }

    public function testADescriptionWrittenWhenCreatingTheRoomIsCleaned(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->portalId}/all/create");
        $form = $crawler->selectButton('context[save]')->form();
        $form['context[title]'] = 'Raum mit Beschreibung';
        $form['context[type_select]'] = 'project';
        $form['context[room_description]'] = self::MARKUP;
        $this->client->submit($form);

        $this->assertResponseRedirects();
        preg_match('~/room/(\d+)~', (string) $this->client->getResponse()->headers->get('Location'), $matches);
        $this->assertStoredDescriptionIsClean((int) $matches[1]);
    }

    public function testADescriptionWrittenInTheEditorIsCleaned(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->portalId}/item/{$this->roomId}/editdescription");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('itemDescription[save]')->form();
        $form['itemDescription[description]'] = self::MARKUP;
        $this->client->submit($form);

        $this->assertStoredDescriptionIsClean($this->roomId);
    }

    public function testADescriptionStoredUncleanedIsCleanedWhenShown(): void
    {
        $this->db->update('room', ['room_description' => self::MARKUP], ['item_id' => $this->roomId]);

        $this->client->request('GET', "/portal/{$this->portalId}/room/{$this->roomId}");

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Worum es geht', $html);
        self::assertStringNotContainsString('onerror', $html);
        self::assertStringNotContainsString('document.title=2', $html);
    }

    private function assertStoredDescriptionIsClean(int $roomId): void
    {
        $stored = (string) $this->db->fetchOne('SELECT room_description FROM room WHERE item_id = ?', [$roomId]);
        self::assertStringContainsString('Worum es geht', $stored);
        self::assertStringNotContainsString('onerror', $stored);
        self::assertStringNotContainsString('<script', $stored);
    }
}
