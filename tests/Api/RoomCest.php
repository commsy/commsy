<?php

namespace Tests\Api;

use App\Entity\AuthSource;
use Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

class RoomCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function listRoomsFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');
        $room = $I->haveRoom('Some room', $portal);

        $I->sendGet('/rooms');

        $I->seeResponseMatchesJsonType([
            'itemId' => 'integer',
            'creationDate' => 'string',
            'modificationDate' => 'string',
            'title' => 'string',
            'type' => 'string',
            'roomDescription' => 'string|null',
        ]);

        $I->seeResponseContainsJson([
            [
                'itemId' => $room->getItemId(),
                'title' => $room->getTitle(),
                'type' => $room->getType(),
            ],
        ]);
    }

    public function listRoomsReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');
        $room = $I->haveRoom('Some room', $portal);

        $I->sendGet('/rooms');

        $I->seeResponseMatchesJsonType([
            'itemId' => 'integer',
            'creationDate' => 'string',
            'modificationDate' => 'string',
            'title' => 'string',
            'type' => 'string',
            'roomDescription' => 'string|null',
        ]);

        $I->seeResponseContainsJson([
            [
                'itemId' => $room->getItemId(),
                'title' => $room->getTitle(),
                'type' => $room->getType(),
            ],
        ]);
    }

    public function getRoomFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $portal = $I->havePortal('Some portal');
        $room = $I->haveRoom('Some room', $portal);

        $I->sendGet('/rooms/' . $room->getItemId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'itemId' => 'integer',
            'creationDate' => 'string',
            'modificationDate' => 'string',
            'title' => 'string',
            'type' => 'string',
            'roomDescription' => 'string|null',
        ]);

        $I->seeResponseContainsJson([
            'itemId' => $room->getItemId(),
            'title' => $room->getTitle(),
            'type' => $room->getType(),
        ]);
    }

    public function getRoomReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');
        $room = $I->haveRoom('Some room', $portal);

        $I->sendGet('/rooms/' . $room->getItemId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'itemId' => 'integer',
            'creationDate' => 'string',
            'modificationDate' => 'string',
            'title' => 'string',
            'type' => 'string',
            'roomDescription' => 'string|null',
        ]);

        $I->seeResponseContainsJson([
            'itemId' => $room->getItemId(),
            'title' => $room->getTitle(),
            'type' => $room->getType(),
        ]);
    }

    public function getRoomNotFound(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/rooms/123');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }
}
