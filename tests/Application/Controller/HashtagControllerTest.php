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
use Tests\Factory\AccountFactory;
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

    /**
     * A project room is closed, so its hashtags are room content: reading
     * them requires being able to enter the room.
     */
    public function testAllRequiresAuthenticationForAnonymousCallers(): void
    {
        $this->enableBuzzwords();
        $this->createHashtag('geheimes-schlagwort');

        $this->logout();

        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");

        $this->assertResponseRedirects("/login/{$this->portalId}");
        $this->assertStringNotContainsString(
            'geheimes-schlagwort',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testAllRequiresMembershipOfTheRoom(): void
    {
        $this->enableBuzzwords();
        $this->createHashtag('geheimes-schlagwort');

        $outsiderPassword = 'outsider-secret';
        $outsider = AccountFactory::createOne([
            'portal' => $this->account->getPortal(),
            'authSource' => $this->account->getAuthSource(),
            'plainPassword' => $outsiderPassword,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->logout();
        $this->loginAsUser($this->portalId, $outsider->getUsername(), $outsiderPassword);

        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");

        // For a normal (non-XHR) GET the access-denied handler turns the
        // refusal into a redirect; what matters is that no content comes back.
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
        $this->assertStringNotContainsString(
            'geheimes-schlagwort',
            (string) $this->client->getResponse()->getContent()
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

    public function testEditSubmitRenamesLabel(): void
    {
        $this->enableBuzzwords();
        $hashtag = $this->createHashtag('alter-name');

        $crawler = $this->client->request(
            'GET',
            "/room/{$this->roomId}/hashtag/edit/{$hashtag->getItemId()}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('hashtag_edit[update]')->form();
        $form['hashtag_edit[name]'] = 'neuer-name';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // verify via /hashtag/all that the rename was applied
        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");
        $this->assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $names = array_column($payload['results'], 'value');
        $this->assertContains('neuer-name', $names);
        $this->assertNotContains('alter-name', $names);
    }

    public function testEditSubmitWithBlankNameShowsError(): void
    {
        $this->enableBuzzwords();
        $hashtag = $this->createHashtag('bleibt-erhalten');

        $crawler = $this->client->request(
            'GET',
            "/room/{$this->roomId}/hashtag/edit/{$hashtag->getItemId()}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('hashtag_edit[update]')->form();
        $form['hashtag_edit[name]'] = '';
        $this->client->submit($form);

        // Symfony >=6.2 surfaces invalid form submits as 422 Unprocessable
        // Entity; the form re-renders with the NotBlank violation visible.
        // Use a structural selector (no text match) to stay locale-agnostic.
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('ul.form-errors li');
    }

    public function testDeleteRemovesLabelFromList(): void
    {
        $this->enableBuzzwords();
        $this->createHashtag('bleibt');
        $hashtagToDelete = $this->createHashtag('zu-loeschen');

        // sanity: both labels currently present
        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $namesBefore = array_column($payload['results'], 'value');
        $this->assertContains('zu-loeschen', $namesBefore);
        $this->assertContains('bleibt', $namesBefore);

        // click the delete button on the edit form for the doomed hashtag
        $crawler = $this->client->request(
            'GET',
            "/room/{$this->roomId}/hashtag/edit/{$hashtagToDelete->getItemId()}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('hashtag_edit[delete]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // the deleted hashtag is gone from the listing, the other one survives
        $this->client->request('GET', "/room/{$this->roomId}/hashtag/all");
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $namesAfter = array_column($payload['results'], 'value');
        $this->assertNotContains('zu-loeschen', $namesAfter);
        $this->assertContains('bleibt', $namesAfter);
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
