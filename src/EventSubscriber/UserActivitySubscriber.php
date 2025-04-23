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

use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class UserActivitySubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'updateActivity',
        ];
    }

    public function updateActivity(TerminateEvent $event): void
    {
        /*
           restrict logging to the following requests:
           "/room/<id>"
           "/room/<id>/<rubric>"
           "/room/<id>/<rubric>/<id>"
           "/dashboard/<id>"
        */
        $request = $event->getRequest();

        $patterns = [
            '~\/room\/(\d)+$~',
            '~\/room\/(\d)+\/([a-z])+$~',
            '~\/room\/(\d)+\/([a-z])+\/(\d)+$~',
            '~\/dashboard\/(\d)+$~',
        ];

        $logRequest = array_filter($patterns, function (string $pattern) use ($request) {
            return preg_match($pattern, $request->getUri());
        }) !== [];

        if (!$logRequest) {
            return;
        }

        $user = $this->legacyEnvironment->getCurrentUser();
        if ($user->isUser() && !$user->isRoot()) {
            $user->updateLastLogin();

            // The portal user is no longer updated here
            // This is now done by the LoginSubscriber and stored in the Account
        }
    }
}
