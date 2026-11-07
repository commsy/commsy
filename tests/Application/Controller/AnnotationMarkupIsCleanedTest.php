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
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * An annotation is shown as HTML like any description, so what a member
 * writes into it is cleaned: when saved, and when shown, for annotations
 * stored before.
 */
#[WithStory(RoomWithMemberStory::class)]
final class AnnotationMarkupIsCleanedTest extends AbstractApplicationTestCase
{
    private const string MARKUP = '<p>Gute Idee</p><img src="x" onerror="document.title=1"><script>document.title=2</script>';

    private Room $room;
    private User $roomUser;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    public function testAWrittenAnnotationIsCleanedWhenSaved(): void
    {
        $announcement = $this->announcement();

        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/announcement/$announcement");
        $form = $crawler->selectButton('annotation[save]')->form();
        $form['annotation[description]'] = self::MARKUP;
        $this->client->submit($form);

        $stored = (string) self::getContainer()->get(Connection::class)
            ->fetchOne('SELECT description FROM annotations WHERE linked_item_id = ? ORDER BY item_id DESC', [$announcement]);
        self::assertStringContainsString('Gute Idee', $stored);
        self::assertStringNotContainsString('onerror', $stored);
        self::assertStringNotContainsString('<script', $stored);
    }

    public function testAnAnnotationStoredUncleanedIsCleanedWhenShown(): void
    {
        $announcement = $this->announcement();
        $annotation = AnnotationFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser, 'linkedItemId' => $announcement]);
        self::getContainer()->get(Connection::class)->update('annotations', ['description' => self::MARKUP], ['item_id' => $annotation->getItemId()]);

        $this->client->request('GET', "/room/{$this->room->getItemId()}/annotation/feed/$announcement/0");

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Gute Idee', $html);
        self::assertStringNotContainsString('onerror', $html);
        self::assertStringNotContainsString('document.title=2', $html);
    }

    private function announcement(): int
    {
        return AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();
    }
}
