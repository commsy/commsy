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
     */
    protected array $indices = [];

    protected array $types = [];

    /**
     * Add array of indices at once.
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
     * @throws InvalidException
     */
    public function addIndex(Index|string $index): MultiIndex
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }

        if (!is_scalar($index)) {
            throw new InvalidException('Invalid param type');
        }

        $this->indices[] = (string) $index;

        return $this;
    }

    /**
     * @param AbstractQuery|array|Collapse|Query|string|Suggest $query
     * @param null                                              $options
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

    public function getTypes(): array
    {
        return $this->types;
    }

    public function addTypes(array $types): void
    {
        $this->types = $types;
    }
}
