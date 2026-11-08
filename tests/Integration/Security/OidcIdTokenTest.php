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


declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Entity\AuthSourceOIDC;
use App\Security\Oidc\Discovery\ProviderMetadata;
use App\Security\Oidc\Flow\AuthorizationCodeFlow;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The ID token of a sign-in through OpenID Connect is checked for its
 * audience, its time of validity, its issuer and the nonce of the request.
 */
final class OidcIdTokenTest extends KernelTestCase
{
    public function testAValidTokenPasses(): void
    {
        self::assertTrue($this->verify($this->token()));
    }

    public function testAnExpiredTokenFails(): void
    {
        self::assertFalse($this->verify($this->token(['exp' => time() - 3600, 'iat' => time() - 7200, 'nbf' => time() - 7200])));
    }

    public function testATokenForAnotherClientFails(): void
    {
        self::assertFalse($this->verify($this->token(['aud' => 'someone-else'])));
    }

    public function testATokenWithAnotherNonceFails(): void
    {
        self::assertFalse($this->verify($this->token(['nonce' => 'other'])));
    }

    private function verify(string $idToken): bool
    {
        self::bootKernel();
        $flow = self::getContainer()->get(AuthorizationCodeFlow::class);

        $authSource = new AuthSourceOIDC();
        $authSource->setClientIdentifier('commsy');
        $metadata = (new ProviderMetadata())
            ->setIssuer('https://idp.example')
            ->setIdTokenSigningAlgValuesSupported(['RS256']);

        return (new ReflectionMethod($flow, 'verifyIdToken'))->invoke($flow, $idToken, $authSource, $metadata, 'nonce-1');
    }

    /**
     * An ID token as the token endpoint returns it; its signature is not
     * checked, the token comes straight from the provider.
     *
     * @param array<string, mixed> $claims
     */
    private function token(array $claims = []): string
    {
        $encode = static fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');
        $claims += [
            'iss' => 'https://idp.example',
            'aud' => 'commsy',
            'sub' => 'someone',
            'nonce' => 'nonce-1',
            'iat' => time() - 10,
            'nbf' => time() - 10,
            'exp' => time() + 300,
        ];

        return $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims).'.c2lnbmF0dXJl';
    }
}
