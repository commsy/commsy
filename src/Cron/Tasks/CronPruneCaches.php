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
namespace App\Cron\Tasks;

use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removes the expired entries of the caches that persist between requests.
 * A cache on the filesystem drops an expired entry only when it is read
 * again, so entries no longer asked for would stay for good.
 */
class CronPruneCaches implements CronTaskInterface
{
    /**
     * @var list<CacheItemPoolInterface>
     */
    private readonly array $pools;

    public function __construct(
        #[Autowire(service: 'cache.app')]
        CacheItemPoolInterface $appCache,
        #[Autowire(service: 'read_status.cache')]
        CacheItemPoolInterface $readStatusCache,
    ) {
        $this->pools = [$appCache, $readStatusCache];
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        foreach ($this->pools as $pool) {
            if ($pool instanceof PruneableInterface) {
                $pool->prune();
            }
        }
    }

    public function getSummary(): string
    {
        return 'Prune expired cache entries';
    }
}
