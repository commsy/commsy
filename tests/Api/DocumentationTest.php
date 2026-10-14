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

/**
 * Smoke test for the public API documentation endpoint (/api/v2/doc).
 *
 * Guards against regressions like routing to a removed controller service,
 * which previously made the endpoint return HTTP 500. The documentation must
 * be reachable without authentication (PUBLIC_ACCESS).
 */
class DocumentationTest extends AbstractApiTestCase
{
    public function testOpenApiJsonIsPubliclyAccessible(): void
    {
        $response = static::createClient()->request('GET', '/api/v2/doc', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        self::assertSame('3.1.0', $data['openapi']);
        self::assertSame('CommSy', $data['info']['title'] ?? null);
        self::assertNotEmpty($data['paths'], 'OpenAPI document must expose paths');
    }

    public function testSwaggerUiHtmlIsPubliclyAccessible(): void
    {
        static::createClient()->request('GET', '/api/v2/doc', [
            'headers' => ['Accept' => 'text/html'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * The interface describes an entry as it comes out, not as the entity holds
     * it: what the normalizers add is there, and a person is what the person
     * normalizer makes of the user row.
     */
    public function testARubricEntryIsDescribedAsItComesOut(): void
    {
        $schemas = static::createClient()->request('GET', '/api/v2/doc', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray()['components']['schemas'];

        $announcement = $schemas['Announcement-export'];
        foreach (['categories', 'hashtags', 'files', 'annotations'] as $field) {
            self::assertArrayHasKey($field, $announcement['properties']);
        }
        self::assertFalse($announcement['additionalProperties']);
        self::assertSame(['id', 'name', 'email', 'roomStatus'], array_keys($schemas['User-export']['properties']));
    }
}
