<?php

namespace App\WOPI\Verification;

use App\WOPI\Discovery\DiscoveryService;
use DateTimeImmutable;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

final readonly class ProofKeyValidator
{
    public const MULTIPLIER = 1e7;
    public const OFFSET = 621355968e9;

    public function __construct(
        private DiscoveryService $discoveryService,
        private ContainerBagInterface $params
    ) {
    }

    public function isRequestValid(Request $request): bool
    {
        return $this->isValid(
            $request->query->get('access_token'),
            $request->headers->get('X-WOPI-TimeStamp'),
            $request->getUri(),
            $request->headers->get('X-WOPI-Proof'),
            $request->headers->get('X-WOPI-ProofOld')
        );
    }

    public function isValid(
        string $accessToken,
        string $timeStamp,
        string $url,
        string $proof,
        string $proofOld,
        bool $verifyTimeStampAge = true
    ): bool
    {
        if (!$this->params->get('commsy.online_office.proofkey_validation')) {
            return true;
        }

        // X-WOPI-TimeStamp must not be older than 20 minutes
        if ($verifyTimeStampAge) {
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
        }

        // See https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/scenarios/proofkeys#constructing-the-expected-proof
        $expected = sprintf(
            '%s%s%s%s%s%s',
            pack('N', strlen($accessToken)), // N = unsigned long (always 32 bit, big endian byte order)
            $accessToken,
            pack('N', strlen($url)),
            strtoupper($url),
            pack('N', 8), // this is 8 bytes in length of the X-WOPI-TimeStamp value
            pack('J', $timeStamp) // J = unsigned long long (always 64 bit, big endian byte order)
        );

        $discovery = $this->discoveryService->getWOPIDiscovery();
        $proofKey = $discovery->getProofKey();

        $key = $proofKey->getValue();
        $keyOld = $proofKey->getOldValue();

        if (!$proof || !$proofOld) {
            return false;
        }

        return $this->verify($expected, $proof, $key) ||
            $this->verify($expected, $proofOld, $key) ||
            $this->verify($expected, $proof, $keyOld);
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
