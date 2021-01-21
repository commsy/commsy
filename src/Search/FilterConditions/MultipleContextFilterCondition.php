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
     * @var string[] $hashtags
     */
    private $contexts;

    /**
     * @param string[] $contexts
     * @return MultipleContextFilterCondition
     */
    public function setContexts(array $contexts): MultipleContextFilterCondition
    {
        $this->contexts = $contexts;
        return $this;
    }

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contexts = [];
        foreach ($searchableRooms as $searchableRoom) {
            if (!is_null($this->contexts) && !empty($this->contexts)) {
                if (in_array($searchableRoom->getTitle(),$this->contexts)) {
                    $contexts[] = $searchableRoom->getTitle();
                }
            } else {
                $contexts[] = $searchableRoom->getTitle();
            }
        }

        $contextFilter = new Terms();
        $contextFilter->setTerms('context.title', $contexts);

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