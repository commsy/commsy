<?php

namespace App\WOPI\Discovery;

use App\WOPI\Discovery\Response\WOPIDiscovery;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

final readonly class DiscoveryCache
{
    public const CACHE_EXPIRES = 86400;

    private ApcuAdapter $cache;

    public function __construct()
    {
        $this->cache = new ApcuAdapter();
    }

    public function getFromCache(string $discoveryBaseUrl): ?WOPIDiscovery
    {
        try {
            $cacheItem = $this->cache->getItem($this->getCacheKey($discoveryBaseUrl));
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (InvalidArgumentException) {
        }

        return null;
    }

    public function storeInCache(string $discoveryBaseUrl, WOPIDiscovery $discovery): bool
    {
        try {
            $cacheItem = $this->cache->getItem($this->getCacheKey($discoveryBaseUrl));
            if (!$cacheItem->isHit()) {
                $cacheItem->set($discovery);
                $cacheItem->expiresAfter(self::CACHE_EXPIRES);
                $this->cache->save($cacheItem);

                return true;
            }
        } catch (InvalidArgumentException) {
        }

        return false;
    }

    public function deleteFromCache(string $discoveryBaseUrl): void
    {
        try {
            $this->cache->delete($this->getCacheKey($discoveryBaseUrl));
        } catch (InvalidArgumentException) {
        }
    }

    private function getCacheKey(string $discoveryBaseUrl): string
    {
        $hostPath = parse_url($discoveryBaseUrl, PHP_URL_HOST) .
            parse_url($discoveryBaseUrl, PHP_URL_PATH);
        return "commsy.wopi.discovery.$hostPath";
    }
}
