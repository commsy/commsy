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

use App\Entity\Account;
use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class TermsOfUseSubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private EntityManagerInterface $entityManager,
        private UserService $userService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        // Return early if this is not a master request
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        // Return early if this is not a GET request or an XHR request
        if ('GET' !== $event->getRequest()->getMethod() || $event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        // First check for portal terms
        /** @var Account $account */
        $account = $this->security->getUser();

        if (!$account instanceof Account) {
            return;
        }

        $portalRepository = $this->entityManager->getRepository(Portal::class);

        /**
         * TODO: Prior to $portalRepository->findOneById() we used $portalRepository->find(id) which - caused by the
         * way authentication for root is implemented? returned a proxy class, findOneById does not. What we should
         * really do is refactor accounts / auth_source to use a portal relation instead of the context_id column
         * and remove the need for server id 99.
         */

        /** @var Portal $portal */
        $portal = $portalRepository->findOneById($account->getContextId());

        if ($portal && $portal->hasAGBEnabled()) {
            $portalUser = $this->userService->getPortalUser($account);

            if ($portalUser) {
                $portalToUDate = $portal->getAGBChangeDate();
                $userAcceptedDate = $portalUser->getAGBAcceptanceDate();

                if (!$portalUser->isRoot() && (null === $userAcceptedDate || $userAcceptedDate < $portalToUDate)) {
                    // Redirect to tou site
                    if ('app_tou_portal' !== $event->getRequest()->attributes->get('_route') &&
                        'app_account_deleteaccount' !== $event->getRequest()->attributes->get('_route') &&
                        'app_logout' !== $event->getRequest()->attributes->get('_route')
                    ) {
                        $event->setController(fn () => new RedirectResponse($this->urlGenerator->generate('app_tou_portal', [
                            'portalId' => $portal->getId(),
                            'redirect' => $event->getRequest()->getRequestUri(),
                        ])));
                    }
                }
            }
        }

        // Room terms
        $currentContext = $this->legacyEnvironment->getCurrentContextItem();
        if ($currentContext->isProjectRoom() || $currentContext->isCommunityRoom() || $currentContext->isGroupRoom()) {
            if ($currentContext->withAGB()) {
                $contextUser = $this->legacyEnvironment->getCurrentUserItem();

                if ($contextUser) {
                    $contextToUDate = $currentContext->getAGBChangeDate();
                    $userAcceptedDate = $contextUser->getAGBAcceptanceDate();

                    if (!$contextUser->isRoot() && (null === $userAcceptedDate || $userAcceptedDate < $contextToUDate)) {
                        // Redirect to tou site
                        if ('app_tou_room' !== $event->getRequest()->attributes->get('_route') &&
                            'app_profile_deleteroomprofile' !== $event->getRequest()->attributes->get('_route') &&
                            'app_logout' !== $event->getRequest()->attributes->get('_route')
                        ) {
                            $event->setController(fn () => new RedirectResponse($this->urlGenerator->generate('app_tou_room', [
                                'roomId' => $currentContext->getItemID(),
                                'redirect' => $event->getRequest()->getRequestUri(),
                            ])));
                        }
                    }
                }
            }
        }
    }
}
