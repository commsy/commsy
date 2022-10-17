<?php

namespace App\Menu\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Voter based on the uri
 */
class CommsyVoter implements VoterInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        list(, $controller, $action) = explode('_', $this->requestStack->getCurrentRequest()->attributes->get('_route'));

        // Room settings
        if ($controller === 'settings') {
            return null;
        }

        // Room
        $roomId = $this->requestStack->getCurrentRequest()->attributes->get('roomId', '');
        if (stristr($item->getUri(), 'room/' . $roomId . '/' . $controller)) {
            return in_array($action, ['detail', 'list', 'calendar', 'changestatus']);
        }

        return null;
    }
}
