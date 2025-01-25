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

class MetricTest extends AbstractApiTestCase
{
    public function testGetUnauthorized(): void
    {
        static::createClient()->request('GET', '/api/metrics');
        $this->assertResponseStatusCodeSame(401);

        static::createClient()->request('GET', '/api/metrics', [
            'auth_basic' => ['commsy', 'wrong'],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetAuthorized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/metrics', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('commsy:metricssecret'),
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('php_info', $client->getResponse()->getContent());
    }
}
