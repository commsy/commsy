<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\Query as Queries;
use Elastica\Filter as Filters;
use Elastica\Aggregation as Aggregations;

class SearchManager
{
    private $commsyFinder;

    private $query;
    private $rubric;
    private $context;

    public function __construct(TransformedFinder $commsyFinder)
    {
        $this->commsyFinder = $commsyFinder;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setRubric($rubric)
    {
        $this->rubric = $rubric;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getResults()
    {
        $fieldQuery = new Queries\MatchPhrase();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $contextFilter = new Filters\Term();
        $contextFilter->setTerm('contextId', $this->context);

        $filterAggregation = new Aggregations\Filter('filterContext');
        $filterAggregation->setFilter($contextFilter);

        $termsAggregation = new Aggregations\Terms('contexts');
        $termsAggregation->setField('contextId');
        $filterAggregation->addAggregation($termsAggregation);

        $query = new \Elastica\Query();
        $query->setQuery($fieldQuery);
        $query->addAggregation($filterAggregation);
        $query->setFilter($contextFilter);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getInstantResults()
    {
        $contextFilter = new Filters\Term();
        $contextFilter->setTerm('contextId', $this->context);

        $fieldQuery = new Queries\MatchPhrasePrefix();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $filteredQuery = new Queries\Filtered();
        $filteredQuery->setQuery($fieldQuery);
        $filteredQuery->setFilter($contextFilter);

        return $this->commsyFinder->find($filteredQuery);
    }
}