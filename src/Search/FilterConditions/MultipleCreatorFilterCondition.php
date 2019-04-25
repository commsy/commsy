<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class MultipleCreatorFilterCondition implements FilterConditionInterface
{
    /**
     * @var string[] $creators
     */
    private $creators;

    /**
     * @param string[] $creators
     * @return MultipleCreatorFilterCondition
     */
    public function setCreators(array $creators): MultipleCreatorFilterCondition
    {
        $this->creators = $creators;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $creatorTerm = new Terms();
        $creatorTerm->setTerms('creator.fullName.raw', $this->creators);

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