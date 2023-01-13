<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Facade\UserCreatorFacade;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AutoRoomMembershipSubscriber implements EventSubscriberInterface
{
    // separator which separates multiple room identifiers
    private const SEPARATOR = ';';

    /**
     * @var UserCreatorFacade
     */
    private UserCreatorFacade $userCreator;

    public function __construct(UserCreatorFacade $userCreator) {
        $this->userCreator = $userCreator;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
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

        $membershipIdentifiers = explode(self::SEPARATOR, trim($membershipIdentifierString));
        if (empty($membershipIdentifiers)) {
            return;
        }

        // for the given account, create users in the specified rooms
        $this->userCreator->addUserToRoomsWithSlugs($account, $membershipIdentifiers);
    }
}
