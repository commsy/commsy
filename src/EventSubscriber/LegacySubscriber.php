<?php


namespace App\EventSubscriber;


use App\Entity\Account;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use cs_user_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class LegacySubscriber implements EventSubscriberInterface
{
    private $legacyEnvironment;
    private $security;

    public function __construct(LegacyEnvironment $legacyEnvironment, Security $security)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => [
                'onKernelController',
                10,
            ],
        );
    }

    public function onKernelController(ControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        $contextId = null;
        $contextId = $contextId ?? $request->attributes->get('roomId', null);
        $contextId = $contextId ?? $request->attributes->get('portalId', null);

        if ($contextId) {
            $this->legacyEnvironment->setCurrentContextID($contextId);

            $userManager = $this->legacyEnvironment->getUserManager();

            if ($this->security->isGranted(RootVoter::ROOT)) {
                $this->legacyEnvironment->setCurrentUser($userManager->getRootUser());
            } else {
                /** @var Account $user */
                $user = $this->security->getUser();
                if ($user !== null) {
                    $userManager->resetLimits();
                    $userManager->setContextLimit($contextId);
                    $userManager->setUserIDLimit($user->getUsername());
                    $userManager->select();

                    /** @var \cs_list $contextUserList */
                    $contextUserList = $userManager->get();

                    if ($contextUserList->getCount() != 1) {
                        throw new \Exception();
                    }

                    $this->legacyEnvironment->setCurrentUser($contextUserList->getFirst());
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
