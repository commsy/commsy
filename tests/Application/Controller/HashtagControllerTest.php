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
use App\Utils\LabelService;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class HashtagControllerTest extends AbstractApplicationTestCase
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

        $this->roomId = $this->createRoom($this->portalId, 'Hashtag-Raum');
    }

    public function testAllReturnsJsonWhenBuzzwordsEnabled(): void
    {
        $this->enableBuzzwords();
        $this->createHashtag('first');
        $this->createHashtag('second');

        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('results', $payload);
    }

    public function testAllRedirectsWhenBuzzwordsDisabled(): void
    {
        // buzzwords NOT enabled → controller throws createAccessDeniedException;
        // the CommSy access-denied handler converts that to a redirect back to
        // the room overview.
        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testAddCreatesNewHashtagReturnsJson(): void
    {
        $this->enableBuzzwords();

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/hashtag/add",
            ['title' => 'brandneu']
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('buzzwordId', $payload);
        $this->assertArrayHasKey('buzzwordTitle', $payload);
        $this->assertSame('brandneu', $payload['buzzwordTitle']);
    }

    public function testAddWithoutTitleIsRejected(): void
    {
        $this->enableBuzzwords();

        // no 'title' post parameter → controller throws createAccessDeniedException;
        // the CommSy access-denied handler converts that to a redirect.
        $this->client->request('POST', "/room/{$this->roomId}/hashtag/add");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testEditFormRenders(): void
    {
        $this->enableBuzzwords();
        $this->createHashtag('zum-editieren');

        $this->client->request('GET', "/room/{$this->roomId}/hashtag/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditFormRendersForExistingLabel(): void
    {
        $this->enableBuzzwords();
        $hashtag = $this->createHashtag('zum-editieren');

        $this->client->request(
            'GET',
            "/room/{$this->roomId}/hashtag/edit/{$hashtag->getItemId()}"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    private function enableBuzzwords(): void
    {
        /** @var LegacyEnvironment $legacyEnvironment */
        $legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class);
        $env = $legacyEnvironment->getEnvironment();
        $roomItem = $env->getRoomManager()->getItem($this->roomId);
        $roomItem->setWithBuzzwords();
        $roomItem->save();
    }

    private function createHashtag(string $name): \cs_label_item
    {
        /** @var LabelService $labelService */
        $labelService = self::getContainer()->get(LabelService::class);

        return $labelService->getNewHashtag($name, $this->roomId);
    }
}
