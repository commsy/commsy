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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApiTestCase extends ApiTestCase
{
    public function setUp(): void
    {
        ApiTestCase::$alwaysBootKernel = false;
        self::bootKernel();

        /**
         * @see https://github.com/api-platform/core/issues/5923 and
         * https://github.com/lexik/LexikJWTAuthenticationBundle/issues/1237
         */
        (new Request())->setFormat('json', ['application/json']);
    }

    protected function createClientWithCredentials($token = null): Client
    {
        $token = $token ?: $this->getToken();

        return static::createClient([], [
            'auth_bearer' => $token,
        ]);
    }

    protected function getToken($body = []): string
    {
        $response = static::createClient()->request('POST', '/api/v2/login_check', [
            'json' => $body ?: [
                'username' => 'api_write',
                'password' => 'apiwrite',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        return $data['token'];
    }

    protected function getReadOnlyToken(): string
    {
        return $this->getToken([
            'username' => 'api_read',
            'password' => 'apiread',
        ]);
    }
}
