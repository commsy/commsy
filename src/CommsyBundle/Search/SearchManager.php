<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Commsy\LegacyBundle\Utils\UserService;

use Elastica\Query as Queries;
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

        $boolQuery = new Queries\BoolQuery();

        // query context
        $matchQuery = new Queries\Match();
        $matchQuery->setFieldQuery('_all', $this->query);

        $boolQuery->addMust($matchQuery);
        
        // filter context
        $contextFilter = $this->createContextFilter();

        $boolQuery->addFilter($contextFilter);

        $query->setQuery($boolQuery);

        // aggregations
        $filterAggregation = new Aggregations\Filter('filterContext');
        $filterAggregation->setFilter($contextFilter);

        $termsAggregation = new Aggregations\Terms('contexts');
        $termsAggregation->setField('contextId');
        $filterAggregation->addAggregation($termsAggregation);

        $query->addAggregation($filterAggregation);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getInstantResults()
    {
        $boolQuery = new Queries\BoolQuery();

        // query context
        $matchTitleQuery = new Queries\Match();
        $matchTitleQuery->setFieldQuery('title', $this->query);

        $matchFirstnameQuery = new Queries\Match();
        $matchFirstnameQuery->setFieldQuery('firstName', $this->query);

        $boolQuery->addShould($matchTitleQuery);
        $boolQuery->addShould($matchFirstnameQuery);

        // filter context
        $contextFilter = $this->createContextFilter();

        $boolQuery->addFilter($contextFilter);

        return $this->commsyFinder->findHybrid($boolQuery, 10);
    }

    public function getRoomResults()
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contextIds = [];

        foreach ($searchableRooms as $searchableRoom) {
            $contextIds[] = $searchableRoom->getItemId();
        }

        $boolQuery = new Queries\BoolQuery();

        // query context
        if (!empty($this->query)) {
            $fieldQuery = new Queries\BoolQuery();

            // title
            $titleQuery = new Queries\Match();
            $titleQuery->setFieldQuery('title', $this->query);

            $fieldQuery->addShould($titleQuery);

            // description
            $descriptionQuery = new Queries\Match();
            $descriptionQuery->setFieldQuery('roomDescription', $this->query);

            $fieldQuery->addShould($descriptionQuery);

            // contact persons
            $contactPersonsQuery = new Queries\Match();
            $contactPersonsQuery->setFieldQuery('contactPersons', $this->query);

            $fieldQuery->addShould($contactPersonsQuery);
        } else {
            // empty query should return all matches
            $fieldQuery = new Queries\MatchAll();
        }

        $boolQuery->addMust($fieldQuery);

        // filter context
        $idsQuery = new Queries\Ids('room', $contextIds);

        $boolQuery->addFilter($idsQuery);

        return $this->commsyFinder->find($boolQuery);
    }

    /**
     * Creats a Terms Filter to restrict the search to contexts, the
     * user is allowed to access
     * 
     * @return Elastica\Query\Terms The terms filter
     */
    private function createContextFilter()
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contextIds = [];

        foreach ($searchableRooms as $searchableRoom) {
            $contextIds[] = $searchableRoom->getItemId();
        }

        $contextFilter = new Queries\Terms();
        $contextFilter->setTerms('contextId', $contextIds);

        return $contextFilter;
    }
}