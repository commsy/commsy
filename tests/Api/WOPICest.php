<?php

namespace Tests\Api;

use App\Entity\Account;
use App\Entity\Files;
use App\Entity\Portal;
use App\Repository\FilesRepository;
use App\WOPI\Auth\AccessTokenGenerator;
use App\WOPI\Permission\WOPIPermission;
use Codeception\Util\HttpCode;
use DateTime;
use DateTimeImmutable;
use Tests\Support\ApiTester;

class WOPICest
{
    private Portal $portal;
    private Account $account;

    private AccessTokenGenerator $tokenGenerator;

    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');

        $this->portal = $I->havePortal('Some portal');
        $this->account = $I->haveAccount($this->portal->getAuthSources()->first(), 'username', 'mypassword');

        $this->tokenGenerator = $I->grabService(AccessTokenGenerator::class);
    }

    // tests
    public function lockFileRequestHeadersMissing(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->sendPostAsJson("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);

        $I->haveHttpHeader('X-WOPI-Override', 'LOCK');
        $I->sendPostAsJson("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
        $I->deleteHeader('X-WOPI-Override');

        $I->haveHttpHeader('X-WOPI-Lock', 'some');
        $I->sendPostAsJson("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
    }

    /**
     * If the file is currently unlocked, the host should lock the file and return 200 OK.
     */
    public function lockUnlockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'some');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    /**
     * If the file is currently locked and the X-WOPI-Lock value matches the lock currently on the file,
     * the host should treat the request as if it's a RefreshLock request.
     * That is, the host should refresh the lock timer and return 200 OK.
     */
    public function lockLockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    public function lockLockedFileInvalid(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'invalid lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', 'lock');
    }

    public function refreshLockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'REFRESH_LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    public function refreshUnlockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'REFRESH_LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', '');
    }

    public function refreshLockedFileInvalid(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'REFRESH_LOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'invalid lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', 'lock');
    }

    public function unlockLockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'UNLOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    public function unlockUnlockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'UNLOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', '');
    }

    public function unlockLockedFileInvalid(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'UNLOCK');
        $I->haveHttpHeader('X-WOPI-Lock', 'invalid lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', 'lock');
    }

    public function getFileContent(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);

        $filePath = $file->getFilepath();
        mkdir(dirname($filePath), 0777, true);
        $I->writeToFile($filePath, 'sample content');

        $I->sendGet("/v2/wopi/files/{$file->getFilesId()}/contents");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeBinaryResponseEquals(sha1_file($filePath));
    }

    public function putFileContentForbidden(ApiTester $I)
    {
        // insufficient view permission
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::VIEW);
        $I->amBearerAuthenticated($token);
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some content');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        // edit permission on another file
        $fileEdit = $I->haveFile($this->portal);
        $tokenEdit = $this->tokenGenerator->generateToken($this->account, $fileEdit, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($tokenEdit);
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some content');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function putFileContentHeadersMissing(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some content');
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's 0 bytes, the PutFile request should be considered valid and should proceed.
     */
    public function putFileContentUnlockedEmptyFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $filePath = $file->getFilepath();
        mkdir(dirname($filePath), 0777, true);
        $I->writeToFile($filePath, '');

        $I->haveHttpHeader('X-WOPI-Override', 'PUT');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some content');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's any value other than 0 bytes, or missing altogether, the host should respond with a 409 Conflict.
     */
    public function putFileContentUnlockedNonEmptyFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $filePath = $file->getFilepath();
        mkdir(dirname($filePath), 0777, true);
        $I->writeToFile($filePath, 'some content');

        $I->haveHttpHeader('X-WOPI-Override', 'PUT');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some other content');

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', '');
    }

    /**
     * When a host receives a PutFile request on a file that's not locked, the host checks the current size of the file.
     * If it's any value other than 0 bytes, or missing altogether, the host should respond with a 409 Conflict.
     */
    public function putFileContentUnlockedMissingFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal);
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('X-WOPI-Override', 'PUT');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some other content');

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', '');

        $I->sendPost("/v2/wopi/files/12345/contents", 'some other content');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
    }

    public function putFileContentLockedFileInvalid(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $filePath = $file->getFilepath();
        mkdir(dirname($filePath), 0777, true);
        $I->writeToFile($filePath, 'some content');

        $I->haveHttpHeader('X-WOPI-Override', 'PUT');
        $I->haveHttpHeader('X-WOPI-Lock', 'invalid lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some other content');

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeHttpHeader('X-WOPI-Lock', 'lock');
    }

    public function putFileContentLockedFile(ApiTester $I)
    {
        $file = $I->haveFile($this->portal, 'lock', new DateTimeImmutable());
        $token = $this->tokenGenerator->generateToken($this->account, $file, WOPIPermission::EDIT);
        $I->amBearerAuthenticated($token);

        $filePath = $file->getFilepath();
        mkdir(dirname($filePath), 0777, true);
        $I->writeToFile($filePath, 'some content');

        $I->haveHttpHeader('X-WOPI-Override', 'PUT');
        $I->haveHttpHeader('X-WOPI-Lock', 'lock');
        $I->sendPost("/v2/wopi/files/{$file->getFilesId()}/contents", 'some other content');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeHttpHeader('X-WOPI-Lock');
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
