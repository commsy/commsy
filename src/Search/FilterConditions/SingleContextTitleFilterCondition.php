<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class SingleContextTitleFilterCondition implements FilterConditionInterface
{
    /**
     * @var string $contextTitle
     */
    private $contextTitle;

    /**
     * @param string $contextTitle
     * @return SingleContextTitleFilterCondition
     */
    public function setContextTitle(string $contextTitle): SingleContextTitleFilterCondition
    {
        $this->contextTitle = $contextTitle;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ($this->contextTitle === 'all') {
            return [];
        }

        $contextTerm = new Terms();
        $contextTerm->setTerms('context.title', [$this->contextTitle]);

        return [$contextTerm];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}