<?php


namespace App\EventSubscriber;


use App\Entity\Account;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_user_item;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class LegacySubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;
    private Security $security;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Security $security,
        UserService $userService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->security = $security;
    }

    public static function getSubscribedEvents()
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
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        $contextId = null;
        $contextId = $contextId ?? $request->attributes->get('roomId');
        $contextId = $contextId ?? $request->attributes->get('portalId');

        if ($contextId) {
            $this->legacyEnvironment->setCurrentContextID($contextId);

            $userManager = $this->legacyEnvironment->getUserManager();

            if ($this->security->isGranted(RootVoter::ROOT)) {
                $this->legacyEnvironment->setCurrentUser($userManager->getRootUser());
            } else {
                /** @var Account $account */
                $account = $this->security->getUser();
                if ($account !== null) {
                    $userManager->resetLimits();
                    $userManager->setContextLimit($contextId);
                    $userManager->setUserIDLimit($account->getUsername());
                    $userManager->setAuthSourceLimit($account->getAuthSource()->getId());
                    $userManager->select();

                    /** @var cs_list $contextUserList */
                    $contextUserList = $userManager->get();

                    if ($contextUserList->getCount() != 1) {
                        throw new Exception("Mandatory unique user item not found!");
                    }

                    $this->legacyEnvironment->setCurrentUser($contextUserList->getFirst());

                    //TODO: MAKE A PROPER FIX FOR THIS
                    //  This fix was implemented as a workaround to get the right _current_user in the extension of cs_manager
                    $this->legacyEnvironment->unsetAllInstancesExceptTranslator();
                } else {
                    // guest
                    $legacyGuest = new cs_user_item($this->legacyEnvironment);
                    $legacyGuest->setStatus(0);
                    $legacyGuest->setUserID('guest');
                    $this->legacyEnvironment->setCurrentUser($legacyGuest);
                }
            }
        }
    }
}
