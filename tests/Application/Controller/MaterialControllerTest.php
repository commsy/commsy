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
class MaterialControllerTest extends AbstractApplicationTestCase
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
        $this->client->request('GET', "/room/$this->roomId/material/create");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $this->client->request('GET', "/room/$this->roomId/material/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/material/$itemId");
        $this->assertResponseIsSuccessful();

        // Forbidden
        $this->logout();
        $this->client->request('GET', "/room/$this->roomId/material/$itemId");
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    public function testEdit(): void
    {
        $this->client->request('GET', "/room/$this->roomId/material/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/material/$itemId/edit");
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitUpdatesTitle(): void
    {
        [$itemId, $crawler] = $this->createMaterialAndOpenEdit();

        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = 'frischer-titel';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // the post-save view (app_material_save) renders the persisted title
        $this->assertStringContainsString('frischer-titel', $crawler->html());

        // sanity: $itemId was used to ensure no static-analysis warning
        $this->assertGreaterThan(0, $itemId);
    }

    public function testEditSubmitWithBlankTitleShowsError(): void
    {
        [, $crawler] = $this->createMaterialAndOpenEdit();

        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = '';
        $this->client->submit($form);

        // Symfony >=6.2: invalid form submit = 422 Unprocessable Entity.
        $this->assertResponseStatusCodeSame(422);
        // structural locale-agnostic check on the rendered field-error list
        $this->assertSelectorExists('ul.form-errors li');
    }

    /**
     * Creates a fresh material via the /create route (which redirects to the
     * detail view), then explicitly loads its /edit page. Returns
     * [itemId, crawlerOnEditPage].
     *
     * @return array{0: int, 1: \Symfony\Component\DomCrawler\Crawler}
     */
    private function createMaterialAndOpenEdit(): array
    {
        $this->client->request('GET', "/room/$this->roomId/material/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$itemId/edit");
        $this->assertResponseIsSuccessful();

        return [$itemId, $crawler];
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/material/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/material");
        $this->assertResponseIsSuccessful();
    }
}
