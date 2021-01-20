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

    /**
     * @var string[] $contextIds
     */
    private $contextIds;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param string[] $contextIds
     * @return MultipleContextFilterCondition
     */
    public function setContextIds(array $contextIds): MultipleContextFilterCondition
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $selectedContextIds = [];
        $classContextIds = $this->contextIds;
        foreach ($searchableRooms as $searchableRoom) {
            if (!is_null($classContextIds) && !empty($classContextIds)) {
                if (in_array($searchableRoom->getItemId(),$classContextIds)) {
                    $selectedContextIds[] = $searchableRoom->getItemId();
                }
            } else {
                $selectedContextIds[] = $searchableRoom->getItemId();
            }
        }

        $contextFilter = new Terms();
        $contextFilter->setTerms('contextId', $selectedContextIds);

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