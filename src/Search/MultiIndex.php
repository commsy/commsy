<?php

namespace App\Search;

use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\ResultSet\BuilderInterface;
use Elastica\Search;

class MultiIndex extends Index
{

    /**
     * Array of indices.
     *
     * @var array
     */
    protected $_indices = [];
    /**
     * @var array
     */
    protected $_types = [];

    /**
     * Add array of indices at once.
     *
     * @param array $indices
     *
     * @return $this
     */
    public function addIndices(array $indices = [])
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
    public function addIndex($index)
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }

        if (!is_scalar($index)) {
            throw new InvalidException('Invalid param type');
        }

        $this->_indices[] = (string)$index;

        return $this;
    }

    /**
     * @param string|array|\Elastica\Query $query
     * @param int|array $options
     * @param BuilderInterface $builder
     *
     * @return Search
     */
    public function createSearch($query = '', $options = null, BuilderInterface $builder = null)
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
    public function getIndices()
    {
        return $this->_indices;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->_types;
    }

    /**
     * @param array $types
     */
    public function addTypes(array $types): void
    {
        $this->_types = $types;
    }
}
