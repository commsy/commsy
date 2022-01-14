<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class RubricFilterCondition implements FilterConditionInterface
{
    /**
     * @var string|null $rubric
     */
    private ?string $rubric;

    /**
     * @param string $rubric
     * @return RubricFilterCondition
     */
    public function setRubric(string $rubric): RubricFilterCondition
    {
        $this->rubric = $rubric;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ($this->rubric === 'all') {
            return [];
        }

        $rubricTerms = new Terms('rubric');
        $rubricTerms->setTerms([$this->rubric]);
        return [$rubricTerms];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}