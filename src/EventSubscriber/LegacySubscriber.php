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
use App\Legacy\UserItemAdapter;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\CurrentContextResolver;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LegacySubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly Security $security,
        private readonly CurrentContextResolver $currentContextResolver,
        private readonly CurrentUserResolver $currentUserResolver
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                'onKernelController',
                10,
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function onKernelController(ControllerEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $account = $this->security->getUser();

        // NOTE: for guests, $account is null but setupUser() will handle this
        if ($account instanceof Account || null === $account) {
            $this->setupContext();

            $this->setupUser($account);
        }
    }

    /**
     * Context decision is now Doctrine-native: CurrentContextResolver
     * owns the (request attributes -> account portal fallback) lookup,
     * 1:1 with the former hand-rolled logic. cs_environment is reduced
     * to a consumer of the resolved id. Resolver null (guest / no
     * context) leaves the LegacyEnvironment ctor default (server 99) in
     * place — the Phase 0 pinned behaviour.
     */
    private function setupContext(): void
    {
        $contextId = $this->currentContextResolver->getContextId();
        if (null !== $contextId) {
            $this->legacyEnvironment->setCurrentContextID($contextId);
        }
    }

    private function setupUser(?Account $account): void
    {
        if (null !== $account && $this->security->isGranted(RootVoter::ROOT)) {
            $userManager = $this->legacyEnvironment->getUserManager();
            $this->legacyEnvironment->setCurrentUser($userManager->getRootUser());

            return;
        }

        if (null === $account) {
            $this->legacyEnvironment->setCurrentUser($this->buildGuestUserItem());

            return;
        }

        // Identity decision is now Doctrine-native: the resolver owns the
        // (account, context) -> which-row lookup; the legacy manager is
        // reduced to a by-id hydrator via UserItemAdapter.
        $user = $this->currentUserResolver->getUser();
        if (null !== $user) {
            $legacyUser = UserItemAdapter::userToLegacy($user, $this->legacyEnvironment);
            if (null !== $legacyUser) {
                $this->legacyEnvironment->setCurrentUser($legacyUser);
            }
        }
        /*
         * No unique (account, context) user row -> mirror the historical
         * "cannot throw" branch and leave the empty currentUserItem in
         * place (avatar image url requested without membership, etc.).
         */

        /*
         * TODO: MAKE A PROPER FIX FOR THIS
         * This fix was implemented as a workaround to get the right _current_user in the extension of cs_manager
         */
        $this->legacyEnvironment->unsetAllInstancesExceptTranslator();
    }

    private function buildGuestUserItem(): cs_user_item
    {
        $legacyGuest = new cs_user_item($this->legacyEnvironment);
        $legacyGuest->setStatus(0);
        $legacyGuest->setUserID('guest');

        return $legacyGuest;
    }
}
