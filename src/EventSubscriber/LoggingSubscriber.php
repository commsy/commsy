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

namespace App\EventSubscriber;

use App\Repository\LogRepository;
use App\Utils\RequestContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

readonly class LoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LogRepository $logRepository,
        private Security $security,
        private RequestContext $requestContext
    ) {
    }

    public function onTerminateEvent(TerminateEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $serializer = new Serializer([], [new JsonEncoder()]);

        $userAgent = $request->headers->get('User-Agent', 'No Info');
        $postAsJson = $serializer->encode($request->request->all(), 'json');
        $anonymousIp = IpUtils::anonymize($request->server->get('REMOTE_ADDR'), '');
        $requestUri = $request->getRequestUri();
        $method = $request->getMethod();
        $username = $this->security->getUser() ? $this->security->getUser()->getUserIdentifier() : null;
        $contextId = $this->requestContext->fetchContextId($request);

        $this->logRepository->addLog(
            $anonymousIp,
            $userAgent,
            $requestUri,
            $postAsJson,
            $method,
            $username,
            $contextId
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TerminateEvent::class => 'onTerminateEvent',
        ];
    }
}
