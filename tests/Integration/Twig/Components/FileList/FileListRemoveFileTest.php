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

namespace Tests\Integration\Twig\Components\FileList;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Tests\Factory\FilesFactory;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Removing a file in the file list takes it off the shown version of the
 * entry only. Versions share their files, so the others keep it.
 */
#[WithStory(RoomWithMemberStory::class)]
final class FileListRemoveFileTest extends WebTestCase
{
    use Factories;
    use InteractsWithLiveComponents;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    public function testRemovingAFileTakesItOffTheShownVersionOnly(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $owner */
        $owner = RoomWithMemberStory::get('roomUser');
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');

        $materialId = $this->createMaterialWithTwoVersions($room, $owner);
        $shared = FilesFactory::createOne(['contextId' => $room->getItemId()])->getFilesId();
        $this->linkFile($shared, $materialId, 0);
        $this->linkFile($shared, $materialId, 1);

        $this->logIn($account, (string) $account->getPlainPassword(), $room);

        $component = $this->createLiveComponent(
            name: 'FileList:FileList',
            data: ['itemId' => $materialId, 'versionId' => 1],
            client: $this->client,
        );
        $this->mount($component);
        $component->call('removeFile', ['fileId' => $shared]);

        self::assertNotNull($this->linkDeletionDate($shared, $materialId, 1));
        self::assertNull($this->linkDeletionDate($shared, $materialId, 0), 'the older version keeps the file');
        self::assertNull($this->connection()->fetchOne('SELECT deletion_date FROM files WHERE files_id = ?', [$shared]));
    }

    /**
     * The first render mounts the component in this process; its upload
     * form needs a session for the CSRF token. The pushed request is taken
     * off again so it does not pose as the main request afterwards.
     */
    private function mount(TestLiveComponent $component): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($request);
        try {
            $component->render();
        } finally {
            $requestStack->pop();
        }
    }

    /**
     * A real form login plus a visit to the room: the component endpoint
     * carries no room id, the legacy context comes from the session.
     */
    private function logIn(Account $account, string $password, Room $room): void
    {
        $this->client->request('GET', "/login/{$account->getPortal()?->getId()}");
        $this->client->submitForm('login_local', [
            'email' => $account->getUsername(),
            'password' => $password,
        ]);
        $this->client->followRedirect();
        $this->client->request('GET', "/room/{$room->getItemId()}");
    }

    private function createMaterialWithTwoVersions(Room $room, User $creator): int
    {
        $materialId = (int) MaterialFactory::createOne(['room' => $room, 'creator' => $creator])->getItemId();

        $this->connection()->executeStatement(
            'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, modifier_id, modification_date, title, extras)
                SELECT item_id, 1, context_id, creator_id, creation_date, modifier_id, modification_date, title, extras
                FROM materials WHERE item_id = ? AND version_id = 0',
            [$materialId]
        );

        return $materialId;
    }

    private function linkFile(int $fileId, int $itemId, int $versionId): void
    {
        $this->connection()->executeStatement(
            'INSERT INTO item_link_file (file_id, item_iid, item_vid) VALUES (?, ?, ?)',
            [$fileId, $itemId, $versionId]
        );
    }

    private function linkDeletionDate(int $fileId, int $itemId, int $versionId): ?string
    {
        $value = $this->connection()->fetchOne(
            'SELECT deletion_date FROM item_link_file WHERE file_id = ? AND item_iid = ? AND item_vid = ?',
            [$fileId, $itemId, $versionId]
        );

        return false === $value ? null : $value;
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }
}
