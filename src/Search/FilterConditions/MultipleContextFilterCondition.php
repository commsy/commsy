<?php


namespace App\Search\FilterConditions;


use App\Utils\UserService;
use Elastica\Query\Terms;

class MultipleContextFilterCondition implements FilterConditionInterface
{
    /**
     * @var UserService $userService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $searchableRooms = $this->userService->getSearchableRooms($currentUser);

        $contextIds = array_map(function (\cs_room_item $room) {
            return $room->getItemID();
        }, $searchableRooms);

        $contextFilter = new Terms();
        $contextFilter->setTerms('contextId', $contextIds);

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