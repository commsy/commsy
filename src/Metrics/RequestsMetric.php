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

use App\Utils\RequestContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestsMetric extends AbstractMetric implements MetricInterface, EventSubscriberInterface
{
    public function __construct(private RequestContext $requestContext)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $portal = $this->requestContext->fetchPortal($event->getRequest());
        if (!$portal) {
            return;
        }

        $response = $event->getResponse();

        $requestsTotal = $this->getCollectorRegistry()->getOrRegisterCounter(
            $this->getNamespace(),
            'requests_total',
            'Number of requests',
            ['portal', 'status_code']
        );

        $requestsTotal->inc([$portal->getTitle(), $response->getStatusCode()]);
    }

    public function update(): void
    {
        // This Metric is event-based and does not need to update
    }
}
