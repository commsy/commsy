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

declare(strict_types=1);

namespace Tests\Support\Search;

use Elastica\Query;
use Elastica\Response;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\SearchableInterface;
use LogicException;

/**
 * Stands in for the search server in tests: answers every search with the
 * hits and aggregations it was given and keeps the queries it was asked.
 * Everything above it runs as in production: the conditions, the query, the
 * loading of the entities found and what the pages make of them.
 */
final class FakeSearchServer implements SearchableInterface
{
    /** @var list<array{index: string, id: int}> */
    private array $hits = [];

    /** @var array<string, list<array{key: int|string, doc_count: int}>> */
    private array $buckets = [];

    /** @var list<array<string, mixed>> */
    private array $queries = [];

    public function __construct(private readonly string $indexPrefix)
    {
    }

    /**
     * @param list<array{0: string, 1: int}>                $hits    index without prefix ('material', 'room', ...) and id, best first
     * @param array<string, array<int|string, int>> $buckets document counts by key, per aggregation name
     */
    public function answerWith(array $hits, array $buckets = []): void
    {
        $this->hits = array_map(fn (array $hit): array => ['index' => $this->indexPrefix.'_'.$hit[0], 'id' => $hit[1]], $hits);
        $this->buckets = [];
        foreach ($buckets as $name => $counts) {
            $this->buckets[$name] = [];
            foreach ($counts as $key => $count) {
                $this->buckets[$name][] = ['key' => $key, 'doc_count' => $count];
            }
        }
    }

    /**
     * @return list<array<string, mixed>> the queries asked so far, as sent
     */
    public function queries(): array
    {
        return $this->queries;
    }

    /**
     * @return array<string, mixed> the query asked last, as sent
     */
    public function lastQuery(): array
    {
        return $this->queries[array_key_last($this->queries) ?? throw new LogicException('Nothing was searched.')];
    }

    public function search($query = '', ?array $options = null): ResultSet
    {
        $query = Query::create($query);
        $asked = $query->toArray();
        $this->queries[] = $asked;

        $from = (int) ($asked['from'] ?? 0);
        $size = (int) ($asked['size'] ?? \count($this->hits));
        $page = array_slice($this->hits, $from, $size);

        $aggregations = [];
        foreach (array_keys($asked['aggs'] ?? []) as $name) {
            $aggregations[$name] = ['buckets' => $this->buckets[$name] ?? []];
        }

        $response = new Response([
            'took' => 1,
            'hits' => [
                'total' => ['value' => \count($this->hits), 'relation' => 'eq'],
                'max_score' => 1.0,
                'hits' => array_map(static fn (array $hit): array => ['_index' => $hit['index'], '_id' => (string) $hit['id'], '_score' => 1.0, '_source' => []], $page),
            ],
            'aggregations' => $aggregations,
        ], 200);

        return new ResultSet($response, $query, array_map(static fn (array $hit): Result => new Result($hit), $response->getData()['hits']['hits']));
    }

    public function count($query = '')
    {
        $this->queries[] = Query::create($query)->toArray();

        return \count($this->hits);
    }

    public function createSearch($query = '', ?array $options = null): Search
    {
        throw new LogicException('Tests never talk to a search server.');
    }
}
