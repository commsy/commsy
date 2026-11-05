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

namespace App\Metrics;

use App\Entity\Account;
use App\Metrics\Data\CachedLogin;
use App\Metrics\Data\CachedLogins;
use DateInterval;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AccountLoginsMetric extends AbstractMetric implements MetricInterface, EventSubscriberInterface
{
    private const string CACHE_KEY = 'commsy_metrics_account_logins';

    /**
     * How long a login counts as recent; older ones are of no use and dropped.
     */
    private const string RECENT = 'PT5M';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getUser();

        if (!$account instanceof Account || !$account->getAuthSource() || !$account->getAuthSource()->getPortal()) {
            return;
        }

        $cachedLogins = $this->getCachedLogins()->getLogins();

        // Check if there is already an entry for this account and remove it
        $matchAccount = $cachedLogins->findFirst(fn ($key, CachedLogin $el) => $el->getAccountId() === $account->getId());
        if ($matchAccount) {
            $cachedLogins->removeElement($matchAccount);
        }

        // Add the current login
        $cachedLogins->add(new CachedLogin(
            $account->getId(),
            $account->getAuthSource()->getPortal()->getTitle()
        ));

        // Update cache, without the logins no longer recent
        $this->saveCachedLogins((new CachedLogins())->setLogins($cachedLogins->filter($this->isRecent(...))));
    }

    public function update(): void
    {
        $cachedLogins = $this->getCachedLogins();

        // Filter all logins that have been expired
        $nonExpiredLogins = $cachedLogins->getLogins()->filter($this->isRecent(...));

        $this->getAdapter()->wipeData('counter', 'account_logins_current');

        $accountLoginsActive = $this->getCollectorRegistry()->getOrRegisterCounter(
            $this->getNamespace(),
            'account_logins_current',
            'Recent logins',
            ['portal', 'accountId']
        );

        foreach ($nonExpiredLogins as $nonExpiredLogin) {
            /** @var CachedLogin $nonExpiredLogin */
            $accountLoginsActive->inc([
                $nonExpiredLogin->getPortalTitle(),
                $nonExpiredLogin->getAccountId(),
            ]);
        }
    }

    private function isRecent(CachedLogin $login): bool
    {
        return $login->getCached() >= (new DateTimeImmutable())->sub(new DateInterval(self::RECENT));
    }

    private function getCachedLogins(): CachedLogins
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        return $cacheItem->isHit() ? $cacheItem->get() : new CachedLogins();
    }

    private function saveCachedLogins(CachedLogins $cachedLogins): void
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        $cacheItem->set($cachedLogins);
        $this->cache->save($cacheItem);
    }
}
