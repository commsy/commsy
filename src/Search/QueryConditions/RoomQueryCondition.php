<?php


namespace App\Search\QueryConditions;


use Elastica\Query\MatchQuery;

class RoomQueryCondition implements QueryConditionInterface
{
    /**
     * @var string|null $query
     */
    private ?string $query;

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
     * @return MatchQuery[]
     */
    public function getConditions(): array
    {
        if ($this->query === '') {
            return [];
        }

        // title
        $titleMatch = new MatchQuery();
        $titleMatch->setFieldQuery('title', $this->query);

        // description
        $descriptionMatch = new MatchQuery();
        $descriptionMatch->setFieldQuery('roomDescription', $this->query);

        // contact persons
        $contactPersonsMatch = new MatchQuery();
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
