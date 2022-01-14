<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class SingleContextFilterCondition implements FilterConditionInterface
{
    /**
     * @var int|null context id
     */
    private ?int $contextId;

    /**
     * @return int|null
     */
    public function getContextId(): ?int
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
        $contextTerm = new Terms('contextId');
        $contextTerm->setTerms([$this->contextId]);

        $parentTerm = new Terms('parentId');
        $parentTerm->setTerms([$this->contextId]);

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