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
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AccountLoginsMetric extends AbstractMetric implements MetricInterface, EventSubscriberInterface
{
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

        // Update cache
        $this->saveCachedLogins((new CachedLogins())->setLogins($cachedLogins));
    }

    public function update(): void
    {
        $cachedLogins = $this->getCachedLogins();

        // Filter all logins that have been expired
        $nonExpiredLogins = $cachedLogins->getLogins()->filter(fn (CachedLogin $el) =>
            $el->getCached() >= (new DateTimeImmutable())->sub(new DateInterval('PT5M'))
        );

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

    private function getCachedLogins(): CachedLogins
    {
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem('commsy_metrics_account_logins');

        return $cacheItem->isHit() ? $cacheItem->get() : new CachedLogins();
    }

    private function saveCachedLogins(CachedLogins $cachedLogins): void
    {
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem('commsy_metrics_account_logins');

        $cacheItem->set($cachedLogins);
        $cache->save($cacheItem);
    }
}
