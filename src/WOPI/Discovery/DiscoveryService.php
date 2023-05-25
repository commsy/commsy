<?php

namespace App\WOPI\Discovery;

use App\WOPI\Discovery\Response\Action;
use App\WOPI\Discovery\Response\App;
use App\WOPI\Discovery\Response\NetZone;
use App\WOPI\Discovery\Response\WOPIDiscovery;
use App\WOPI\Discovery\Response\WOPIZone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DiscoveryService
{
    public function __construct(
        private HttpClientInterface   $httpClient,
        private SerializerInterface   $serializer,
        private DiscoveryCache        $cache,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function getWOPIDiscovery(): ?WOPIDiscovery
    {
        $discoveryBaseUrl = $this->parameterBag->get('commsy.online_office.base_url');
        if (!$discoveryBaseUrl) {
            return null;
        }

        /**
         * TODO:
         * A more dynamic option is to re-run discovery when proof key validation fails,
         * or when it succeeds using the old key. That implies that the keys have been rotated,
         * so discovery should definitely be re-run to get the new public key.
         * @see https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/scenarios/proofkeys
         */

        // For now, we only rely on the cache timeout
        $cachedDiscovery = $this->cache->getFromCache($discoveryBaseUrl);
        if (!$cachedDiscovery) {
            $discovery = $this->discoverDocumentServer($discoveryBaseUrl);
            if ($discovery) {
                $this->cache->storeInCache($discoveryBaseUrl, $discovery);
            }

        }

        return $this->cache->getFromCache($discoveryBaseUrl);
    }

    public function findApp(WOPIDiscovery $discovery, string $fileExt, string $name): ?App
    {
        $netZones = $discovery->getNetZones();
        if (empty($netZones)) {
            return null;
        }

        $netZone = array_filter($netZones, fn (NetZone $netZone) => $netZone->getName() === WOPIZone::EXTERNAL_HTTPS) ?:
            $netZones[0];

        foreach ($netZone->getApps() as $app) {
            $action = $this->findAction($app, $fileExt, $name);
            if ($action) {
                return $app;
            }
        }

        return null;
    }

    public function findAction(App $app, string $fileExt, string $name): ?Action
    {
        $actions = array_filter($app->getActions(), fn (Action $action) =>
            $action->getExt() === $fileExt && $action->getName() === $name
        );
        if (!empty($actions)) {
            return array_values($actions)[0];
        }

        return null;
    }

    private function discoverDocumentServer(string $discoveryBaseUrl): ?WOPIDiscovery
    {
        $discoveryUrl = "$discoveryBaseUrl/hosting/discovery";

        try {
            $discoveryResponse = $this->httpClient->request('GET', $discoveryUrl)->getContent();
            return $this->serializer->deserialize($discoveryResponse, WOPIDiscovery::class, 'xml');
        } catch (TransportExceptionInterface|HttpExceptionInterface) {
            return null;
        }
    }
}
