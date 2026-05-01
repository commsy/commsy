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
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class AnnouncementControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser($this->account->getContextId(), $this->account->getUsername(), $this->account->getPlainPassword());
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Testportal');
    }

    public function testCreate(): void
    {
        $this->client->request('GET', "/room/$this->roomId/announcement/create");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $this->client->request('GET', "/room/$this->roomId/announcement/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/announcement/$itemId");
        $this->assertResponseIsSuccessful();

        // Forbidden
        $this->logout();
        $this->client->request('GET', "/room/$this->roomId/announcement/$itemId");
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    public function testEdit(): void
    {
        $this->client->request('GET', "/room/$this->roomId/announcement/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/announcement/$itemId/edit");
        $this->assertResponseIsSuccessful();
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/announcement/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/announcement");
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitUpdatesTitle(): void
    {
        [$itemId, $crawler] = $this->createAnnouncementAndOpenEdit();

        $form = $crawler->selectButton('announcement[save]')->form();
        $form['announcement[title]'] = 'frische-mitteilung';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // post-save view (app_announcement_save) renders the persisted title
        $this->assertStringContainsString('frische-mitteilung', $crawler->html());

        $this->assertGreaterThan(0, $itemId);
    }

    public function testEditSubmitWithBlankTitleShowsError(): void
    {
        [$itemId, $crawler] = $this->createAnnouncementAndOpenEdit();

        $form = $crawler->selectButton('announcement[save]')->form();
        $form['announcement[title]'] = '';
        $this->client->submit($form);

        // 422 is the canonical signal that the save was blocked. The
        // announcement edit template renders the title via form_widget
        // without a sibling form_errors call, so field-level errors aren't
        // in the DOM.
        $this->assertResponseStatusCodeSame(422);

        $this->assertGreaterThan(0, $itemId);
    }

    /**
     * @return array{0: int, 1: \Symfony\Component\DomCrawler\Crawler}
     */
    private function createAnnouncementAndOpenEdit(): array
    {
        $this->client->request('GET', "/room/$this->roomId/announcement/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$this->roomId/announcement/$itemId/edit");
        $this->assertResponseIsSuccessful();

        return [$itemId, $crawler];
    }
}
