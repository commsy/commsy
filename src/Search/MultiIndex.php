<?php

namespace App\Search;

use Elastica\Collapse;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\ResultSet\BuilderInterface;
use Elastica\Search;
use Elastica\Suggest;

class MultiIndex extends Index
{
    /**
     * Array of indices.
     *
     * @var array
     */
    protected array $indices = [];
    /**
     * @var array
     */
    protected array $types = [];

    /**
     * Add array of indices at once.
     *
     * @param array $indices
     *
     * @return $this
     */
    public function addIndices(array $indices = []): MultiIndex
    {
        foreach ($indices as $index) {
            $this->addIndex($index);
        }

        return $this;
    }

    /**
     * Adds a index to the list.
     *
     * @param Index|string $index Index object or string
     *
     * @return $this
     * @throws InvalidException
     *
     */
    public function addIndex($index): MultiIndex
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }

        if (!is_scalar($index)) {
            throw new InvalidException('Invalid param type');
        }

        $this->indices[] = (string)$index;

        return $this;
    }

    /**
     * @param AbstractQuery|array|Collapse|Query|string|Suggest $query
     * @param null $options
     * @param BuilderInterface|null $builder
     *
     * @return Search
     */
    public function createSearch($query = '', $options = null, ?BuilderInterface $builder = null): Search
    {

        $search = new Search($this->getClient(), $builder);
        $search->addIndices($this->getIndices());
        $search->setOptionsAndQuery($options, $query);

        return $search;
    }

    /**
     * Return array of indices.
     *
     * @return array List of index names
     */
    public function getIndices(): array
    {
        return $this->indices;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array $types
     */
    public function addTypes(array $types): void
    {
        $this->types = $types;
    }
}
