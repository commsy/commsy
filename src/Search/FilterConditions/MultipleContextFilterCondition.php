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

namespace App\Search\FilterConditions;

use App\Utils\UserService;
use cs_room_item;
use Elastica\Query\Terms;

class MultipleContextFilterCondition implements FilterConditionInterface
{
    public function __construct(private readonly UserService $userService)
    {
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $searchableRooms = $this->userService->getSearchableRooms($currentUser);

        $contextIds = array_map(fn (cs_room_item $room) => $room->getItemID(), $searchableRooms);

        $contextFilter = new Terms('contextId');
        $contextFilter->setTerms($contextIds);

        return [$contextFilter];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
