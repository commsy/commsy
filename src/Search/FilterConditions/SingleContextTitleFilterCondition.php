<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class SingleContextTitleFilterCondition implements FilterConditionInterface
{
    /**
     * @var string|null $contextTitle
     */
    private ?string $contextTitle;

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

        $contextTerm = new Terms('context.title');
        $contextTerm->setTerms([$this->contextTitle]);

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