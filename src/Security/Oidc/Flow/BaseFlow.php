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

namespace App\Security\Oidc\Flow;

use App\Entity\AuthSourceOIDC;
use App\Security\Oidc\Discovery\ProviderMetadata;
use App\Security\Oidc\Request\ResponseType;
use App\Security\Oidc\Response\JWKSet;
use DateInterval;
use Exception;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Random\Randomizer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseFlow implements ProtocolFlow
{
    private const string NONE_SPACE = 'abcdefghijklmnopqrstuvwxyz0123456789';
    private const string SESSION_KEY_NONCE = 'oidc_nonce';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private HttpClientInterface $client,
        private RequestStack $requestStack,
    ) {
    }

    public function prepareAuthorizationRequest(
        string $clientIdentifier,
        int $portalId,
        ProviderMetadata $metadata,
        ResponseType $responseType,
    ): RedirectResponse
    {
        $nonce = $this->generateNonce();
        $this->storeNonce($nonce);

        $queryParameter = [
            'response_type' => $responseType->value,
            'client_id' => $clientIdentifier,
            'scope' => 'openid profile email',
            'redirect_uri' => $this->urlGenerator->generate('app_oidc_authoidccheck', [
                'context' => $portalId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'nonce' => $nonce,
        ];

        $queryString = http_build_query($queryParameter, '', '&', PHP_QUERY_RFC3986);
        return new RedirectResponse("{$metadata->getAuthorizationEndpoint()}?{$queryString}");
    }

    public function generateNonce(int $length = 12): string
    {
        $randomizer = new Randomizer();
        return $randomizer->getBytesFromString(self::NONE_SPACE, $length);
    }

    protected function storeNonce($nonce): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY_NONCE, $nonce);
    }

    protected function getStoredNonce(): string
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::SESSION_KEY_NONCE, '');
    }

    protected function verifyIdToken(
        string $idToken,
        AuthSourceOIDC $authSourceOIDC,
        ProviderMetadata $metadata,
        string $expectedNonce
    ): bool
    {
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($idToken);

        $validator = new Validator();

        $alg = $token->headers()->get('alg');
        if (!in_array($alg, $metadata->getIdTokenSigningAlgValuesSupported())) {
            return false;
        }

        // We only support RSASSA-PKCS1-v1_5 using SHA-256 for now
        if ($alg !== 'RS256') {
            throw new Exception();
        }

        // request jwk set
        //$jwkSet = $this->requestJwkSet($metadata);

        try {
            // verify that the claim "aud" matches the client identifier
            $validator->assert($token, new PermittedFor($authSourceOIDC->getClientIdentifier()));

            // verify signature
//            $validator->assert($token, new Signedwith(
//                new Sha256(),
//                InMemory::base64Encoded()
//            ));

            $validator->assert($token, new LooseValidAt(SystemClock::fromUTC(), new DateInterval('PT5M')));
            $validator->assert($token, new IssuedBy($metadata->getIssuer()));
            $validator->assert($token, new HasClaimWithValue('nonce', $expectedNonce));
        } catch (RequiredConstraintsViolated) {
            return false;
        }

        return true;
    }

    protected function requestJwkSet(ProviderMetadata $metadata): JWKSet
    {
        $response = $this->client->request('GET', $metadata->getJwksUri());

        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        $serializer = new Serializer(
            [new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter)],
            ['json' => new JsonEncoder()]
        );

        return $serializer->deserialize($response->getContent(), JWKSet::class, 'json');
    }
}
