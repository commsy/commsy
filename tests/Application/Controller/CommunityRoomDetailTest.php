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
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A community room hangs off its portal. Its page in the portal is open to
 * the members of the portal, as is the page of a project room.
 */
#[WithStory(AccountStory::class)]
final class CommunityRoomDetailTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private Connection $db;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getContextId();
        $this->db = self::getContainer()->get(Connection::class);
    }

    public function testThePortalModerationIsShownTheCommunityRoomItCreated(): void
    {
        $this->setPortalStatus(3);
        $this->loginAsUser($this->portalId, $this->account->getUsername(), $this->account->getPlainPassword());

        $crawler = $this->client->request('GET', "/room/{$this->portalId}/all/create");
        $form = $crawler->selectButton('context[save]')->form();
        $form['context[title]'] = 'Gemeinschaft';
        $form['context[type_select]']->select('community');
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Gemeinschaft', (string) $this->client->getResponse()->getContent());
    }

    public function testAMemberOfThePortalSeesACommunityRoom(): void
    {
        $this->setPortalStatus(3);
        $this->loginAsUser($this->portalId, $this->account->getUsername(), $this->account->getPlainPassword());
        $roomId = $this->createCommunityRoom();
        $this->setPortalStatus(2);

        $this->client->request('GET', "/portal/{$this->portalId}/room/{$roomId}");

        self::assertResponseIsSuccessful();
    }

    private function createCommunityRoom(): int
    {
        $crawler = $this->client->request('GET', "/room/{$this->portalId}/all/create");
        $form = $crawler->selectButton('context[save]')->form();
        $form['context[title]'] = 'Gemeinschaft';
        $form['context[type_select]']->select('community');
        $this->client->submit($form);
        preg_match('~/room/(\d+)~', (string) $this->client->getResponse()->headers->get('Location'), $matches);

        return (int) $matches[1];
    }

    private function setPortalStatus(int $status): void
    {
        $this->db->update('user', ['status' => $status], ['context_id' => $this->portalId, 'user_id' => $this->account->getUsername()]);
    }
}
