<?php

namespace App\Metrics;

use App\Entity\Account;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AccountLoginsMetric implements MetricInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN  => 'onSecurityInteractiveLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Account $account */
        $account = $event->getAuthenticationToken()->getUser();

        if (!$account instanceof Account || !$account->getAuthSource() || !$account->getAuthSource()->getPortal()) {
            return;
        }

        $registry = PrometheusCollector::getCollectorRegistry();
        $accountLoginsTotal = $registry->getOrRegisterCounter(
            'commsy',
            'account_logins_total',
            'Number of logins',
            ['portal']
        );
        $accountLoginsTotal->inc([$account->getAuthSource()->getPortal()->getTitle()]);
    }

    public function update(): void
    {
        // This Metric is event-based and does not need to update
    }
}
