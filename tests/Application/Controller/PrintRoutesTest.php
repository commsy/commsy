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
use App\Entity\Room;
use App\Entity\User;
use Knp\Snappy\Pdf;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Smoke tests for the print routes: does the template render at all?
 *
 * The PDF renderer is replaced by a stub because CI has no wkhtmltopdf binary
 * and PrintService rewrites asset URLs to a host unreachable from the test
 * process. Controller wiring, Twig and PrintService::preProcessHtml() still
 * run, which is where the known defects live.
 *
 * Entries are created with content — sections, articles, steps, annotations
 * and a link — so the loops in the print templates are actually entered. An
 * empty entry would skip most of the markup and turn this into a placebo.
 */
#[WithStory(AccountStory::class)]
class PrintRoutesTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;
    private int $roomId;

    /** @var array<string, int> rubric => itemId of the entry to print */
    private array $itemIds = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');

        // The browsing context is built here rather than taken from a story:
        // RoomFactory rolls project/community, and the group rubric only exists
        // in project rooms. With a random type, RUBRIC_GROUP would decide by
        // dice whether the group list print answers 200 or redirects.
        $this->room = RoomFactory::createOne([
            'type' => 'project',
            'contextId' => $this->account->getPortal()->getId(),
            'portal' => $this->account->getPortal(),
        ]);
        $this->roomUser = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $this->room,
        ]);
        $this->roomId = $this->room->getItemId();

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        $pdf = $this->createMock(Pdf::class);
        $pdf->method('getOutputFromHtml')->willReturn('%PDF-1.4 stub');
        static::getContainer()->set('knp_snappy.pdf', $pdf);

        $this->createContent();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function rubricProvider(): iterable
    {
        foreach (['announcement', 'date', 'discussion', 'group', 'material', 'todo', 'topic'] as $rubric) {
            yield $rubric => [$rubric];
        }
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function listRubricProvider(): iterable
    {
        foreach (['announcement', 'date', 'discussion', 'group', 'material', 'todo', 'topic', 'user'] as $rubric) {
            yield $rubric => [$rubric];
        }
    }

    #[DataProvider('listRubricProvider')]
    public function testPrintListRenders(string $rubric): void
    {
        $this->client->request('GET', "/room/$this->roomId/$rubric/print/none");

        $this->assertResponseIsSuccessful();
    }

    /**
     * Opens the entry before printing it: several print templates branch on
     * the reader list being non-empty, so a never-opened entry would skip
     * exactly the code path that breaks.
     */
    #[DataProvider('rubricProvider')]
    public function testPrintDetailRenders(string $rubric): void
    {
        $itemId = $this->itemIds[$rubric];

        $this->client->request('GET', "/room/$this->roomId/$rubric/$itemId");
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', "/room/$this->roomId/$rubric/$itemId/print");
        $this->assertResponseIsSuccessful();
    }

    public function testUserPrintRenders(): void
    {
        $itemId = $this->roomUser->getItemId();

        $this->client->request('GET', "/room/$this->roomId/user/$itemId/print");

        $this->assertResponseIsSuccessful();
    }

    public function testAccountPrivacyPrintRenders(): void
    {
        $portalId = $this->account->getPortal()->getId();

        $this->client->request('GET', "/portal/$portalId/account/privacy/print");

        $this->assertResponseIsSuccessful();
    }

    public function testAnnotationFeedPrintRenders(): void
    {
        $materialId = $this->itemIds['material'];

        $this->client->request('GET', "/room/$this->roomId/annotation/feed/$materialId/0");

        $this->assertResponseIsSuccessful();
    }

    /**
     * Creates one entry per rubric, each carrying the nested content its print
     * template iterates over.
     *
     * LinkController::showDetailPrint has no reachable route of its own — five
     * actions share the path /room/{roomId}/material/link/{itemId} and only the
     * first one matches. It is reached through render(controller(...)) from
     * printmacro.html.twig instead, which is why the material below gets a
     * linked entry: without one, that forward never happens.
     */
    private function createContent(): void
    {
        $owner = ['room' => $this->room, 'creator' => $this->roomUser];

        $material = MaterialFactory::createOne($owner + ['title' => 'Seminarunterlagen']);
        SectionFactory::createMany(2, $owner + ['material' => $material]);

        $todo = TodoFactory::createOne($owner + ['title' => 'Exposé abgeben']);
        StepFactory::createMany(3, $owner + ['todo' => $todo]);

        $discussion = DiscussionFactory::createOne($owner + ['title' => 'Kodierfrage']);
        DiscussionArticleFactory::createMany(3, $owner + ['discussion' => $discussion]);

        $announcement = AnnouncementFactory::createOne($owner + ['title' => 'Werkstatt-Termine']);
        $date = DatesFactory::createOne($owner + ['title' => 'Auswertungswerkstatt']);
        $group = LabelFactory::createOne($owner + ['type' => 'group', 'name' => 'Gruppe A']);
        $topic = LabelFactory::createOne($owner + ['type' => 'topic', 'name' => 'Methodik']);

        // an annotation on the material, so the annotation feed sub-request in
        // the print template has something to render
        AnnotationFactory::createOne($owner + ['linkedItemId' => $material->getItemId()]);

        // a second material linked to the first, so the link block renders
        $linked = MaterialFactory::createOne($owner + ['title' => 'Begleittext']);
        LinkItemFactory::createOne($owner + [
            'firstItemId' => $material->getItemId(),
            'secondItemId' => $linked->getItemId(),
        ]);

        $this->itemIds = [
            'announcement' => $announcement->getItemId(),
            'date' => $date->getItemId(),
            'discussion' => $discussion->getItemId(),
            'group' => $group->getItemId(),
            'material' => $material->getItemId(),
            'todo' => $todo->getItemId(),
            'topic' => $topic->getItemId(),
        ];
    }
}
