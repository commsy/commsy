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

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getResults()
    {
        // create our basic query
        $query = new \Elastica\Query();

        $boolQuery = new Queries\BoolQuery();

        // query context
        $contextQuery = $this->createContextQuery();

        $boolQuery->addMust($contextQuery);

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

        //$query->addAggregation($filterAggregation);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getLinkedItemResults()
    {
        // create our basic query
        $query = new \Elastica\Query();

        $boolQuery = new Queries\BoolQuery();

        // query context
        $contextQuery = $this->createContextQuery();
        $contextQuery->setFields(['id', 'title', 'firstName', 'lastName']);

        $boolQuery->addMust($contextQuery);

        // filter context
        $contextFilter = $this->createContextFilter();

        $boolQuery->addFilter($contextFilter);

        $query->setQuery($boolQuery);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getRoomResults()
    {
        $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

        $contextIds = [];

        foreach ($searchableRooms as $searchableRoom) {
            $contextIds[] = $searchableRoom->getItemId();
        }

        $query = new \Elastica\Query();

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

        $query->setQuery($boolQuery);

        // sort by activity
        $sortArray = ['activity' => 'desc'];

        $query->setSort($sortArray);

        return $this->commsyFinder->find($query, 50);
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
        $contextFilter = new Queries\Terms();

        if ($this->context) {
            $contextFilter->setTerms('contextId', [$this->context]);
        } else {
            $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

            $contextIds = [];
            foreach ($searchableRooms as $searchableRoom) {
                $contextIds[] = $searchableRoom->getItemId();
            }

            $contextFilter->setTerms('contextId', $contextIds);
        }

        return $contextFilter;
    }

    private function createContextQuery()
    {
        if (empty(trim($this->query))) {
            return new Queries\MatchAll();
        }

        $matchQuery = new Queries\MultiMatch();
        $matchQuery->setQuery($this->query);
        $matchQuery->setType('best_fields');
        $matchQuery->setTieBreaker(0.3);
        $matchQuery->setMinimumShouldMatch('80%');
        $matchQuery->setFields([
            'title^1.3',
            'discussionarticles.subject',
            'steps.title',
            'sections.title',
            'description',
            'discussionarticles.description',
            'steps.description',
            'sections.description',
            'fullName^1.1',
            'userId',
//            'creationDate',
//            'endDate',
//            'datetimeStart',
//            'datetimeEnd',
//            'date',
            'hashtags',
            'tags',
            'annotations',
            'files.content',
//            'discussionarticles.files.content',
//            'steps.files.content',
//            'sections.files.content',
            'contactPersons',
            'roomDescription',
        ]);

        return $matchQuery;
    }
}