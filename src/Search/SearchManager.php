<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Search;

use App\Search\FilterConditions\FilterConditionInterface;
use App\Search\FilterConditions\RoomFilterCondition;
use App\Search\QueryConditions\QueryConditionInterface;
use App\Utils\ItemService;
use App\Utils\UserService;
use Elastica\Aggregation as Aggregations;
use Elastica\Query as Queries;
use FOS\ElasticaBundle\Finder\TransformedFinder;

class SearchManager
{
    private $query;

    /**
     * @var array FilterConditionInterface
     */
    private array $filterConditions = [];

    /**
     * @var array QueryConditionInterface
     */
    private array $queryConditions = [];

    public function __construct(private TransformedFinder $commsyFinder, private UserService $userService, private ItemService $itemService)
    {
    }

    public function addFilterCondition(FilterConditionInterface $filterCondition)
    {
        $this->filterConditions[] = $filterCondition;
    }

    public function addQueryCondition(QueryConditionInterface $queryCondition)
    {
        $this->queryConditions[] = $queryCondition;
    }

    public function getResults($sortArguments = [])
    {
        // create our basic query
        $query = new Queries();
        $boolQuery = new Queries\BoolQuery();

        // query context
        $contextQuery = $this->createContextQuery();
        $boolQuery->addMust($contextQuery);

        // filter context
        $contextFilter = $this->createContextFilter();
        $boolQuery->addFilter($contextFilter);

        $query->setQuery($boolQuery);

        // sorting
        if (!empty($sortArguments)) {
            $query->setSort($sortArguments);
        }

        // aggregation
        $typeAggregation = new Aggregations\Terms('rubrics');
        $typeAggregation->setField('rubric');
        $query->addAggregation($typeAggregation);

        $creatorAggregation = new Aggregations\Terms('creators');
        $creatorAggregation->setField('creator.fullName.raw');
        // return at most 100 of the most prolific creators (default is 10)
        $creatorAggregation->setSize(100);
        $query->addAggregation($creatorAggregation);

        $hashtagsAggregation = new Aggregations\Terms('hashtags');
        $hashtagsAggregation->setField('hashtags');
        // return at most 100 of the most used hashtags (default is 10)
        $hashtagsAggregation->setSize(100);
        $query->addAggregation($hashtagsAggregation);

        $categoriesAggregation = new Aggregations\Terms('tags');
        $categoriesAggregation->setField('tags');
        // return at most 100 of the most used categories (default is 10)
        $categoriesAggregation->setSize(100);
        $query->addAggregation($categoriesAggregation);

        $contextsAggregation = new Aggregations\Terms('contexts');
        $contextsAggregation->setField('context.title');
        // return at most 100 of the most used contexts (default is 10)
        $contextsAggregation->setSize(100);
        $query->addAggregation($contextsAggregation);

        $statusesAggregation = new Aggregations\Terms('todostatuses');
        $statusesAggregation->setField('status');
        // return at most 100 of the most used statuses (default is 10)
        $statusesAggregation->setSize(100);
        $query->addAggregation($statusesAggregation);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getLinkedItemResults()
    {
        // create our basic query
        $query = new Queries();
        $boolQuery = new Queries\BoolQuery();

        // query context
        $contextQuery = $this->createContextQuery();
        $boolQuery->addMust($contextQuery);

        // filter context
        $contextFilter = $this->createContextFilter();
        $boolQuery->addFilter($contextFilter);

        $query->setQuery($boolQuery);

        return $this->commsyFinder->createPaginatorAdapter($query);
    }

    public function getRoomResults()
    {
        $query = new Queries();
        $boolQuery = new Queries\BoolQuery();

        // query context
        $contextQuery = $this->createContextQuery();
        $boolQuery->addMust($contextQuery);

        // filter context
        $roomFilterCondition = new RoomFilterCondition($this->userService);
        $this->addFilterCondition($roomFilterCondition);

        $contextFilter = $this->createContextFilter();
        $boolQuery->addFilter($contextFilter);

        $query->setQuery($boolQuery);

        // sort by activity
        $sortArray = ['activity' => ['order' => 'desc', 'unmapped_type' => 'long']];

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
     * Parses the buckets from the given aggregation result and returns an associative
     * array containing the bucket keys as keys and their doc counts as values.
     *
     * @param array $aggregationResult aggregation result generated by ElasticSearch
     */
    public function countsByKeyFromAggregation(array $aggregationResult): array
    {
        $countsByKey = [];
        foreach ($aggregationResult['buckets'] as $bucket) {
            $countsByKey[$bucket['key']] = $bucket['doc_count'];
        }

        return $countsByKey;
    }

    /**
     * Creates a Terms or Range Filter to restrict the search to contexts, the
     * user is allowed to access.
     *
     * @return Queries\BoolQuery
     */
    private function createContextFilter()
    {
        $boolQuery = new Queries\BoolQuery();

        foreach ($this->filterConditions as $filterCondition) {
            /** @var FilterConditionInterface $filterCondition */
            $conditions = $filterCondition->getConditions();
            foreach ($conditions as $condition) {
                switch ($filterCondition->getOperator()) {
                    case FilterConditionInterface::BOOL_MUST:
                        $boolQuery->addMust($condition);
                        break;
                    case FilterConditionInterface::BOOL_SHOULD:
                        $boolQuery->addShould($condition);
                        break;
                }
            }
        }

        return $boolQuery;
    }

    /**
     * Creates & returns a function score query for the context based on the registered query conditions,
     * or if there are no query conditions, returns a query that matches everything.
     *
     * @return Queries\FunctionScore|Queries\MatchAll
     */
    private function createContextQuery()
    {
        if (empty($this->queryConditions)) {
            return new Queries\MatchAll();
        }

        $boolQuery = new Queries\BoolQuery();

        foreach ($this->queryConditions as $queryCondition) {
            /** @var QueryConditionInterface $queryCondition */
            $conditions = $queryCondition->getConditions();
            foreach ($conditions as $condition) {
                switch ($queryCondition->getOperator()) {
                    case QueryConditionInterface::BOOL_MUST:
                        $boolQuery->addMust($condition);
                        break;
                    case QueryConditionInterface::BOOL_SHOULD:
                        $boolQuery->addShould($condition);
                        break;
                }
            }
        }

        $functionScoreQuery = new Queries\FunctionScore();
        $functionScoreQuery->setQuery($boolQuery);
        $functionScoreQuery->setScoreMode(Queries\FunctionScore::SCORE_MODE_SUM);
        $functionScoreQuery->setBoostMode(Queries\FunctionScore::BOOST_MODE_SUM);

        $functionScoreQuery->addDecayFunction(Queries\FunctionScore::DECAY_GAUSS, 'modificationDate', 'now', '30d', null, 0.1, 15);

        return $functionScoreQuery;
    }
}
