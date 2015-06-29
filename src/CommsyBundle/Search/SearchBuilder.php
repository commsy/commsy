<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\Query\Filtered;
use Elastica\Query\MatchPhrasePrefix;
use Elastica\Filter\Term;
use Elastica\Filter\Range;

class SearchBuilder
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
        $contextFilter = new Term();
        $contextFilter->setTerm('contextId', $this->context);

        $fieldQuery = new MatchPhrasePrefix();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $filteredQuery = new Filtered();
        $filteredQuery->setQuery($fieldQuery);
        $filteredQuery->setFilter($contextFilter);

        return $this->commsyFinder->find($filteredQuery);
    }
}