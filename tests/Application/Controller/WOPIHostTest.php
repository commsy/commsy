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

declare(strict_types=1);

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\WOPI\Discovery\DiscoveryCache;
use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Discovery\Response\Action;
use App\WOPI\Discovery\Response\App;
use App\WOPI\Discovery\Response\NetZone;
use App\WOPI\Discovery\Response\WOPIDiscovery;
use App\WOPI\Discovery\Response\WOPIZone;
use Doctrine\DBAL\Connection;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Serializer\SerializerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\FilesFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Pins the entry into the online office (`app_wopi_host`): it opens files of
 * the checked entry only, and a version about to change a file it shares
 * with other versions gets a copy of its own first.
 */
#[WithStory(RoomWithMemberStory::class)]
final class WOPIHostTest extends WebTestCase
{
    use Factories;

    private KernelBrowser $client;
    private Room $room;
    private User $owner;
    private string $projectDir;
    private string $directory;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->projectDir = (string) $container->getParameter('kernel.project_dir');
        $this->directory = $container->getParameter('files_directory').'/phpunit-wopi/'.uniqid();
        (new Filesystem())->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->directory);
        // copies are stored by the regular file store below the room
        $filepaths = isset($this->room) ? $this->connection()->fetchFirstColumn(
            'SELECT filepath FROM files WHERE context_id = ? AND filepath <> ""',
            [$this->room->getItemId()]
        ) : [];
        foreach ($filepaths as $filepath) {
            $filesystem->remove($this->projectDir.'/'.$filepath);
        }

        parent::tearDown();
    }

    public function testAFileOfAnotherEntryDoesNotOpen(): void
    {
        $this->logInAsOwner();
        $materialId = $this->createMaterial();
        $otherId = $this->createMaterial();
        $fileId = $this->createFile();
        $this->linkFile($fileId, $otherId, 0);

        $this->client->request('GET', $this->hostUrl($materialId, $fileId));

        self::assertResponseStatusCodeSame(404);
    }

    public function testAFileOfAnotherVersionDoesNotOpen(): void
    {
        $this->logInAsOwner();
        $materialId = $this->createMaterial(withSecondVersion: true);
        $fileId = $this->createFile();
        $this->linkFile($fileId, $materialId, 0);

        $this->client->request('GET', $this->hostUrl($materialId, $fileId, 1));

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditingAFileNoOtherVersionCarriesChangesItInPlace(): void
    {
        $this->logInAsOwner();
        $materialId = $this->createMaterial(withSecondVersion: true);
        $fileId = $this->createFile();
        $this->linkFile($fileId, $materialId, 1);

        $this->client->request('GET', $this->hostUrl($materialId, $fileId, 1));

        self::assertResponseIsSuccessful();
        self::assertSame($fileId, $this->openedFileId());
        self::assertSame([$fileId], $this->liveFileIds($materialId, 1));
    }

    public function testEditingASharedFileGivesTheOpenedVersionItsOwnCopy(): void
    {
        $this->logInAsOwner();
        $materialId = $this->createMaterial(withSecondVersion: true);
        $fileId = $this->createFile('Inhalt von damals');
        $this->linkFile($fileId, $materialId, 0);
        $this->linkFile($fileId, $materialId, 1);

        $this->client->request('GET', $this->hostUrl($materialId, $fileId, 1));

        self::assertResponseIsSuccessful();
        $copyId = $this->openedFileId();
        self::assertNotSame($fileId, $copyId, 'the shared file must not be opened for editing');

        self::assertSame([$copyId], $this->liveFileIds($materialId, 1));
        self::assertSame([$fileId], $this->liveFileIds($materialId, 0), 'the older version keeps the original');
        self::assertNull($this->connection()->fetchOne('SELECT deletion_date FROM files WHERE files_id = ?', [$fileId]));

        $copy = $this->connection()->fetchAssociative('SELECT filename, filepath, size FROM files WHERE files_id = ?', [$copyId]);
        self::assertIsArray($copy);
        self::assertSame('Bericht.docx', $copy['filename']);
        self::assertStringEqualsFile($this->projectDir.'/'.$copy['filepath'], 'Inhalt von damals');
        self::assertStringEqualsFile($this->directory.'/Bericht.docx', 'Inhalt von damals');
    }

    public function testTheFileListOpensTheOfficeForTheShownVersion(): void
    {
        $this->logInAsOwner();
        $materialId = $this->createMaterial(withSecondVersion: true);
        $fileId = $this->createFile();
        $this->linkFile($fileId, $materialId, 1);

        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/material/$materialId/1");
        self::assertResponseIsSuccessful();

        $urls = $crawler->filter('[data-wopi-modal-url-value]')->each(
            static fn ($node): string => (string) $node->attr('data-wopi-modal-url-value')
        );
        self::assertSame([$this->hostUrl($materialId, $fileId, 1)], $urls);
    }

    /**
     * Logs in a member with a known password who then creates the entries,
     * and may therefore edit them. The office stub has to be in place before
     * the first request touches the discovery service.
     */
    private function logInAsOwner(): void
    {
        $this->useOfficeServerThatEditsDocx();

        /** @var Account $storyAccount */
        $storyAccount = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');

        $password = 'owner-secret';
        $account = AccountFactory::createOne([
            'portal' => $storyAccount->getPortal(),
            'authSource' => $storyAccount->getAuthSource(),
            'plainPassword' => $password,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $this->owner = RoomUserFactory::createOne(['account' => $account, 'room' => $this->room]);

        $this->client->request('GET', "/login/{$account->getPortal()?->getId()}");
        $this->client->submitForm('login_local', [
            'email' => $account->getUsername(),
            'password' => $password,
        ]);
        $this->client->followRedirect();
        $this->client->request('GET', "/room/{$this->room->getItemId()}");
    }

    private function hostUrl(int $itemId, int $fileId, ?int $versionId = null): string
    {
        $url = "/room/{$this->room->getItemId()}/item/$itemId/file/$fileId/wopi";

        return null === $versionId ? $url : "$url?versionId=$versionId";
    }

    /**
     * The file the editor was handed, read from the access token.
     */
    private function openedFileId(): int
    {
        $token = $this->client->getCrawler()->filter('input[name=access_token]')->attr('value');

        /** @var JWTTokenManagerInterface $tokenManager */
        $tokenManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        return (int) $tokenManager->parse((string) $token)['fid'];
    }

    private function createMaterial(bool $withSecondVersion = false): int
    {
        $materialId = (int) MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->owner])->getItemId();

        if ($withSecondVersion) {
            $this->connection()->executeStatement(
                'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, modifier_id, modification_date, title, extras)
                    SELECT item_id, 1, context_id, creator_id, creation_date, modifier_id, modification_date, title, extras
                    FROM materials WHERE item_id = ? AND version_id = 0',
                [$materialId]
            );
        }

        return $materialId;
    }

    private function createFile(string $content = 'Inhalt'): int
    {
        $path = $this->directory.'/Bericht.docx';
        file_put_contents($path, $content);

        return FilesFactory::createOne([
            'contextId' => $this->room->getItemId(),
            'portal' => $this->room->getPortal() ?? RoomWithMemberStory::get('account')->getPortal(),
            'filename' => 'Bericht.docx',
            'filepath' => ltrim(substr($path, \strlen($this->projectDir)), '/'),
            'size' => \strlen($content),
        ])->getFilesId();
    }

    private function linkFile(int $fileId, int $itemId, int $versionId): void
    {
        $this->connection()->executeStatement(
            'INSERT INTO item_link_file (file_id, item_iid, item_vid) VALUES (?, ?, ?)',
            [$fileId, $itemId, $versionId]
        );
    }

    /**
     * @return int[]
     */
    private function liveFileIds(int $itemId, int $versionId): array
    {
        return array_map('intval', $this->connection()->fetchFirstColumn(
            'SELECT file_id FROM item_link_file WHERE item_iid = ? AND item_vid = ? AND deletion_date IS NULL ORDER BY file_id',
            [$itemId, $versionId]
        ));
    }

    /**
     * There is no office server in the test environment: a discovery that
     * offers to view and edit docx is put where the service finds it.
     */
    private function useOfficeServerThatEditsDocx(): void
    {
        // a host of its own per test; the cache key does not allow a path
        $baseUrl = 'http://office-'.uniqid().'.test';

        $actions = [];
        foreach (['view', 'edit'] as $name) {
            $actions[] = (new Action())
                ->setName($name)
                ->setExt('docx')
                ->setDefault('view' === $name)
                ->setRequires('')
                ->setProgId('')
                ->setUrlSrc("https://office.test/$name?<ui=UI_LLCC&>");
        }
        $app = (new App())->setName('Word')->setFavIconUrl('https://office.test/word.ico')->setActions($actions);
        $discovery = (new WOPIDiscovery())->setNetZones([
            (new NetZone())->setName(WOPIZone::EXTERNAL_HTTPS)->setApps([$app]),
        ]);

        $cache = new DiscoveryCache();
        $cache->storeInCache($baseUrl, $discovery);

        self::getContainer()->set(DiscoveryService::class, new DiscoveryService(
            new MockHttpClient(),
            self::getContainer()->get(SerializerInterface::class),
            $cache,
            new ParameterBag(['commsy.online_office.base_url' => $baseUrl]),
        ));
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }
}
