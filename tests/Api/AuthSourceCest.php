<?php

namespace App\Tests\Api;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use App\Tests\ApiTester;
use Codeception\Util\HttpCode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthSourceCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function listAuthSourcesFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/auth_sources');

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
                'enabled' => true,
                'type' => 'local',
            ],
        ]);
    }

    public function listAuthSourcesReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/auth_sources');

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
                'enabled' => true,
                'type' => 'local',
            ],
        ]);
    }

    public function getAuthSourceFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->first();

        $I->sendGet('/auth_sources/' . $authSource->getId());

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
            'id' => $authSource->getId(),
            'title' => $authSource->getTitle(),
            'enabled' => true,
            'type' => 'local',
        ]);
    }

    public function getAuthSourceReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->first();

        $I->sendGet('/auth_sources/' . $authSource->getId());

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
            'id' => $authSource->getId(),
            'title' => $authSource->getTitle(),
            'enabled' => true,
            'type' => 'local',
        ]);
    }

    public function getAuthSourceNotFound(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->first();

        $I->sendGet('/auth_sources/123');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function getAuthSourceLoginURLFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->first();

        $I->sendGet('/auth_sources/' . $authSource->getId() . '/login_url');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'url' => 'string|null',
        ]);

        $I->seeResponseContainsJson([
            'url' => null,
        ]);

        $portal = $I->havePortal('Some portal', new AuthSourceShibboleth());

        /** @var AuthSource $authSource */
        $authSource = $portal->getAuthSources()->first();

        $I->sendGet('/auth_sources/' . $authSource->getId() . '/login_url');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'url' => 'string|null',
        ]);

        /** @var RouterInterface $router */
        $router = $I->grabService(UrlGeneratorInterface::class);
        $I->seeResponseContainsJson([
            'url' => $router->generate('app_shibboleth_authshibbolethinit', [
                'portalId' => $portal->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
