<?php
namespace App\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;

use App\Utils\UserService;
use App\Utils\ItemService;

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
//        $filterAggregation = new Aggregations\Filter('filterContext');
//        $filterAggregation->setFilter($contextFilter);

//        $termsAggregation = new Aggregations\Terms('contexts');
//        $termsAggregation->setField('contextId');
//        $filterAggregation->addAggregation($termsAggregation);

//        $query->addAggregation($filterAggregation);

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
        $idsQuery = new Queries\Ids($contextIds);

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
     * @return Queries\BoolQuery
     */
    private function createContextFilter()
    {
        $bool = new Queries\BoolQuery();

        if ($this->context) {
            $contextFilter = new Queries\Terms();
            $contextFilter->setTerms('contextId', [$this->context]);
            $bool->addShould($contextFilter);

            $parentIdFilter = new Queries\Terms();
            $parentIdFilter->setTerms('parentId', [$this->context]);
            $bool->addShould($parentIdFilter);
        } else {
            $searchableRooms = $this->userService->getSearchableRooms($this->userService->getCurrentUserItem());

            $contextIds = [];
            foreach ($searchableRooms as $searchableRoom) {
                $contextIds[] = $searchableRoom->getItemId();
            }

            $contextFilter = new Queries\Terms();
            $contextFilter->setTerms('contextId', $contextIds);
            $bool->addMust($contextFilter);
        }

        return $bool;
    }

    private function createContextQuery()
    {
        if (empty(trim($this->query))) {
            return new Queries\MatchAll();
        }

        $matchQuery = new Queries\MultiMatch();
        $matchQuery->setQuery($this->query);
        $matchQuery->setType('most_fields');
//        $matchQuery->setTieBreaker(0.3);
//        $matchQuery->setMinimumShouldMatch('80%');
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
        if ($queryAsDate = \DateTime::createFromFormat('d.m.Y', $this->query)) {
            $fields[] = 'modificationDate';
            $matchQuery->setQuery($queryAsDate->format('Y-m-d'));
        }
        if ($queryAsDate = \DateTime::createFromFormat('Y-m-d', $this->query)) {
            $fields[] = 'modificationDate';
            $matchQuery->setQuery($queryAsDate->format('Y-m-d'));
        }

        $matchQuery->setFields($fields);

        $functionScoreQuery = new Queries\FunctionScore();
        $functionScoreQuery->setQuery($matchQuery);
        $functionScoreQuery->setScoreMode(Queries\FunctionScore::SCORE_MODE_SUM);
        $functionScoreQuery->setBoostMode(Queries\FunctionScore::BOOST_MODE_SUM);

        $functionScoreQuery->addDecayFunction(Queries\FunctionScore::DECAY_GAUSS, 'modificationDate', 'now', '30d', null, 0.1, 15);

        return $functionScoreQuery;
    }
}