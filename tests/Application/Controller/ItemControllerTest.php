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
class ItemControllerTest extends AbstractApplicationTestCase
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
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Item-Helpers-Raum');
    }

    public function testEditDescriptionFormRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editdescription/0");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testSaveDescriptionRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/savedescription");
        $this->assertResponseIsSuccessful();
    }

    public function testEditWorkflowFormRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editworkflow");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditLinksFormRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editlinks/20");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCancelEditReturnsRedirectUrlAsJson(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/canceledit");

        // cancelEdit returns JSON with the target URL (the actual redirect
        // is handled client-side by edit.js)
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $payload);
        // newly-created drafts redirect back to the rubric list
        $this->assertStringContainsString("/room/{$this->roomId}/material", $payload['redirectUrl']);
    }

    public function testSendFormRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/{$itemId}/send");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testFileListReturnsJson(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/filelist");
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testStepperRenders(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/stepper");
        $this->assertResponseIsSuccessful();
    }

    public function testEditDescriptionSubmitRedirectsToSave(): void
    {
        $itemId = $this->createMaterial();

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editdescription/0");
        $this->assertResponseIsSuccessful();

        // ItemDescriptionType uses the custom block prefix `itemDescription`.
        // Default-empty submit should be accepted (description is optional)
        // and redirect to app_item_savedescription.
        $form = $crawler->selectButton('itemDescription[save]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->roomId}/item/{$itemId}/savedescription");
    }

    public function testEditWorkflowSubmitRedirectsToSaveWorkflow(): void
    {
        $itemId = $this->createMaterial();

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editworkflow");
        $this->assertResponseIsSuccessful();

        // submit unchanged — workflow fields are all optional, accepting
        // the default values yields a redirect to app_material_saveworkflow
        $form = $crawler->selectButton('itemWorkflow[save]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->roomId}/material/{$itemId}/saveworkflow");
    }

    public function testEditLinksSubmitRedirectsToSaveLinks(): void
    {
        $itemId = $this->createMaterial();

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/editlinks/20");
        $this->assertResponseIsSuccessful();

        // submit unchanged — links/categories/hashtags fields are all
        // optional, default-empty submit redirects to savelinks
        $form = $crawler->selectButton('itemLinks[save]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->roomId}/item/{$itemId}/savelinks");
    }

    public function testSaveLinksRoute(): void
    {
        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/item/{$itemId}/savelinks");
        $this->assertResponseIsSuccessful();
    }

    /**
     * Creates a material item via the /material/create route (which is the
     * standard production code path) and returns its id.
     */
    private function createMaterial(): int
    {
        $this->client->request('GET', "/room/{$this->roomId}/material/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        return (int) $this->client->getRequest()->attributes->get('itemId');
    }
}
