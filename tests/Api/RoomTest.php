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

use Tests\Factory\RoomFactory;

class RoomTest extends AbstractApiTestCase
{
    public function testListRoomsFull(): void
    {
        $room = RoomFactory::createOne();

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
                    'type' => 'string',
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
        $room = RoomFactory::createOne();

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
                    'type' => 'string',
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
        $room = RoomFactory::createOne();

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
                'type' => 'string',
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
        $room = RoomFactory::createOne();

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
                'type' => 'string',
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
        $client->request('GET', '/api/v2/rooms/123', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
