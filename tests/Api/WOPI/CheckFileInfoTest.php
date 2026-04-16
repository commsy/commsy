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

namespace Tests\Api\WOPI;

use App\Entity\Account;
use App\WOPI\Auth\AccessTokenGenerator;
use App\WOPI\Permission\WOPIPermission;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Api\AbstractApiTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\FilesFactory;

class CheckFileInfoTest extends AbstractApiTestCase
{
    private AccessTokenGenerator $tokenGenerator;

    private Account $account;

    private const string FILES_FOLDER = __DIR__ . '/../../../files/tmp';

    public function setUp(): void
    {
        parent::setUp();

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

    public function testCheckFileInfoReturnsResponseForViewer(): void
    {
        file_put_contents(self::FILES_FOLDER . '/test.txt', 'sample content');
        $file = FilesFactory::createOne([
            'filepath' => 'files/tmp/test.txt',
            'size' => 14,
            'creatorId' => (string) $this->account->getId(),
        ]);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);

        $client = static::createClient();
        $client->request('GET', "/api/v2/wopi/files/{$file->getFilesId()}", [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testCheckFileInfoReturns404ForMissingFile(): void
    {
        // generate a token against an existing file, but then request a
        // non-existing file id — the controller must return 404 before
        // delegating to the WOPIVoter.
        $file = FilesFactory::createOne();
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);

        $client = static::createClient();
        $client->request('GET', '/api/v2/wopi/files/99999999', [
            'auth_bearer' => $token,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
