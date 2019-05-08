<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class SingleCreatorFilterCondition implements FilterConditionInterface
{
    /**
     * @var string $creator
     */
    private $creator;

    /**
     * @param string $creator
     * @return SingleCreatorFilterCondition
     */
    public function setCreator(string $creator): SingleCreatorFilterCondition
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ($this->creator === 'all') {
            return [];
        }

        $creatorTerm = new Terms();
        $creatorTerm->setTerms('creator.fullName.raw', [$this->creator]);

        return [$creatorTerm];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}