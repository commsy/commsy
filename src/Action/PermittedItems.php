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

declare(strict_types=1);

namespace App\Action;

use App\Security\Authorization\Voter\ItemVoter;
use App\Security\Authorization\Voter\UserVoter;
use cs_item;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Keeps those of the chosen items an action may act on. The ids of a bulk
 * action come from the request, so each item is checked on its own.
 */
final readonly class PermittedItems
{
    public function __construct(private Security $security)
    {
    }

    /**
     * @param array<cs_item> $items
     *
     * @return list<cs_item>
     */
    public function visible(array $items): array
    {
        return $this->keep($items, fn (cs_item $item): bool => $this->security->isGranted(ItemVoter::SEE, $item->getItemID()));
    }

    /**
     * @param array<cs_item> $items
     *
     * @return list<cs_item>
     */
    public function editable(array $items): array
    {
        return $this->keep($items, fn (cs_item $item): bool => $this->security->isGranted(ItemVoter::EDIT, $item->getItemID()));
    }

    /**
     * Pinning is up to the moderation of the room the item is in.
     *
     * @param array<cs_item> $items
     *
     * @return list<cs_item>
     */
    public function pinnable(array $items): array
    {
        return $this->keep($items, fn (cs_item $item): bool => $this->security->isGranted(ItemVoter::EDIT, $item->getItemID())
            && $this->security->isGranted(UserVoter::ROOM_MODERATOR, $item->getContextID()));
    }

    /**
     * @param array<cs_item>          $items
     * @param callable(cs_item): bool $permitted
     *
     * @return list<cs_item>
     */
    private function keep(array $items, callable $permitted): array
    {
        return array_values(array_filter($items, $permitted));
    }
}
