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
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use cs_environment;
use cs_list;
use cs_user_item;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LegacySubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly Security $security,
        private readonly FileService $fileService
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
    public function onKernelController(ControllerEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $account = $this->security->getUser();

        // NOTE: for guests, $account is null but setupUser() will handle this
        if ($account instanceof Account || null === $account) {
            $request = $event->getRequest();
            $this->setupContext($request, $account);

            $this->setupUser($account);
        }
    }

    private function setupContext(Request $request, ?Account $account): void
    {
        $contextId = null;
        $contextId ??= $request->attributes->get('roomId');
        $contextId ??= $request->attributes->get('portalId');

        if ($request->attributes->has('fileId')) {
            $file = $this->fileService->getFile($request->attributes->get('fileId'));
            $contextId = $file->getContextID();
        }

        if ($contextId) {
            $this->legacyEnvironment->setCurrentContextID($contextId);
        } else {
            if (null !== $account) {
                $this->legacyEnvironment->setCurrentContextID($account->getContextId());
            }
        }
    }

    private function setupUser(?Account $account): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();

        if (null !== $account && $this->security->isGranted(RootVoter::ROOT)) {
            $this->legacyEnvironment->setCurrentUser($userManager->getRootUser());

            return;
        }

        if (null === $account) {
            // guest
            $legacyGuest = new cs_user_item($this->legacyEnvironment);
            $legacyGuest->setStatus(0);
            $legacyGuest->setUserID('guest');
            $this->legacyEnvironment->setCurrentUser($legacyGuest);

            return;
        }

        $userManager->resetLimits();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserIDLimit($account->getUsername());
        $userManager->setAuthSourceLimit($account->getAuthSource()->getId());
        $userManager->select();

        /** @var cs_list $contextUserList */
        $contextUserList = $userManager->get();

        if (1 != $contextUserList->getCount()) {
            /*
             * TODO: We still cannot throw an exception here, because of the avatar user image url
             * (requesting an project room image without membership from inside a community room)
             */
            // throw new AccessDeniedHttpException("Mandatory unique user item not found!");
        } else {
            $this->legacyEnvironment->setCurrentUser($contextUserList->getFirst());
        }

        /*
         * TODO: MAKE A PROPER FIX FOR THIS
         * This fix was implemented as a workaround to get the right _current_user in the extension of cs_manager
         */
        $this->legacyEnvironment->unsetAllInstancesExceptTranslator();
    }
}
