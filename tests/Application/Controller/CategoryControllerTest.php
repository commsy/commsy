<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class CategoryControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $this->portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        $this->roomId = $this->createRoom($this->portalId, 'Kategorie-Raum');
    }

    public function testAddCreatesCategoryReturnsJson(): void
    {
        $this->enableTags();

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/category/add",
            ['title' => 'Forschung']
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('categoryId', $payload);
        $this->assertSame('Forschung', $payload['categoryTitle']);
    }

    public function testAddIsRejectedWhenTagsDisabled(): void
    {
        // tags NOT enabled → controller throws createAccessDeniedException.
        // CommSy's access-denied handler converts that to a redirect.
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/category/add",
            ['title' => 'Forschung']
        );

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testNewRedirectsToRoomHome(): void
    {
        $this->enableTags();

        // CategoryNewType has no submit button; the controller just redirects
        // regardless of form validity.
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/category/new",
            ['category_new' => ['title' => 'Lehre']]
        );

        $this->assertResponseRedirects("/room/{$this->roomId}");
    }

    public function testEditFormRenders(): void
    {
        $this->enableTags();

        $this->client->request('GET', "/room/{$this->roomId}/category/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditFormRendersForExistingCategory(): void
    {
        $this->enableTags();
        $categoryId = $this->createCategoryViaHttp('Forschung');

        $this->client->request(
            'GET',
            "/room/{$this->roomId}/category/edit/{$categoryId}"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testDeleteRedirectsToEdit(): void
    {
        $this->enableTags();
        $categoryId = $this->createCategoryViaHttp('Zu-Löschen');

        $this->client->request(
            'GET',
            "/room/{$this->roomId}/category/delete/{$categoryId}"
        );

        $this->assertResponseRedirects("/room/{$this->roomId}/category/edit");
    }

    private function enableTags(): void
    {
        /** @var LegacyEnvironment $legacyEnvironment */
        $legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class);
        $env = $legacyEnvironment->getEnvironment();
        $roomItem = $env->getRoomManager()->getItem($this->roomId);
        $roomItem->setWithTags();
        $roomItem->save();
    }

    private function createCategoryViaHttp(string $title): int
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/category/add",
            ['title' => $title]
        );
        $this->assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true);

        return (int) $payload['categoryId'];
    }
}
