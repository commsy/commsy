<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Etherpad;

use App\Etherpad\EtherpadClient;
use App\Etherpad\MaterialPad;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * An Etherpad session is issued for a whole group, while the right to open
 * the editor is checked per material. The group therefore has to cover one
 * material and no more — a room-wide group handed a member with rights on a
 * single entry access to every pad in the room.
 */
final class MaterialPadTest extends TestCase
{
    /** @var list<string> */
    private array $requestedMappers = [];

    private function client(): EtherpadClient
    {
        $mock = new MockHttpClient(function (string $method, string $url): MockResponse {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $mapper = (string) ($query['groupMapper'] ?? '');
            $this->requestedMappers[] = $mapper;

            // Etherpad hands out a stable group per mapper.
            return new MockResponse(json_encode([
                'code' => 0,
                'message' => 'ok',
                'data' => ['groupID' => 'g.'.md5($mapper)],
            ], JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
        });

        return new EtherpadClient($mock, 'http://etherpad.test', 'k');
    }

    public function testTheGroupIsScopedToTheMaterial(): void
    {
        MaterialPad::locate($this->client(), 149);

        self::assertSame(['material-149'], $this->requestedMappers);
    }

    public function testTwoMaterialsInTheSameRoomDoNotShareAGroup(): void
    {
        $client = $this->client();

        $first = MaterialPad::locate($client, 149);
        $second = MaterialPad::locate($client, 150);

        self::assertNotSame($first->groupId, $second->groupId);
        self::assertNotSame($first->padId, $second->padId);
    }

    public function testThePadSitsInsideItsOwnGroup(): void
    {
        $pad = MaterialPad::locate($this->client(), 149);

        self::assertSame(
            EtherpadClient::padId($pad->groupId, MaterialPad::padName()),
            $pad->padId
        );
        self::assertStringStartsWith($pad->groupId.'$', $pad->padId);
    }

    public function testTheSameMaterialAlwaysResolvesToTheSamePad(): void
    {
        $client = $this->client();

        self::assertSame(
            MaterialPad::locate($client, 149)->padId,
            MaterialPad::locate($client, 149)->padId
        );
    }
}
