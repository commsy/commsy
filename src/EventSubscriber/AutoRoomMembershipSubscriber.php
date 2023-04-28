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

    public function __construct(private readonly UserCreatorFacade $userCreator)
    {
    }

    public static function getSubscribedEvents(): array
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
        $paramBag = $request->server;
        $membershipIdentifierString = $paramBag->get($membershipIdentifiersKey);
        if (empty($membershipIdentifierString)) {
            return;
        }

        $membershipIdentifiers = explode(self::SEPARATOR, trim((string) $membershipIdentifierString));
        if (empty($membershipIdentifiers)) {
            return;
        }

        // for the given account, create users in the specified rooms
        $this->userCreator->addUserToRoomsWithSlugs($account, $membershipIdentifiers);
    }
}
