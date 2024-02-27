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

use App\Proxy\PortalProxy;
use App\Services\LegacyEnvironment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LegacyEnvironment $legacyEnvironment
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // If no explicit locale has been set on this request, use one from the session.
        // The value in the session is the user's preference from the account table.
        $resolvedLocale = $request->getSession()->get('_locale', $this->parameterBag->get('locale'));

        // The locale might be enforced by a workspace
        $contextItem = $this->legacyEnvironment->getEnvironment()->getCurrentContextItem();
        if (!$contextItem instanceof PortalProxy) {
            if ($contextItem->getLanguage() !== 'user') {
                $resolvedLocale = $contextItem->getLanguage();
            }
        }

        // Locale might be 'browser'. In this case we try to get preferred languages from the Accept-Language header.
        if ($resolvedLocale === 'browser') {
            $enabledLocales = $this->parameterBag->get('kernel.enabled_locales');
            $resolvedLocale = $request->getPreferredLanguage($enabledLocales);
        }

        $request->setLocale($resolvedLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
