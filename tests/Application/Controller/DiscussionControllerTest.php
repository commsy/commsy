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
class DiscussionControllerTest extends AbstractApplicationTestCase
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
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Diskussionsraum');
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/discussion");
        $this->assertResponseIsSuccessful();
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/discussion/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testCreate(): void
    {
        $this->client->request('GET', "/room/$this->roomId/discussion/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $itemId = $this->createDiscussionAndGetId();
        $this->client->request('GET', "/room/$this->roomId/discussion/$itemId");
        $this->assertResponseIsSuccessful();
    }

    public function testEditFormRenders(): void
    {
        $itemId = $this->createDiscussionAndGetId();
        $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/edit");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditSubmitUpdatesTitle(): void
    {
        [$itemId, $crawler] = $this->createDiscussionAndOpenEdit();

        $form = $crawler->selectButton('discussion[save]')->form();
        $form['discussion[title]'] = 'frische-diskussion';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // post-save view (app_discussion_save) renders the persisted title
        $this->assertStringContainsString('frische-diskussion', $crawler->html());
        $this->assertGreaterThan(0, $itemId);
    }

    public function testEditSubmitWithBlankTitleShowsError(): void
    {
        [$itemId, $crawler] = $this->createDiscussionAndOpenEdit();

        $form = $crawler->selectButton('discussion[save]')->form();
        $form['discussion[title]'] = '';
        $this->client->submit($form);

        // 422 = invalid form submit. The discussion edit template renders
        // the title via form_widget without a sibling form_errors call, so
        // field-level errors aren't in the DOM — 422 is the canonical signal.
        $this->assertResponseStatusCodeSame(422);
        $this->assertGreaterThan(0, $itemId);
    }

    private function createDiscussionAndGetId(): int
    {
        $this->client->request('GET', "/room/$this->roomId/discussion/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        return (int) $this->client->getRequest()->attributes->get('itemId');
    }

    /**
     * @return array{0: int, 1: \Symfony\Component\DomCrawler\Crawler}
     */
    private function createDiscussionAndOpenEdit(): array
    {
        $itemId = $this->createDiscussionAndGetId();

        $crawler = $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/edit");
        $this->assertResponseIsSuccessful();

        return [$itemId, $crawler];
    }
}
