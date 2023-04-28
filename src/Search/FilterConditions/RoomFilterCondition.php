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
use Elastica\Query\Ids;

class RoomFilterCondition implements FilterConditionInterface
{
    public function __construct(private readonly UserService $userService)
    {
    }

    /**
     * @return Ids[]
     */
    public function getConditions(): array
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contextIds = [];
        foreach ($searchableRooms as $searchableRoom) {
            $contextIds[] = $searchableRoom->getItemId();
        }

        $contextFilter = new Ids($contextIds);

        return [$contextFilter];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
