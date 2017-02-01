<?php
namespace CommsyBundle\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Utils\ItemService;

use Elastica\Query as Queries;
use Elastica\Aggregation as Aggregations;

class SearchManager
{
    private $commsyFinder;
    private $userService;
    private $itemService;

    private $query;
    private $rubric;
    private $context;

    public function __construct(TransformedFinder $commsyFinder, UserService $userService, ItemService $itemService)
    {
        $this->commsyFinder = $commsyFinder;
        $this->userService = $userService;
        $this->itemService = $itemService;
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
        $matchQuery->setFieldOperator('_all', 'and');

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
        $multiMatchQuery = new Queries\MultiMatch();
        $multiMatchQuery->setQuery($this->query);
        $multiMatchQuery->setFields(['title', 'firstName', 'lastName']);
        $multiMatchQuery->setOperator('and');

        $boolQuery->addMust($multiMatchQuery);

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

    public function getLinkedItemResults($roomId, $itemId)
    {
        $boolQuery = new Queries\BoolQuery();

        // query context
        $multiMatchQuery = new Queries\MultiMatch();
        $multiMatchQuery->setQuery($this->query);
        $multiMatchQuery->setFields(['title', 'firstName', 'lastName']);
        $multiMatchQuery->setOperator('and');

        $boolQuery->addMust($multiMatchQuery);

        // filter context
        // we are only interested in entries for the current room
        $contextFilter = new Queries\Terms();
        $contextFilter->setTerms('contextId', [$roomId]);

        $boolQuery->addFilter($contextFilter);

        // results must not match already linked entries or the item itself
        $excludeFilter = $this->createExcludeFilter($itemId);

        $boolQuery->addMustNot($excludeFilter);

        return $this->commsyFinder->findHybrid($boolQuery, 10);
    }


    public function createExcludeFilter($itemId)
    {
        $linkedItems = $this->itemService->getLinkedItemIdArray($itemId);

        $itemFilter = new Queries\Ids();
        $itemFilter->setIds(array_merge($linkedItems, [$itemId]));

        return $itemFilter;

    }

    /**
     * Creats a Terms Filter to restrict the search to contexts, the
     * user is allowed to access
     * 
     * @return \Elastica\Query\Terms The terms filter
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