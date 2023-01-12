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

namespace App\Menu\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Voter based on the uri.
 */
class CommsyVoter implements VoterInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        [, $controller, $action] = explode('_', $this->requestStack->getCurrentRequest()->attributes->get('_route'));

        // Room settings
        if ('settings' === $controller) {
            return null;
        }

        // Room
        $roomId = $this->requestStack->getCurrentRequest()->attributes->get('roomId', '');
        if (stristr($item->getUri(), 'room/'.$roomId.'/'.$controller)) {
            return in_array($action, ['detail', 'list', 'calendar', 'changestatus']);
        }

        return null;
    }
}
