<?php

namespace App\Metrics;

use App\Utils\RequestContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestsMetric extends AbstractMetric implements MetricInterface, EventSubscriberInterface
{
    /**
     * @var RequestContext
     */
    private RequestContext $requestContext;

    /**
     * @param RequestContext $requestContext
     */
    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        if (!$event->isMasterRequest()) {
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
