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

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCreator extends Creator
{
    public function canCreate($rubric)
    {
        return 'user' === $rubric;
    }

    public function getTitle($item)
    {
        if ($this->isGuestAccess) {
            if (!$item->isVisibleForAll()) {
                return $this->translator->trans('Not visible', [], 'rss');
            }
        }

        return $item->getFullName();
    }

    public function getDescription($item)
    {
        if ($item->getCreationDate() === $item->getModificationDate()) {
            return $this->translator->trans('A new person has become a member');
        } else {
            return $this->translator->trans('The profile has been updated');
        }
    }

    public function getLink($item)
    {
        return $this->router->generate('app_user_detail', [
            'roomId' => $item->getContextId(),
            'itemId' => $item->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
