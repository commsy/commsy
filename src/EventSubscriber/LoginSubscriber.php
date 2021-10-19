<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        Security $security,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') === 'app_migration_password') {
            return;
        }

        /** @var Account $user */
        $user = $this->security->getUser();

        if ($user && $user->hasLegacyPassword()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_migration_password')));
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Account $account */
        $account = $this->security->getUser();

        if ($account) {
            $account->setLastLogin(new \DateTime());
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        }

        // TODO: Reset user inactivity?
//        $portalUser = $user->getRelatedCommSyUserItem();
//        if ($portalUser) {
//            if ($portalUser->getMailSendNextLock() || $portalUser->getMailSendBeforeLock() || $portalUser->getNotifyLockDate()) {
//                $portalUser->resetInactivity();
//            }
//        }
    }
}
