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

class ServerTest extends AbstractApiTestCase
{
    public function testListServersFull(): void
    {
        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/servers', [
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
                    'id' => 'integer',
                ],
            ],
        ]);
    }

    public function testListServersReadOnly(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/servers', [
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
                    'id' => 'integer',
                ],
            ],
        ]);
    }

    public function testGetServerFull(): void
    {
        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/servers/99', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'id' => 'integer',
            ],
        ]);
    }

    public function testGetServerFullReadOnly(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/servers/99', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'id' => 'integer',
            ],
        ]);
    }

    public function testGetServerNotFound(): void
    {
        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/servers/123', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function testGetServerAnnouncementFull(): void
    {
        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/servers/99/announcement', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'enabled' => 'boolean',
                'title' => 'string',
                'severity' => 'string',
                'text' => 'string',
            ],
        ]);
    }

    public function testGetServerAnnouncementReadOnly(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/servers/99/announcement', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'enabled' => 'boolean',
                'title' => 'string',
                'severity' => 'string',
                'text' => 'string',
            ],
        ]);
    }
}
