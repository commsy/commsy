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

use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomStory::class)]
class RoomTest extends AbstractApiTestCase
{
    public function testListRoomsFull(): void
    {
        $room = RoomStory::get('room');

        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/rooms', [
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
                    'itemId' => 'integer',
                    'creationDate' => 'string',
                    'modificationDate' => 'string',
                    'title' => 'string',
                    'type' => [
                        'enum' => ['project', 'community', 'grouproom'],
                    ],
                    'roomDescription' => 'string',
                ],
            ],
        ]);

        $this->assertJsonContains([
            [
                'itemId' => $room->getItemId(),
                'title' => $room->getTitle(),
                'type' => $room->getType(),
            ],
        ]);
    }

    public function testListRoomsReadOnly(): void
    {
        $room = RoomStory::get('room');

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', '/api/v2/rooms', [
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
                    'itemId' => 'integer',
                    'creationDate' => 'string',
                    'modificationDate' => 'string',
                    'title' => 'string',
                    'type' => [
                        'enum' => ['project', 'community', 'grouproom'],
                    ],
                    'roomDescription' => 'string',
                ],
            ],
        ]);

        $this->assertJsonContains([
            [
                'itemId' => $room->getItemId(),
                'title' => $room->getTitle(),
                'type' => $room->getType(),
            ],
        ]);
    }

    public function testGetRoomFull(): void
    {
        $room = RoomStory::get('room');

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/rooms/{$room->getItemId()}", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'itemId' => 'integer',
                'creationDate' => 'string',
                'modificationDate' => 'string',
                'title' => 'string',
                'type' => [
                    'enum' => ['project', 'community', 'grouproom'],
                ],
                'roomDescription' => 'string',
            ],
        ]);

        $this->assertJsonContains([
            'itemId' => $room->getItemId(),
            'title' => $room->getTitle(),
            'type' => $room->getType(),
        ]);
    }

    public function testGetRoomReadOnly(): void
    {
        $room = RoomStory::get('room');

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/rooms/{$room->getItemId()}", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'object',
            'properties' => [
                'itemId' => 'integer',
                'creationDate' => 'string',
                'modificationDate' => 'string',
                'title' => 'string',
                'type' => [
                    'enum' => ['project', 'community', 'grouproom'],
                ],
                'roomDescription' => 'string',
            ],
        ]);

        $this->assertJsonContains([
            'itemId' => $room->getItemId(),
            'title' => $room->getTitle(),
            'type' => $room->getType(),
        ]);
    }

    public function testGetRoomNotFound(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $nonExistingId = PHP_INT_MAX;
        $client->request('GET', "/api/v2/rooms/$nonExistingId", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPrivateRoomIsNotInCollection(): void
    {
        $portal = PortalFactory::createOne();
        RoomFactory::createOne([
            'type' => 'privateroom',
            'portal' => $portal,
        ]);

        $client = $this->createClientWithCredentials();
        $client->request('GET', '/api/v2/rooms', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // We do not iterate each result here, but using the schema to check the structure of the response.
        $this->assertMatchesJsonSchema([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'enum' => ['project', 'community', 'grouproom'],
                    ]
                ]
            ]
        ]);
    }

    public function testGetPrivateRoomReturnsNotFound(): void
    {
        $portal = PortalFactory::createOne();
        $privateRoom = RoomFactory::createOne([
            'type' => 'privateroom',
            'portal' => $portal,
        ]);

        $client = $this->createClientWithCredentials();
        $client->request('GET', "/api/v2/rooms/{$privateRoom->getItemId()}", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
