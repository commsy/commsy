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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\AuthSourceShibbolethFactory;
use Tests\Factory\PortalFactory;

class AuthSourceTest extends AbstractApiTestCase
{
    public function testListAuthSourcesFull(): void
    {
        AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/auth_sources', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'enabled' => ['type' => 'boolean'],
                    'type' => ['type' => 'string'],
                ],
            ],
        ]);
    }

    public function testListAuthSourcesReadOnly(): void
    {
        AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/auth_sources', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'enabled' => ['type' => 'boolean'],
                    'type' => ['type' => 'string'],
                ],
            ],
        ]);
    }

    public function testGetAuthSourceFull(): void
    {
        $authSource = AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/auth_sources/{$authSource->getId()}", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'enabled' => ['type' => 'boolean'],
                'type' => ['type' => 'string'],
            ],
        ]);
    }

    public function testGetAuthSourceReadOnly(): void
    {
        $authSource = AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/auth_sources/{$authSource->getId()}", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'enabled' => ['type' => 'boolean'],
                'type' => ['type' => 'string'],
            ],
        ]);
    }

    public function getAuthSourceNotFound(): void
    {
        AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/auth_sources/123', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetAuthSourceLoginURLFull(): void
    {
        $authSource = AuthSourceLocalFactory::createOne();

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/auth_sources/{$authSource->getId()}/login_url", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'url' => 'string|null',
        ]);
        $this->assertJsonContains([
            'url' => null,
        ]);

        $authSourceShib = AuthSourceShibbolethFactory::createOne();
        $portal = PortalFactory::createOne(['authSources' => [$authSourceShib]]);
        $client->request('GET', "/api/v2/auth_sources/{$authSourceShib->getId()}/login_url", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'url' => 'string|null',
        ]);

        /** @var UrlGeneratorInterface $router */
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->assertJsonContains([
            'url' => $urlGenerator->generate('app_shibboleth_authshibbolethinit', [
                'portalId' => $portal->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
