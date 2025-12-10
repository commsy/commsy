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

namespace Tests\Api;

use App\Entity\Account;
use App\Repository\FilesRepository;
use App\WOPI\Auth\AccessTokenGenerator;
use App\WOPI\Permission\WOPIPermission;
use DateTimeImmutable;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\AccountFactory;
use Tests\Factory\FilesFactory;

class WOPITest extends AbstractApiTestCase
{
    private AccessTokenGenerator $tokenGenerator;

    private Account $account;

    private const string FILES_FOLDER = __DIR__ . '/../../files/tmp';

    public function setUp(): void
    {
        $this->tokenGenerator = static::getContainer()->get(AccessTokenGenerator::class);
        $this->account = AccountFactory::createOne();

        $filesystem = new Filesystem();
        if ($filesystem->exists(self::FILES_FOLDER)) {
            $filesystem->remove(self::FILES_FOLDER);
        }

        $filesystem->mkdir(self::FILES_FOLDER);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists(self::FILES_FOLDER)) {
            $filesystem->remove(self::FILES_FOLDER);
        }
    }

    public function testLockFileRequestHeadersMissing(): void
    {
        $file = FilesFactory::createOne();
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);

        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(500);

        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
            ],
        ]);
        $this->assertResponseStatusCodeSame(500);

        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Lock' => 'some',
            ],
        ]);
        $this->assertResponseStatusCodeSame(500);
    }

    public function testLockUnlockedFileForbidden(): void
    {
        $file = FilesFactory::createOne();
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
                'X-WOPI-Lock' => 'some',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    /**
     * If the file is currently unlocked, the host should lock the file and return 200 OK.
     */
    public function testLockUnlockedFile(): void
    {
        $file = FilesFactory::createOne();
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
                'X-WOPI-Lock' => 'some',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    /**
     * If the file is currently locked and the X-WOPI-Lock value matches the lock currently on the file,
     * the host should treat the request as if it's a RefreshLock request.
     * That is, the host should refresh the lock timer and return 200 OK.
     */
    public function testLockLockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
                'X-WOPI-Lock' => 'lock',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    public function testLockLockedFileInvalid(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
                'X-WOPI-Lock' => 'invalid lock',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', 'lock');
    }

    public function testRefreshLockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'REFRESH_LOCK',
                'X-WOPI-Lock' => 'lock',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    public function testRefreshUnlockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => null]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'REFRESH_LOCK',
                'X-WOPI-Lock' => 'lock',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', '');
    }

    public function testRefreshLockedFileInvalid(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'REFRESH_LOCK',
                'X-WOPI-Lock' => 'invalid lock',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', 'lock');
    }

    public function testUnlockLockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'UNLOCK',
                'X-WOPI-Lock' => 'lock',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    public function testUnlockUnlockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => null]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'UNLOCK',
                'X-WOPI-Lock' => 'lock',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', '');
    }

    public function testUnlockLockedFileInvalid(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'UNLOCK',
                'X-WOPI-Lock' => 'invalid lock',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', 'lock');
    }

    public function testUnlockAndRelockLockedFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable()]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'LOCK',
                'X-WOPI-Oldlock' => 'lock',
                'X-WOPI-Lock' => 'new lock',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    public function testGetFileContent(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', 'sample content');

        $file = FilesFactory::createOne(['lockingId' => null, 'filepath' => 'files/tmp/test.txt']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $client = static::createClient();
        $client->request('GET', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse()->getBrowserKitResponse()->getContent();
        $this->assertEquals(file_get_contents(self::FILES_FOLDER . '/test.txt'), $response);
    }

    public function testPutFileContentForbidden(): void
    {
        // insufficient view permission
        $file = FilesFactory::createOne(['lockingId' => null]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseStatusCodeSame(403);

        // edit permission on another file
        $anotherFile = FilesFactory::new(['lockingId' => null])->create();
        $token = $this->tokenGenerator->generateToken($this->account, $anotherFile, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPutFileContentHeadersMissing(): void
    {
        $file = FilesFactory::createOne(['lockingId' => null]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseStatusCodeSame(500);
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's 0 bytes, the PutFile request should be considered valid and should proceed.
     */
    public function testPutFileContentUnlockedEmptyFile(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', '');

        $file = FilesFactory::createOne(['lockingId' => null, 'filepath' => 'files/tmp/test.txt']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'PUT',
                'X-WOPI-Lock' => 'lock',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');

        $reloadedFile = FilesFactory::find($file->getFilesId());
        $this->assertSame('lock', $reloadedFile->getLockingId());
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's any value other than 0 bytes, or missing altogether, the host should respond with a 409 Conflict.
     */
    public function testPutFileContentUnlockedNonEmptyFile(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', 'some content');

        $file = FilesFactory::createOne(['lockingId' => null, 'filepath' => 'files/tmp/test.txt']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'PUT',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', '');
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's any value other than 0 bytes, or missing altogether, the host should respond with a 409 Conflict.
     */
    public function testPutFileContentUnlockedMissingFile(): void
    {
        $file = FilesFactory::createOne(['lockingId' => null]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();

        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'PUT'
            ],
            'body' => 'some content',
        ]);
        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', '');

        $client->request('POST', "/api/v2/wopi/files/12345/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => 'some content',
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    public function testPutFileContentLockedFileInvalid(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', 'some content');

        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable(), 'filepath' => 'files/tmp/test.txt']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'PUT',
                'X-WOPI-Lock' => 'invalid lock',
            ],
            'body' => 'some other content',
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('X-WOPI-Lock', 'lock');
    }

    public function testPutFileContentLockedFile(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', 'some content');

        $file = FilesFactory::createOne(['lockingId' => 'lock', 'lockingDate' => new DateTimeImmutable(), 'filepath' => 'files/tmp/test.txt']);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $client = static::createClient();
        $client->request('POST', "/api/v2/wopi/files/{$file->getFilesId()}/contents", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-WOPI-Override' => 'PUT',
                'X-WOPI-Lock' => 'lock',
            ],
            'body' => 'some other content',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-WOPI-Lock');
    }

    /**
     * test:
     * - CheckFileInfo
     * - proof key validation
     * - controller test for host page (check if user has permission to view / edit file -> token generation)
     * - lock expiration???
     * - test auth with query token
     */
}
