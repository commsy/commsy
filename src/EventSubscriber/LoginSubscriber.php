<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Facade\UserCreatorFacade;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

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
     * @var UserCreatorFacade
     */
    private UserCreatorFacade $userCreator;

    public function __construct(
        Security $security,
        UrlGeneratorInterface $urlGenerator,
        UserCreatorFacade $userCreator
    ) {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->userCreator = $userCreator;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            return;
        }

        /**
         * Make sure that there is a valid user object and as a result an authentication token in the token storage.
         * If missing, ->isGranted() will cause development-only urls like _wdt/ to
         * throw an AuthenticationCredentialsNotFoundException
         */
        if (
            $event->getRequest()->attributes->get('_route') === 'app_migration_password' ||
            $this->security->isGranted('ROLE_PREVIOUS_ADMIN')
        ) {
            return;
        }

        if ($account->hasLegacyPassword()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_migration_password')));
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Account $account */
        $account = $event->getAuthenticationToken()->getUser();
        if (!$account instanceof Account) {
            return;
        }

        /** @var AuthSource $uthSource */
        $authSource = $account->getAuthSource();
        if (!$authSource) {
            return;
        }

        // check if auto-creating room memberships is enabled in the portal configuration
        /** @var Portal $portal */
        $portal = $authSource->getPortal();
        if (!$portal || !$portal->getAuthMembershipEnabled()) {
            return;
        }

        // extract any room identifiers from the request using the parameter key defined in the portal configuration
        $membershipIdentifiersKey = $portal->getAuthMembershipIdentifier();
        if (empty($membershipIdentifiersKey)) {
            return;
        }

        $request = $event->getRequest();
        $paramBag = $request->request;
        $membershipIdentifierString = $paramBag->get($membershipIdentifiersKey);
        if (empty($membershipIdentifierString)) {
            return;
        }

        $membershipIdentifiers = explode(',', trim($membershipIdentifierString));
        if (empty($membershipIdentifiers)) {
            return;
        }

        // for the given account, create users in the specified rooms
        $this->userCreator->addUserToRoomsWithSlugs($account, $membershipIdentifiers);
    }
}
