<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Etherpad\EtherpadClient;
use App\Etherpad\MaterialPad;
use App\Services\EtherpadService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Drives the pad-backed description through the controller with Etherpad
 * replaced by a stand-in, so the round trip is covered without a running
 * pad server.
 *
 * The case that matters is the last one: when the pad already exists,
 * Etherpad refuses to create it again. That refusal used to be swallowed and
 * stored as an empty pad id, which switched the write-back off and left the
 * next save wiping the description.
 */
#[WithStory(RoomWithMemberStory::class)]
final class EtherpadDescriptionTest extends WebTestCase
{
    private const GROUP_ID = 'g.TestGroup00000000';
    private const PAD_HTML = '<!DOCTYPE HTML><html><body>Text aus dem Pad<br></body></html>';

    private KernelBrowser $client;
    private Room $room;
    private User $roomUser;
    private int $materialId;

    /** @var list<string> */
    private array $calls = [];

    /** @var array<string, string|false> environment as it was before this test */
    private array $environmentBefore = [];

    /** Whether the pad shows up in the group listing. */
    private bool $padIsListed = false;

    /** Whether creating the pad is refused because the name is taken. */
    private bool $createIsRefused = false;

    /** Whether the pad service refuses everything. */
    private bool $etherpadIsDown = false;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();

        // The controller only offers the pad when the feature is switched on,
        // and that is read from the environment at runtime. These are process
        // wide, so remember what was there and put it back afterwards —
        // leaking them turns unrelated tests red.
        $this->setEnvironment([
            'ETHERPAD_ENABLED' => 'true',
            'ETHERPAD_API_URL' => 'http://etherpad.test',
            'ETHERPAD_API_KEY' => 'test-key',
        ]);

        $this->client = static::createClient();
        $this->client->disableReboot();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $portalId = (int) $account->getPortal()?->getId();
        $username = $account->getUsername();
        $password = (string) $account->getPlainPassword();

        $material = MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'description' => 'Beschreibung vor der Bearbeitung',
        ]);
        $this->materialId = (int) $material->getItemId();
        $this->markMaterialAsPadEdited($this->materialId);

        self::getContainer()->set(EtherpadService::class, new EtherpadService(
            $this->etherpadStandIn(),
            'http://etherpad.test',
            'http://etherpad.test',
            'test-key',
        ));

        $this->client->request('GET', "/login/{$portalId}");
        $this->client->submitForm('login_local', ['email' => $username, 'password' => $password]);
        $this->client->followRedirect();
        $this->client->request('GET', "/room/{$this->room->getItemId()}");
    }

    protected function tearDown(): void
    {
        foreach ($this->environmentBefore as $name => $value) {
            if ($value === false) {
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
        $this->environmentBefore = [];

        parent::tearDown();
    }

    /**
     * @param array<string, string> $values
     */
    private function setEnvironment(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->environmentBefore[$name] = $_ENV[$name] ?? false;
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
    }

    public function testOpeningTheEditorPreparesThePadAndSeedsItFromTheDescription(): void
    {
        $this->openEditor();

        self::assertContains('createGroupPad', $this->calls, 'the pad has to be prepared');
        self::assertContains('setHTML', $this->calls, 'a fresh pad has to receive the current description');
    }

    public function testSavingTakesTheTextFromThePad(): void
    {
        $this->save($this->openEditor());

        self::assertStringContainsString('Text aus dem Pad', $this->storedDescription());
    }

    public function testAListedPadIsUsedWithoutCreatingItAgain(): void
    {
        $this->padIsListed = true;

        $this->save($this->openEditor());

        self::assertNotContains('createGroupPad', $this->calls);
        self::assertStringContainsString('Text aus dem Pad', $this->storedDescription());
    }

    /**
     * The regression this whole change is about: the pad exists but is not in
     * the listing, so the code tries to create it and Etherpad refuses the
     * taken name. That refusal used to become an empty pad id, after which the
     * save no longer read the pad and wrote an empty description instead.
     */
    public function testARefusedCreationAdoptsThePadInsteadOfLosingTheDescription(): void
    {
        $this->createIsRefused = true;

        $this->save($this->openEditor());

        self::assertContains('createGroupPad', $this->calls, 'the refusal has to be exercised');
        // Under the old behaviour the refusal left an empty pad id, and the
        // save then skipped the pad entirely — so this call is what separates
        // the fix from the bug.
        self::assertContains('getHTML', $this->calls, 'the save has to read the pad');
        self::assertStringContainsString(
            'Text aus dem Pad',
            $this->storedDescription(),
            'the description must come from the pad, not be emptied'
        );
    }

    /**
     * A pad service that is down must not take the page with it, and above
     * all must not cost the text that is already there.
     */
    public function testAFailingPadServiceShowsANoticeInsteadOfBreakingThePage(): void
    {
        $this->etherpadIsDown = true;

        $crawler = $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/item/{$this->materialId}/editdescription"
        );

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('uk-alert-danger', $crawler->html());
        self::assertStringNotContainsString('<iframe', $crawler->html());
    }

    public function testAFailingPadServiceOnSaveLeavesTheDescriptionAlone(): void
    {
        $crawler = $this->openEditor();

        // The service goes away between opening the editor and saving.
        $this->etherpadIsDown = true;
        $this->save($crawler);

        self::assertSame(
            'Beschreibung vor der Bearbeitung',
            $this->storedDescription(),
            'a description that could not be read must not be overwritten'
        );
        self::assertStringContainsString('uk-alert-danger', (string) $this->client->getResponse()->getContent());
    }

    // ---- helpers

    private function openEditor(): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/item/{$this->materialId}/editdescription"
        );
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    private function save(\Symfony\Component\DomCrawler\Crawler $crawler): void
    {
        // Submit the form the page actually rendered rather than guessing at
        // field names; the description field is deliberately absent here,
        // since the text comes from the pad.
        $this->client->submit($crawler->selectButton('itemDescription[save]')->form());
    }

    private function storedDescription(): string
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        return (string) $connection->fetchOne(
            'SELECT description FROM materials WHERE item_id = ? ORDER BY version_id DESC LIMIT 1',
            [$this->materialId]
        );
    }

    private function markMaterialAsPadEdited(int $itemId): void
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $extras = $connection->fetchOne('SELECT extras FROM materials WHERE item_id = ?', [$itemId]);
        $decoded = is_string($extras) && $extras !== '' ? unserialize($extras, ['allowed_classes' => false]) : [];
        $decoded = is_array($decoded) ? $decoded : [];
        $decoded['etherpad'] = '1';

        $connection->update('materials', ['extras' => serialize($decoded)], ['item_id' => $itemId]);
    }

    /**
     * Answers the handful of API calls this flow makes, routed by method name
     * so the test does not depend on their order.
     */
    private function etherpadStandIn(): MockHttpClient
    {
        return new MockHttpClient(function (string $method, string $url): MockResponse {
            $called = (string) preg_replace('#^.*/api/[^/]+/([^?]+).*$#', '$1', $url);
            $this->calls[] = $called;

            $padId = EtherpadClient::padId(self::GROUP_ID, MaterialPad::padName());

            if ($this->etherpadIsDown) {
                return self::answer(['code' => 2, 'message' => 'internal error', 'data' => null]);
            }

            $body = match ($called) {
                'createAuthorIfNotExistsFor' => ['authorID' => 'a.TestAuthor'],
                'createGroupIfNotExistsFor' => ['groupID' => self::GROUP_ID],
                'listPads' => ['padIDs' => $this->padIsListed ? [$padId] : []],
                'createSession' => ['sessionID' => 's.TestSession'],
                'getHTML' => ['html' => self::PAD_HTML],
                'createGroupPad' => ['padID' => $padId],
                default => null,
            };

            if ($called === 'createGroupPad' && $this->createIsRefused) {
                // Etherpad's own wording; the client recognises it as "already there".
                return self::answer(['code' => 1, 'message' => 'padName does already exist', 'data' => null]);
            }

            return self::answer(['code' => 0, 'message' => 'ok', 'data' => $body]);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function answer(array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    }
}
