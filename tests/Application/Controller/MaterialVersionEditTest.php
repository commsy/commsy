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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Editing on the page of an older material version changes that version and
 * leaves the newer ones alone.
 */
#[WithStory(AccountStory::class)]
class MaterialVersionEditTest extends AbstractApplicationTestCase
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

    public function testTheOlderVersionPointsItsEditActionsAtItself(): void
    {
        [$itemId, $firstSectionId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$itemId/0");
        $this->assertResponseIsSuccessful();
        $html = $crawler->html();

        $this->assertStringContainsString("/room/$this->roomId/material/$itemId/edit?versionId=0", $html);
        $this->assertMatchesRegularExpression("#/room/$this->roomId/item/$itemId/editdescription(/0)?\?versionId=0#", $html);
        $this->assertStringContainsString("/room/$this->roomId/material/$itemId/editsections?versionId=0", $html);
        $this->assertStringContainsString("/room/$this->roomId/material/$itemId/createsection?versionId=0", $html);
        $this->assertStringContainsString("/room/$this->roomId/item/$itemId/canceledit?versionId=0", $html);
        $this->assertStringContainsString("/room/$this->roomId/material/$firstSectionId/edit?versionId=0", $html);
        $this->assertStringContainsString("/room/$this->roomId/item/$firstSectionId/editdescription?versionId=0", $html);
        $this->assertStringContainsString("/room/$this->roomId/item/$firstSectionId/canceledit?versionId=0", $html);

        $deleteUrls = [];
        foreach ($crawler->filter('[data-cs-action]')->each(static fn (Crawler $node): ?string => $node->attr('data-cs-action')) as $json) {
            $action = json_decode((string) $json, true);
            if (str_contains($action['url'] ?? '', '/section/xhr/delete')) {
                $deleteUrls[] = $action['url'];
            }
        }
        $this->assertNotEmpty($deleteUrls);
        foreach ($deleteUrls as $deleteUrl) {
            $this->assertSame("/room/$this->roomId/section/xhr/delete/0", parse_url($deleteUrl, PHP_URL_PATH));
        }

        $sectionFileLists = [];
        foreach ($crawler->filter('[data-live-props-value]')->each(static fn (Crawler $node): ?string => $node->attr('data-live-props-value')) as $json) {
            $props = json_decode((string) $json, true);
            if (($props['itemId'] ?? null) === $firstSectionId) {
                $sectionFileLists[] = $props;
            }
        }
        $this->assertCount(1, $sectionFileLists);
        $this->assertSame(0, $sectionFileLists[0]['versionId']);
    }

    public function testTheTitleIsSavedInTheOpenedVersion(): void
    {
        [$itemId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$itemId/edit?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = 'Alte Fassung';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/material/$itemId/save?versionId=0");
        $this->assertSame('Alte Fassung', $this->materialColumn($itemId, 0, 'title'));
        $this->assertSame('Fassung', $this->materialColumn($itemId, 1, 'title'));

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Alte Fassung', $crawler->html());
    }

    public function testTheDescriptionIsSavedInTheOpenedVersion(): void
    {
        [$itemId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/item/$itemId/editdescription/0?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('itemDescription[save]')->form();
        $form['itemDescription[description]'] = 'Text der alten Fassung';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/item/$itemId/savedescription?versionId=0");
        $this->assertStringContainsString('Text der alten Fassung', (string) $this->materialColumn($itemId, 0, 'description'));
        $this->assertStringNotContainsString('Text der alten Fassung', (string) $this->materialColumn($itemId, 1, 'description'));

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Text der alten Fassung', $crawler->html());
    }

    public function testAnImageUploadedInTheEditorIsAttachedToTheOpenedVersion(): void
    {
        [$itemId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/item/$itemId/editdescription/0?versionId=0");
        $this->assertMatchesRegularExpression('#ckupload\\\\?/'.$itemId.'\\\\?/\\?[^"]*versionId=0#', $crawler->html());

        $path = tempnam(sys_get_temp_dir(), 'ck');
        file_put_contents($path, 'bild');
        $this->client->request(
            'POST',
            "/room/$this->roomId/ckupload/$itemId/?versionId=0",
            [],
            ['upload' => new UploadedFile($path, 'bild.png', 'image/png', null, true)],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [0],
            array_map('intval', $this->connection()->fetchFirstColumn('SELECT item_vid FROM item_link_file WHERE item_iid = ? AND deleter_id IS NULL', [$itemId])),
        );
    }

    public function testTheWorkflowIsSavedInTheOpenedVersion(): void
    {
        [$itemId] = $this->createMaterialWithTwoVersions();
        $newestStatus = $this->materialColumn($itemId, 1, 'workflow_status');

        $crawler = $this->client->request('GET', "/room/$this->roomId/item/$itemId/editworkflow?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('itemWorkflow[save]')->form();
        $form['itemWorkflow[workflowTrafficLight]'] = '2_red';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/material/$itemId/saveworkflow?versionId=0");
        $this->assertSame('2_red', $this->materialColumn($itemId, 0, 'workflow_status'));
        $this->assertSame($newestStatus, $this->materialColumn($itemId, 1, 'workflow_status'));

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testTheSectionsAreSortedInTheOpenedVersion(): void
    {
        [$itemId, $firstSectionId, $secondSectionId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$itemId/editsections?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('material[save]')->form();
        $form['material[sectionOrder]']->setValue("$secondSectionId,$firstSectionId");
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/material/$itemId/savesections?versionId=0");
        $this->assertSame(2, (int) $this->sectionColumn($firstSectionId, 0, 'number'));
        $this->assertSame(1, (int) $this->sectionColumn($secondSectionId, 0, 'number'));
        $this->assertSame(1, (int) $this->sectionColumn($firstSectionId, 1, 'number'));
        $this->assertSame(2, (int) $this->sectionColumn($secondSectionId, 1, 'number'));

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testANewSectionIsAddedToTheOpenedVersion(): void
    {
        [$itemId] = $this->createMaterialWithTwoVersions();

        $this->client->request('GET', "/room/$this->roomId/material/$itemId/createsection?versionId=0");
        $this->assertResponseIsSuccessful();

        $sectionId = $this->newestSectionId($itemId);
        $this->assertSame(
            [['version_id' => 0, 'number' => 3]],
            array_map(
                static fn (array $row): array => array_map('intval', $row),
                $this->connection()->fetchAllAssociative('SELECT version_id, number FROM section WHERE item_id = ?', [$sectionId]),
            ),
        );
        $this->assertStringContainsString("/room/$this->roomId/material/$sectionId/edit?versionId=0", $this->client->getResponse()->getContent());
    }

    public function testASectionTitleIsSavedInTheOpenedVersion(): void
    {
        [, $firstSectionId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$firstSectionId/edit?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('section[save]')->form();
        $form['section[title]'] = 'Altes Kapitel';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/material/$firstSectionId/save?versionId=0");
        $this->assertSame('Altes Kapitel', $this->sectionColumn($firstSectionId, 0, 'title'));
        $this->assertSame('Erstes Kapitel', $this->sectionColumn($firstSectionId, 1, 'title'));

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Altes Kapitel', $crawler->html());
    }

    public function testASectionDescriptionIsSavedInTheOpenedVersion(): void
    {
        [, $firstSectionId] = $this->createMaterialWithTwoVersions();

        $crawler = $this->client->request('GET', "/room/$this->roomId/item/$firstSectionId/editdescription/0?versionId=0");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('itemDescription[save]')->form();
        $form['itemDescription[description]'] = 'Kapiteltext der alten Fassung';
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->roomId/item/$firstSectionId/savedescription?versionId=0");
        $this->assertStringContainsString('Kapiteltext der alten Fassung', (string) $this->sectionColumn($firstSectionId, 0, 'description'));
        $this->assertStringNotContainsString('Kapiteltext der alten Fassung', (string) $this->sectionColumn($firstSectionId, 1, 'description'));

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Kapiteltext der alten Fassung', $crawler->html());
    }

    public function testASectionIsDeletedFromTheOpenedVersionOnly(): void
    {
        [$itemId, $firstSectionId] = $this->createMaterialWithTwoVersions();

        $this->client->request(
            'POST',
            "/room/$this->roomId/section/xhr/delete/0",
            ['action' => 'delete', 'positiveItemIds' => [$firstSectionId]],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            json_encode("/room/$this->roomId/material/$itemId/0"),
            $this->client->getResponse()->getContent(),
        );
        $this->assertNotNull($this->sectionColumn($firstSectionId, 0, 'deleter_id'));
        $this->assertNull($this->sectionColumn($firstSectionId, 1, 'deleter_id'));
    }

    public function testCancellingReturnsToTheOpenedVersion(): void
    {
        [$itemId, $firstSectionId] = $this->createMaterialWithTwoVersions();

        foreach ([$itemId, $firstSectionId] as $editedId) {
            $this->client->request('POST', "/room/$this->roomId/item/$editedId/canceledit?versionId=0");

            $this->assertResponseIsSuccessful();
            $this->assertSame(
                "/room/$this->roomId/material/$itemId/0",
                json_decode($this->client->getResponse()->getContent(), true)['redirectUrl'],
            );
        }
    }

    /**
     * Creates a released material titled "Fassung" with two released sections
     * and adds a second version. Version 0 is the older one.
     *
     * @return array{0: int, 1: int, 2: int} material id, first and second section id
     */
    private function createMaterialWithTwoVersions(): array
    {
        $this->client->request('GET', "/room/$this->roomId/material/create");
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$this->roomId/material/$itemId/edit");
        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = 'Fassung';
        $this->client->submit($form);
        $this->undraft($itemId);

        $sectionIds = [];
        foreach (['Erstes Kapitel', 'Zweites Kapitel'] as $title) {
            $this->client->request('GET', "/room/$this->roomId/material/$itemId/createsection");
            $sectionId = $this->newestSectionId($itemId);

            $crawler = $this->client->request('GET', "/room/$this->roomId/material/$sectionId/edit");
            $form = $crawler->selectButton('section[save]')->form();
            $form['section[title]'] = $title;
            $this->client->submit($form);
            $this->undraft($sectionId);

            $sectionIds[] = $sectionId;
        }

        $this->client->request('GET', "/room/$this->roomId/material/$itemId/0/createversion/");
        $this->assertResponseRedirects("/room/$this->roomId/material/$itemId/1");
        $this->assertSame('Fassung', $this->materialColumn($itemId, 1, 'title'));
        $this->assertSame('Erstes Kapitel', $this->sectionColumn($sectionIds[0], 1, 'title'));

        return [$itemId, ...$sectionIds];
    }

    private function undraft(int $itemId): void
    {
        $this->client->request('POST', "/room/$this->roomId/item/$itemId/undraft", [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertResponseIsSuccessful();
    }

    private function newestSectionId(int $materialId): int
    {
        return (int) $this->connection()->fetchOne('SELECT MAX(item_id) FROM section WHERE material_item_id = ?', [$materialId]);
    }

    private function materialColumn(int $itemId, int $versionId, string $column): mixed
    {
        return $this->connection()->fetchOne("SELECT $column FROM materials WHERE item_id = ? AND version_id = ?", [$itemId, $versionId]);
    }

    private function sectionColumn(int $itemId, int $versionId, string $column): mixed
    {
        return $this->connection()->fetchOne("SELECT $column FROM section WHERE item_id = ? AND version_id = ?", [$itemId, $versionId]);
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
