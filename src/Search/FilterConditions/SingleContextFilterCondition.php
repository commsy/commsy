<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class SingleContextFilterCondition implements FilterConditionInterface
{
    /**
     * @var int context id
     */
    private $contextId;

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     * @return SingleContextFilterCondition
     */
    public function setContextId(int $contextId): SingleContextFilterCondition
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $contextTerm = new Terms();
        $contextTerm->setTerms('contextId', [$this->contextId]);

        $parentTerm = new Terms();
        $parentTerm->setTerms('parentId', [$this->contextId]);

        return [$contextTerm, $parentTerm];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_SHOULD;
    }
}