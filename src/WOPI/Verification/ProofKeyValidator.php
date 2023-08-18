<?php

namespace App\WOPI\Verification;

use App\WOPI\Discovery\DiscoveryService;
use DateTimeImmutable;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

final readonly class ProofKeyValidator
{
    private const MULTIPLIER = 1e7;
    private const OFFSET = 621355968e9;

    public function __construct(
        private DiscoveryService $discoveryService,
        private RequestStack $requestStack,
        private ContainerBagInterface $params
    ) {
    }

    public function isValid(): bool
    {
        if (!$this->params->get('commsy.online_office.proofkey_validation')) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        // X-WOPI-TimeStamp must not be older than 20 minutes
        $timeStamp = $request->headers->get('X-WOPI-TimeStamp');
        if (!$timeStamp) {
            return false;
        }

        try {
            $date = DateTimeImmutable::createFromFormat(
                'U',
                (string) ((int) (((float) $timeStamp - self::OFFSET) / self::MULTIPLIER))
            );
            if ($date < new DateTimeImmutable('-20 minutes')) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        $accessToken = $request->query->get('access_token');
        if (!$accessToken) {
            return false;
        }

        $url = $request->getUri();

        $expected = sprintf(
            '%s%s%s%s%s%s',
            pack('N', strlen($accessToken)),
            $accessToken,
            pack('N', strlen($url)),
            strtoupper($url),
            pack('N', 8),
            pack('J', $timeStamp)
        );

        $discovery = $this->discoveryService->getWOPIDiscovery();
        $proofKey = $discovery->getProofKey();

        $key = $proofKey->getValue();
        $keyOld = $proofKey->getOldValue();
        $xWOPIProof = $request->headers->get('X-WOPI-Proof');
        $xWOPIProofOld = $request->headers->get('X-WOPI-ProofOld');

        if (!$xWOPIProof || !$xWOPIProofOld) {
            return false;
        }

        return $this->verify($expected, $xWOPIProof, $key) ||
            $this->verify($expected, $xWOPIProofOld, $key) ||
            $this->verify($expected, $xWOPIProof, $keyOld);
    }

    private function verify(string $expected, string $proof, string $key): bool
    {
        try {
            /** @var RSA $key */
            $key = PublicKeyLoader::loadPublicKey($key);
        } catch (Throwable) {
            return false;
        }

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $key
            ->withHash('sha256')
            ->withPadding(RSA::SIGNATURE_RELAXED_PKCS1)
            ->verify($expected, (string) base64_decode($proof, true));
    }
}
