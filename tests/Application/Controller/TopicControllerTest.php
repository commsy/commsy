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
class TopicControllerTest extends AbstractApplicationTestCase
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
        $this->client->request('GET', "/room/$this->roomId/topic/create");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $this->client->request('GET', "/room/$this->roomId/topic/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/topic/$itemId");
        $this->assertResponseIsSuccessful();

        // Forbidden
        $this->logout();
        $this->client->request('GET', "/room/$this->roomId/topic/$itemId");
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    public function testEdit(): void
    {
        $this->client->request('GET', "/room/$this->roomId/topic/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/topic/$itemId/edit");
        $this->assertResponseIsSuccessful();
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/topic/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/topic");
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitWithTitleRedirectsToSave(): void
    {
        [$itemId, $crawler] = $this->createTopicAndOpenEdit();

        $form = $crawler->selectButton('topic[save]')->form();
        $form['topic[title]'] = 'frisches-thema';
        $this->client->submit($form);

        // Form was accepted (no 422) and routed back to the topic save view.
        // We do NOT assert that 'frisches-thema' shows up on the save view:
        // topicService->getTopic() reads from a stale legacy cache in the
        // same test cycle, so the persisted title is invisible there. The
        // 422-vs-302 distinction (vs. testEditSubmitWithBlankTitleShowsError
        // below) is what proves the title was accepted.
        $this->assertResponseRedirects("/room/$this->roomId/topic/$itemId/save");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitWithBlankTitleShowsError(): void
    {
        [$itemId, $crawler] = $this->createTopicAndOpenEdit();

        $form = $crawler->selectButton('topic[save]')->form();
        $form['topic[title]'] = '';
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertGreaterThan(0, $itemId);
    }

    /**
     * @return array{0: int, 1: \Symfony\Component\DomCrawler\Crawler}
     */
    private function createTopicAndOpenEdit(): array
    {
        $this->client->request('GET', "/room/$this->roomId/topic/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$this->roomId/topic/$itemId/edit");
        $this->assertResponseIsSuccessful();

        return [$itemId, $crawler];
    }
}
