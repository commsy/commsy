<?php

namespace App\Tests\Api;

use App\Entity\AuthSource;
use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class PortalCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function listPortalsFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $I->havePortal('Some portal');

        $I->sendGet('/portals');

        $I->seeResponseJsonMatchesJsonPath('$[0].id');
        $I->seeResponseJsonMatchesJsonPath('$[0].creationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].title');
        $I->seeResponseJsonMatchesJsonPath('$[0].descriptionGerman');
        $I->seeResponseJsonMatchesJsonPath('$[0].descriptionEnglish');
        $I->seeResponseJsonMatchesJsonPath('$[0].aGBEnabled');

        $I->seeResponseContainsJson([
            [
                'title' => 'Some portal',
            ],
        ]);
    }

    public function listPortalsReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->havePortal('Some portal');

        $I->sendGet('/portals');

        $I->seeResponseJsonMatchesJsonPath('$[0].id');
        $I->seeResponseJsonMatchesJsonPath('$[0].creationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].title');
        $I->seeResponseJsonMatchesJsonPath('$[0].descriptionGerman');
        $I->seeResponseJsonMatchesJsonPath('$[0].descriptionEnglish');
        $I->seeResponseJsonMatchesJsonPath('$[0].aGBEnabled');

        $I->seeResponseContainsJson([
            [
                'title' => 'Some portal',
            ],
        ]);
    }

    public function getPortalFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->seeResponseJsonMatchesJsonPath('$.creationDate');
        $I->seeResponseJsonMatchesJsonPath('$.modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$.title');
        $I->seeResponseJsonMatchesJsonPath('$.descriptionGerman');
        $I->seeResponseJsonMatchesJsonPath('$.descriptionEnglish');
        $I->seeResponseJsonMatchesJsonPath('$.aGBEnabled');

        $I->seeResponseContainsJson([
            'id' => $portal->getId(),
            'title' => 'Some portal',
        ]);
    }

    public function getPortalReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->seeResponseJsonMatchesJsonPath('$.creationDate');
        $I->seeResponseJsonMatchesJsonPath('$.modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$.title');
        $I->seeResponseJsonMatchesJsonPath('$.descriptionGerman');
        $I->seeResponseJsonMatchesJsonPath('$.descriptionEnglish');
        $I->seeResponseJsonMatchesJsonPath('$.aGBEnabled');

        $I->seeResponseContainsJson([
            'id' => $portal->getId(),
            'title' => 'Some portal',
        ]);
    }

    public function getPortalNotFound(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/portals/123');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function getPortalAuthSourcesFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/auth_sources');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'id' => 'integer',
            'title' => 'string',
            'description' => 'string|null',
            'enabled' => 'boolean',
            'type' => 'string',
        ]);

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->get(0);

        $I->seeResponseContainsJson([
            [
                'id' => $authSource->getId(),
                'title' => $authSource->getTitle(),
            ],
        ]);
    }

    public function getPortalAuthSourcesReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/auth_sources');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'id' => 'integer',
            'title' => 'string',
            'description' => 'string|null',
            'enabled' => 'boolean',
            'type' => 'string',
        ]);

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->get(0);

        $I->seeResponseContainsJson([
            [
                'id' => $authSource->getId(),
                'title' => $authSource->getTitle(),
            ],
        ]);
    }

    public function getPortalAnnouncementFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/announcement');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }

    public function getPortalAnnouncementReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/announcement');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }

    public function getPortalTermsFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/tou');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'de' => 'string|null',
            'en' => 'string|null',
        ]);
    }

    public function getPortalTermsReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/tou');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'de' => 'string|null',
            'en' => 'string|null',
        ]);
    }
}
