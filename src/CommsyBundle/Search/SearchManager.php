<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Commsy\LegacyBundle\Utils\UserService;

use Elastica\Query as Queries;
use Elastica\Filter as Filters;
use Elastica\Aggregation as Aggregations;

class SearchManager
{
    private $commsyFinder;
    private $userService;

    private $query;
    private $rubric;
    private $context;

    public function __construct(TransformedFinder $commsyFinder, UserService $userService)
    {
        $this->commsyFinder = $commsyFinder;
        $this->userService = $userService;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setRubric($rubric)
    {
        $this->rubric = $rubric;
    }

    public function getResults()
    {
        // create our basic query
        $query = new \Elastica\Query();

        // query
        $fieldQuery = new Queries\MatchPhrase();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $query->setQuery($fieldQuery);

        // filter
        $contextFilter = $this->createContextFilter();

        $query->setFilter($contextFilter);

        // aggregations
        $filterAggregation = new Aggregations\Filter('filterContext');
        $filterAggregation->setFilter($contextFilter);

        $termsAggregation = new Aggregations\Terms('contexts');
        $termsAggregation->setField('contextId');
        $filterAggregation->addAggregation($termsAggregation);

        //$query->addAggregation($filterAggregation);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getInstantResults()
    {
        // Filtered query. Needs a query and a filter.
        $filteredQuery = new Queries\Filtered();

        // query
        $fieldQuery = new Queries\MatchPhrasePrefix();
        $fieldQuery->setFieldQuery('_all', $this->query);

        $filteredQuery->setQuery($fieldQuery);

        // filter
        $contextFilter = $this->createContextFilter();

        $filteredQuery->setFilter($contextFilter);

        return $this->commsyFinder->findHybrid($filteredQuery, 10);
    }

    /**
     * Creats a Terms Filter to restrict the search to contexts, the
     * user is allowed to access
     * 
     * @return Filters\Terms The terms filter
     */
    private function createContextFilter()
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contextIds = [];

        foreach ($searchableRooms as $searchableRoom) {
            $contextIds[] = $searchableRoom->getItemId();
        }

        $contextFilter = new Filters\Terms();
        $contextFilter->setTerms('contextId', $contextIds);

        return $contextFilter;
    }
}