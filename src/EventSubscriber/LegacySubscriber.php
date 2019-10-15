<?php


namespace App\EventSubscriber;


use App\Entity\Account;
use App\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('roomId')) {
            $contextId = $request->attributes->get('roomId');

            $this->legacyEnvironment->setCurrentContextID($contextId);

            /** @var Account $user */
            $user = $this->security->getUser();

            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setContextLimit($contextId);
            $userManager->setUserIDLimit($user->getUsername());
            $userManager->select();

            /** @var \cs_list $portalUserList */
            $portalUserList = $userManager->get();

            if ($portalUserList->getCount() != 1) {
                throw new \Exception();
            }

            $this->legacyEnvironment->setCurrentUser($portalUserList->getFirst());
        }
    }
}
