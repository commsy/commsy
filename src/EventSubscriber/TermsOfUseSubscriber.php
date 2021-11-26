<?php

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
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        UrlGeneratorInterface $urlGenerator,
        Security $security,
        EntityManagerInterface $entityManager,
        UserService $userService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
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
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // Return early if this is not a GET request are it is an XHR request
        if ($event->getRequest()->getMethod() !== 'GET' || $event->getRequest()->isXmlHttpRequest()) {
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

                if (!$portalUser->isRoot() && ($userAcceptedDate === null || $userAcceptedDate < $portalToUDate)) {
                    // Redirect to tou site
                    if ($event->getRequest()->attributes->get('_route') !== 'app_tou_portal' &&
                        $event->getRequest()->attributes->get('_route') !== 'app_account_deleteaccount' &&
                        $event->getRequest()->attributes->get('_route') !== 'app_logout'
                    ) {
                        $event->setController(function() use ($portal, $event) {
                            return new RedirectResponse($this->urlGenerator->generate('app_tou_portal', [
                                'portalId' => $portal->getId(),
                                'redirect' => $event->getRequest()->getRequestUri(),
                            ]));
                        });
                    }
                }
            }
        }

        // Room terms
        $currentContext = $this->legacyEnvironment->getCurrentContextItem();
        if ($currentContext->isProjectRoom() || $currentContext->isCommunityRoom() ||$currentContext->isGroupRoom()) {
            if ($currentContext->withAGB()) {
                $contextUser = $this->legacyEnvironment->getCurrentUserItem();

                if ($contextUser) {
                    $contextToUDate = $currentContext->getAGBChangeDate();
                    $userAcceptedDate = $contextUser->getAGBAcceptanceDate();

                    if (!$contextUser->isRoot() && ($userAcceptedDate === null || $userAcceptedDate < $contextToUDate)) {
                        // Redirect to tou site
                        if ($event->getRequest()->attributes->get('_route') !== 'app_tou_room' &&
                            $event->getRequest()->attributes->get('_route') !== 'app_profile_deleteroomprofile' &&
                            $event->getRequest()->attributes->get('_route') !== 'app_logout'
                        ) {
                            $event->setController(function() use ($currentContext, $event) {
                                return new RedirectResponse($this->urlGenerator->generate('app_tou_room', [
                                    'roomId' => $currentContext->getItemID(),
                                    'redirect' => $event->getRequest()->getRequestUri(),
                                ]));
                            });
                        }
                    }
                }
            }
        }
    }
}
