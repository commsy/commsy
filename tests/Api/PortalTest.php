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

use App\Entity\AuthSource;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

class PortalTest extends AbstractApiTestCase
{
    public function testListPortalsFull(): void
    {
        PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/portals', [
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
                    'creationDate' => ['type' => 'string'],
                    'modificationDate' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'descriptionGerman' => ['type' => 'string'],
                    'descriptionEnglish' => ['type' => 'string'],
                    'aGBEnabled' => ['type' => 'boolean'],
                ],
            ],
        ]);

        $this->assertJsonContains([
            [
                'title' => 'Some portal',
            ],
        ]);
    }

    public function testListPortalsReadOnly(): void
    {
        PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/portals', [
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
                    'creationDate' => ['type' => 'string'],
                    'modificationDate' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'descriptionGerman' => ['type' => 'string'],
                    'descriptionEnglish' => ['type' => 'string'],
                    'aGBEnabled' => ['type' => 'boolean'],
                ],
            ],
        ]);

        $this->assertJsonContains([
            [
                'title' => 'Some portal',
            ],
        ]);
    }

    public function testGetPortalFull(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/portals/{$portal->getId()}", [
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
                'creationDate' => ['type' => 'string'],
                'modificationDate' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'descriptionGerman' => ['type' => 'string'],
                'descriptionEnglish' => ['type' => 'string'],
                'aGBEnabled' => ['type' => 'boolean'],
            ],
        ]);

        $this->assertJsonContains([
            'id' => $portal->getId(),
            'title' => 'Some portal',
        ]);
    }

    public function testGetPortalReadOnly(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/portals/{$portal->getId()}", [
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
                'creationDate' => ['type' => 'string'],
                'modificationDate' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'descriptionGerman' => ['type' => 'string'],
                'descriptionEnglish' => ['type' => 'string'],
                'aGBEnabled' => ['type' => 'boolean'],
            ],
        ]);

        $this->assertJsonContains([
            'id' => $portal->getId(),
            'title' => 'Some portal',
        ]);
    }

    public function testGetPortalNotFound(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/portals/123', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetPortalAuthSourcesFull(): void
    {
        /** @var AuthSource $authSource */
        $authSource = AuthSourceLocalFactory::createOne();
        $portal = PortalFactory::createOne(['authSources' => [$authSource], 'title' => 'Some portal']);

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/auth_sources", [
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

        $this->assertJsonContains([
            [
                'id' => $authSource->getId(),
                'title' => $authSource->getTitle(),
            ],
        ]);
    }

    public function testGetPortalAuthSourcesReadOnly(): void
    {
        /** @var AuthSource $authSource */
        $authSource = AuthSourceLocalFactory::createOne();
        $portal = PortalFactory::createOne(['authSources' => [$authSource], 'title' => 'Some portal']);

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/auth_sources", [
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

        $this->assertJsonContains([
            [
                'id' => $authSource->getId(),
                'title' => $authSource->getTitle(),
            ],
        ]);
    }

    public function testGetPortalAnnouncementFull(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/announcement", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }

    public function testGetPortalAnnouncementReadOnly(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/announcement", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }

    public function testGetPortalTermsFull(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/tou", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'de' => ['type' => ['string', 'null']],
                'en' => ['type' => ['string', 'null']],
            ],
        ]);
    }

    public function testGetPortalTermsReadOnly(): void
    {
        $portal = PortalFactory::createOne(['title' => 'Some portal']);

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/portals/{$portal->getId()}/tou", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'de' => ['type' => ['string', 'null']],
                'en' => ['type' => ['string', 'null']],
            ],
        ]);
    }
}
