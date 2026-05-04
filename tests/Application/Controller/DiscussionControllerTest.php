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
use App\Services\LegacyEnvironment;
use App\Utils\DiscussionService;
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

    public function testAnswerFormReturns404OnDraftDiscussion(): void
    {
        // Newly-created discussions are drafts. answerRoot rejects drafts
        // with 404 — a draft discussion has no public surface to answer on.
        $itemId = $this->createDiscussionAndGetId();

        $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/answerform");

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnswerFormRendersOnPublishedDiscussion(): void
    {
        $itemId = $this->createPublishedDiscussion('Veröffentlichte Diskussion');

        $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/answerform");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAnswerSubmitCreatesAnswerAndRedirectsToDetail(): void
    {
        $itemId = $this->createPublishedDiscussion('Diskussion mit Antwort');

        $crawler = $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/answerform");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('discussion_answer[save]')->form();
        $form['discussion_answer[description]'] = 'Meine Antwort hier';
        $this->client->submit($form);

        // answerRoot redirects to the discussion detail with an
        // answer_id_<n> fragment — the path itself is enough to verify.
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(
            "/room/$this->roomId/discussion/$itemId",
            $location,
            "expected redirect to discussion detail, got: {$location}"
        );

        // verify the answer text shows up on the detail page
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Meine Antwort hier', $crawler->html());
    }

    public function testCreateAnswerRendersFormForExistingDiscussion(): void
    {
        // createAnswer is a GET that creates a new (draft) article and
        // renders the answer-edit form pointed at /editanswer.
        $itemId = $this->createPublishedDiscussion('Mit createAnswer');

        $crawler = $this->client->request('GET', "/room/$this->roomId/discussion/$itemId/createanswer");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        // the rendered form action targets the editanswer route on the
        // freshly-created article id (not the original discussion id)
        $this->assertSelectorExists('form[action*="/editanswer"]');
    }

    public function testEditAnswerSaveSubmitRedirectsToDetail(): void
    {
        $discussionId = $this->createPublishedDiscussion('Edit-Antwort-Test');

        // first, createAnswer to materialise an article + render its form
        $crawler = $this->client->request(
            'GET',
            "/room/$this->roomId/discussion/$discussionId/createanswer"
        );
        $this->assertResponseIsSuccessful();

        // submit the edit-answer form with text → 302 to detail
        $form = $crawler->selectButton('discussion_answer[save]')->form();
        $form['discussion_answer[description]'] = 'Editierte Antwort';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(
            "/room/$this->roomId/discussion/$discussionId",
            $location,
            "expected redirect to discussion detail, got: {$location}"
        );

        // verify the new answer text shows up on the detail page
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Editierte Antwort', $crawler->html());
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

    /**
     * Creates a published (non-draft) discussion directly via the legacy
     * service layer rather than going through /create + /edit + undraft.
     *
     * The HTTP route /discussion/create always sets draftStatus=1 and
     * there is no clean HTTP path inside a single test to publish that
     * discussion: /item/{id}/undraft works on disk but the legacy
     * cs_discussion_manager keeps two parallel caches (`_cache_object`
     * for instantiated items, `_cached_items` for raw DB rows) that
     * survive across requests in the disableReboot test client and
     * re-serve the stale draft=1 row on the next getDiscussion() call.
     * Going through DiscussionService directly avoids the cache trap
     * and matches what answerRoot ultimately needs (a non-draft
     * discussion that the room user owns).
     */
    private function createPublishedDiscussion(string $title): int
    {
        $discussionService = self::getContainer()->get(DiscussionService::class);
        $env = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $env->setCurrentContextID($this->roomId);

        $discussion = $discussionService->getNewDiscussion();
        $discussion->setTitle($title);
        $discussion->setDescription('');
        $discussion->setDraftStatus(0);
        $discussion->setPrivateEditing('0');
        $discussion->save();

        return (int) $discussion->getItemID();
    }
}
