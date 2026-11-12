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
use App\Utils\GroupService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Mime\Email;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A new group's room is opened with the group's name. Creating a draft saves
 * its hashtags and categories before the form that carries the title, so the
 * group is saved once without a title first.
 */
#[WithStory(AccountStory::class)]
final class GroupRoomOpenedMailTest extends AbstractApplicationTestCase
{
    private const string TITLE = 'Lerngruppe Ökonomie';

    private Connection $connection;
    private int $roomId;
    private int $groupId;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = self::getContainer()->get(Connection::class);

        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($account->getContextId(), 'Testportal');

        // the portal moderation is told about new rooms, as in the reported mail
        $this->connection->update('user', ['status' => 3], ['context_id' => $account->getContextId(), 'user_id' => $account->getUsername()]);
        $this->connection->insert('account_settings', [
            'account_id' => $account->getId(),
            'name' => 'notifyPortalModOnWorkspaceChange',
            'value' => '{"enabled":true}',
        ]);

        $this->client->request('GET', "/room/$this->roomId/group/create");
        $this->client->followRedirect();
        $this->groupId = (int) $this->client->getRequest()->attributes->get('itemId');
    }

    public function testNoRoomIsOpenedForAGroupWithoutAName(): void
    {
        $this->saveTheDraftAsItsHashtagsDo();

        self::assertSame([], $this->groupRoomTitles());
        self::assertSame([], $this->sentEmails());
    }

    public function testTheRoomIsOpenedWithTheNameOfTheGroup(): void
    {
        $this->saveTheDraftAsItsHashtagsDo();

        $crawler = $this->client->request('GET', "/room/$this->roomId/group/$this->groupId/edit");
        $this->client->submit($crawler->selectButton('group[save]')->form(['group[title]' => self::TITLE]));

        self::assertSame([self::TITLE], $this->groupRoomTitles());

        $emails = $this->sentEmails();
        self::assertNotEmpty($emails);
        foreach ($emails as $email) {
            self::assertStringContainsString(self::TITLE, (string) $email->getSubject());
            self::assertStringContainsString(self::TITLE, (string) $email->getHtmlBody());
        }
    }

    /**
     * What the hashtag and category components do when a draft is created:
     * they load the entry as stored, still without a title, and save it.
     */
    private function saveTheDraftAsItsHashtagsDo(): void
    {
        self::getContainer()->get(GroupService::class)->getGroup($this->groupId)?->save();
    }

    /**
     * @return list<string>
     */
    private function groupRoomTitles(): array
    {
        return $this->connection->fetchFirstColumn("SELECT title FROM room WHERE type = 'grouproom'");
    }

    /**
     * @return list<Email>
     */
    private function sentEmails(): array
    {
        return array_values(array_filter(self::getMailerMessages(), static fn ($m) => $m instanceof Email));
    }
}
