<?php


namespace App\Search\FilterConditions;


use App\Utils\UserService;
use Elastica\Query\Ids;

class RoomFilterCondition implements FilterConditionInterface
{
    /**
     * @var UserService $userService
     */
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}