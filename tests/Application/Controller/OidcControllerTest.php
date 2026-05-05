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

namespace Tests\Application\Controller;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AuthSourceOIDCFactory;
use Tests\Factory\PortalFactory;

class OidcControllerTest extends AbstractApplicationTestCase
{
    /**
     * Replaces the autowired HttpClientInterface with a MockHttpClient
     * pre-loaded with the given responses (consumed in FIFO order).
     */
    private function stubHttpClient(MockResponse ...$responses): MockHttpClient
    {
        $mock = new MockHttpClient($responses);
        self::getContainer()->set(HttpClientInterface::class, $mock);

        return $mock;
    }

    private function oidcMetadataResponse(string $issuer = 'https://issuer.test'): MockResponse
    {
        return new MockResponse(json_encode([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/auth',
            'token_endpoint' => $issuer . '/token',
            'userinfo_endpoint' => $issuer . '/userinfo',
            'jwks_uri' => $issuer . '/jwks',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ]));
    }

    public function testAuthOidcInitRedirectsToAuthorizationEndpoint(): void
    {
        $portal = PortalFactory::createOne();
        AuthSourceOIDCFactory::createOne([
            'portal' => $portal,
            'enabled' => true,
            'issuer' => 'https://issuer.test',
            'clientIdentifier' => 'client-abc',
        ]);

        $this->stubHttpClient($this->oidcMetadataResponse('https://issuer.test'));

        $this->client->request('GET', "/login/{$portal->getId()}/auth/oidc");

        // The authorization-code flow builds a redirect to the IdP's
        // authorization endpoint with client_id, redirect_uri, scope etc.
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('https://issuer.test/auth', $location);
        $this->assertStringContainsString('client_id=client-abc', $location);
        $this->assertStringContainsString('response_type=code', $location);
    }

    public function testAuthOidcInitDeniedWhenNoOidcSourceConfigured(): void
    {
        // Portal without an OIDC auth source — controller throws
        // createAccessDeniedException, the CommSy access-denied handler
        // converts that to a redirect.
        $portal = PortalFactory::createOne();

        // mock client is set but not consumed since the controller short-
        // circuits before fetching metadata
        $this->stubHttpClient();

        $this->client->request('GET', "/login/{$portal->getId()}/auth/oidc");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testAuthOidcInitDeniedWhenOidcSourceDisabled(): void
    {
        $portal = PortalFactory::createOne();
        AuthSourceOIDCFactory::createOne([
            'portal' => $portal,
            'enabled' => false,
        ]);

        $this->stubHttpClient();

        $this->client->request('GET', "/login/{$portal->getId()}/auth/oidc");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testAuthOidcCheckRouteIsRegistered(): void
    {
        // /login/{context}/auth/oidc/check is the firewall callback URL.
        // The controller throws by design — it is never reached because
        // the firewall intercepts the request first. Hitting it directly
        // (with no firewall match) surfaces a 500.
        $this->client->request('GET', '/login/server/auth/oidc/check');
        $this->assertResponseStatusCodeSame(500);
    }
}
