<?php

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCreator extends Creator
{
    public function canCreate($rubric)
    {
        return $rubric === 'user';
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