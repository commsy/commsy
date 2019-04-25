<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class RubricFilterCondition implements FilterConditionInterface
{
    private $rubric;

    public function setRubric($rubric)
    {
        $this->rubric = $rubric;
    }

    public function getConditions(): array
    {
        if ($this->rubric === 'all') {
            return [];
        }

        $rubricTerms = new Terms();
        $rubricTerms->setTerms('_type', [$this->rubric]);
        return [$rubricTerms];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}