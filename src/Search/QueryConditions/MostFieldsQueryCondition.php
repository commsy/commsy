<?php


namespace App\Search\QueryConditions;


use Elastica\Query\MultiMatch;

class MostFieldsQueryCondition implements QueryConditionInterface
{
    /**
     * @var string $query
     */
    private $query;

    /**
     * @param string $query
     * @return MostFieldsQueryCondition
     */
    public function setQuery(string $query): MostFieldsQueryCondition
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return MultiMatch[]
     */
    public function getConditions(): array
    {
        if ($this->query === '') {
            return [];
        }

        $multiMatch = new MultiMatch();
        $multiMatch->setQuery($this->query);
        $multiMatch->setType('most_fields');
//        $multiMatch->setTieBreaker(0.3);
//        $multiMatch->setMinimumShouldMatch('80%');

        $fields = [
            // first level title
            'title^5',
            'title.raw^20',

            // description
            'description^1.7',

            // date
//            'modificationDate',

            // files
            'files.content^1.6',
//            'discussionarticles.files.content',
//            'steps.files.content',
//            'sections.files.content',

            // creator, sections
            'creator.fullName^1.5',

            // tags
            'tags^1.4',

            // discussion articles
            'discussionarticles.subject^1.3',
            'discussionarticles.description^1.3',

            // user
            'fullName',

            // others
            'steps.title',
            'sections.title',

            'steps.description',
            'sections.description',

            'userId',
//            'creationDate',
//            'endDate',
//            'datetimeStart',
//            'datetimeEnd',
//            'date',
            'hashtags',

            'annotations',

            'contactPersons',
            'roomDescription',
        ];

        /**
         * In order to search in datetime fields we must ensure to send only search strings that are already in
         * a valid format.
         */
        $queryAsDate = (\DateTime::createFromFormat('d.m.Y', $this->query)) ?: \DateTime::createFromFormat('Y-m-d', $this->query);
        if ($queryAsDate) {
            $fields[] = 'modificationDate';
            $multiMatch->setQuery($queryAsDate->format('Y-m-d'));
        }

        $multiMatch->setFields($fields);

        return [$multiMatch];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
