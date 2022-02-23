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
use Symfony\Component\HttpFoundation\Request;
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

        /** @var Account $account */
        $account = $this->security->getUser();

        $request = $event->getRequest();
        $this->setupContextId($request, $account);

        $this->setupUser($account);
    }

    /**
     * @param Request $request
     * @param Account|null $account
     * @return void
     */
    private function setupContextId(Request $request, ?Account $account)
    {
        $contextId = null;
        $contextId = $contextId ?? $request->attributes->get('roomId');
        $contextId = $contextId ?? $request->attributes->get('portalId');

        if ($request->attributes->has('fileId')) {
            $file = $this->fileService->getFile($request->attributes->get('fileId'));
            $contextId = $file->getContextID();
        }

        if ($contextId) {
            $this->legacyEnvironment->setCurrentContextID($contextId);
        } else {
            if ($account !== null) {
                $this->legacyEnvironment->setCurrentContextID($account->getContextId());
            }
        }
    }

    /**
     * @param Account|null $account
     * @return void
     */
    private function setupUser(?Account $account)
    {
        $userManager = $this->legacyEnvironment->getUserManager();

        if ($account !== null && $this->security->isGranted(RootVoter::ROOT)) {
            $this->legacyEnvironment->setCurrentUser($userManager->getRootUser());
            return;
        }

        if ($account === null) {
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
    }
}
