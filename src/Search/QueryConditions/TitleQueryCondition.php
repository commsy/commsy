<?php


namespace App\Search\QueryConditions;


use Elastica\Query\MultiMatch;

class TitleQueryCondition implements QueryConditionInterface
{
    /**
     * @var string $title
     */
    private $title;

    /**
     * @param string $title
     * @return TitleQueryCondition
     */
    public function setTitle(string $title): TitleQueryCondition
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Match[]
     */
    public function getConditions(): array
    {
        if ($this->title === '') {
            return [];
        }

        $titleMatch = new MultiMatch();
        $titleMatch->setQuery($this->title);
        $titleMatch->setType('most_fields');

        $fields = [
            'title^5',
            'title.raw^20',
        ];

        $titleMatch->setFields($fields);

        return [$titleMatch];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
