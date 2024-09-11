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
use App\Security\Oidc\Discovery\MetadataReader;
use App\Security\Oidc\Discovery\ProviderMetadata;
use App\Security\Oidc\Response\AccessTokenResponse;
use Exception;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthorizationCodeFlow extends BaseFlow
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly HttpClientInterface $client,
        private readonly RequestStack $requestStack,
        private readonly MetadataReader $metadataReader,
    ) {
        parent::__construct($this->urlGenerator, $this->client, $this->requestStack);
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
    public function authenticate(Request $request, AuthSourceOIDC $authSource): ?Token
    {
        $code = $request->query->get('code');
        $metadata = $this->metadataReader->fetchRemoteConfiguration($authSource->getIssuer());

        $tokenResponse = $this->requestAccessToken(
            $code,
            $authSource->getClientIdentifier(),
            $authSource->getClientSecret(),
            $authSource->getPortal()->getId(),
            $metadata
        );

        if ($this->verifyIdToken($tokenResponse->getIdToken(), $authSource, $metadata, $this->getStoredNonce())) {
            $parser = new Parser(new JoseEncoder());
            return $parser->parse($tokenResponse->getIdToken());
        }

        return null;
    }

    protected function requestAccessToken(
        string $code,
        string $clientIdentifier,
        string $clientSecret,
        int $portalId,
        ProviderMetadata $metadata
    ): AccessTokenResponse
    {
        // We only support basic auth for now
        if (!in_array('client_secret_basic', $metadata->getTokenEndpointAuthMethodsSupported())) {
            throw new Exception();
        }

        $response = $this->client->request('POST', $metadata->getTokenEndpoint(), [
            'auth_basic' => [$clientIdentifier, $clientSecret],
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->urlGenerator->generate('app_oidc_authoidccheck', [
                    'context' => $portalId,
                ]),
            ]
        ]);

        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        $serializer = new Serializer(
            [new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter), new ArrayDenormalizer()],
            ['json' => new JsonEncoder()]
        );

        return $serializer->deserialize($response->getContent(), AccessTokenResponse::class, 'json');
    }
}
