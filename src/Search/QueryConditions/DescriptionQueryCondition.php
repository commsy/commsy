<?php


namespace App\Search\QueryConditions;


use Elastica\Query\MultiMatch;

class DescriptionQueryCondition implements QueryConditionInterface
{
    /**
     * @var string|null $description
     */
    private ?string $description;

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
     * @return MultiMatch[]
     */
    public function getConditions(): array
    {
        if ($this->description === '') {
            return [];
        }

        $descriptionMatch = new MultiMatch();
        $descriptionMatch->setQuery($this->description);
        $descriptionMatch->setType('most_fields');

        $fields = [
            // description
            'description^1.7',

            // discussion articles
            'discussionarticles.description^1.3',

            // others
            'steps.description',
            'sections.description',
        ];

        $descriptionMatch->setFields($fields);

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
