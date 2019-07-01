<?php


namespace App\Search\QueryConditions;


use Elastica\Query\Match;

class RoomQueryCondition implements QueryConditionInterface
{
    /**
     * @var string $query
     */
    private $query;

    /**
     * @param string $query
     * @return RoomQueryCondition
     */
    public function setQuery(string $query): RoomQueryCondition
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return Match[]
     */
    public function getConditions(): array
    {
        if ($this->query === '') {
            return [];
        }

        // title
        $titleMatch = new Match();
        $titleMatch->setFieldQuery('title', $this->query);

        // description
        $descriptionMatch = new Match();
        $descriptionMatch->setFieldQuery('roomDescription', $this->query);

        // contact persons
        $contactPersonsMatch = new Match();
        $contactPersonsMatch->setFieldQuery('contactPersons', $this->query);

        return [$titleMatch, $descriptionMatch, $contactPersonsMatch];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_SHOULD;
    }
}
