<?php


namespace App\EventSubscriber;


use App\Entity\Account;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
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
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var FileService
     */
    private FileService $fileService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Security $security,
        FileService $fileService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->security = $security;
        $this->fileService = $fileService;
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

        if ($request->attributes->has('fileId')) {
            $file = $this->fileService->getFile($request->attributes->get('fileId'));
            $contextId = $file->getContextID();
        }

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
                        /**
                         * TODO: We still cannot throw an exception here, because of the avatar user image url
                         * (requesting an project room image without membership from inside a community room)
                         */
                        //throw new AccessDeniedHttpException("Mandatory unique user item not found!");
                    } else {
                        $this->legacyEnvironment->setCurrentUser($contextUserList->getFirst());
                    }

                    /**
                     * TODO: MAKE A PROPER FIX FOR THIS
                     * This fix was implemented as a workaround to get the right _current_user in the extension of cs_manager
                     */
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
