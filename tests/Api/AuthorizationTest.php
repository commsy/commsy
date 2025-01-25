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

class AuthorizationTest extends AbstractApiTestCase
{
    public function testAccessDenied(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v2/portals', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertMatchesJsonSchema([
            'code' => 'integer',
            'message' => 'string',
        ]);
        $this->assertJsonContains([
            'message' => 'JWT Token not found',
        ]);
    }

    public function testInvalidCredentials(): void
    {
        static::createClient()->request('POST', '/api/v2/login_check', [
            'json' => [
                'username' => 'unknown',
                'password' => 'secret',
            ]
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertMatchesJsonSchema([
            'code' => 'integer',
            'message' => 'string',
        ]);
    }

    public function testValidCredentials(): void
    {
        static::createClient()->request('POST', '/api/v2/login_check', [
            'json' => [
                'username' => 'api_write',
                'password' => 'apiwrite',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'token' => 'string',
        ]);
    }
}
