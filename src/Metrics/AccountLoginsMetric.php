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
        $account = $event->getUser();

        if (!$account instanceof Account || !$account->getAuthSource() || !$account->getAuthSource()->getPortal()) {
            return;
        }

        $accountLoginsTotal = $this->getCollectorRegistry()->getOrRegisterCounter(
            $this->getNamespace(),
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
