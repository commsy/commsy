<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\Query as Queries;
use Elastica\Filter as Filters;

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
        $contextFilter = new Filters\Term();
        $contextFilter->setTerm('contextId', $this->context);

        $fieldQuery = new Queries\MatchPhrase();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $filteredQuery = new Queries\Filtered();
        $filteredQuery->setQuery($fieldQuery);
        $filteredQuery->setFilter($contextFilter);

        return $this->checkResults($this->commsyFinder->find($filteredQuery));
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

        return $this->checkResults($this->commsyFinder->find($filteredQuery));
    }

    private function checkResults(array $results) {
        foreach ($results as $result) {
            if (!$result instanceof Searchable) {
                throw new \Exception('Result must implement Searchable');
            }
        }

        return $results;
    }
}