<?php


namespace App\Search\QueryConditions;


use Elastica\Query\Match;

class DescriptionQueryCondition implements QueryConditionInterface
{
    /**
     * @var string $description
     */
    private $description;

    /**
     * @param string $description
     * @return DescriptionQueryCondition
     */
    public function setDescription(string $description): DescriptionQueryCondition
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Match[]
     */
    public function getConditions(): array
    {
        if ($this->description === '') {
            return [];
        }

        $descriptionMatch = new Match();
        $descriptionMatch->setFieldQuery('description', $this->description);

        return [$descriptionMatch];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
