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

namespace App\Search\QueryConditions;

use Elastica\Query\MatchQuery;

class RoomQueryCondition implements QueryConditionInterface
{
    private ?string $query = null;

    public function setQuery(string $query): RoomQueryCondition
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return MatchQuery[]
     */
    public function getConditions(): array
    {
        if ('' === $this->query) {
            return [];
        }

        // title
        $titleMatch = new MatchQuery();
        $titleMatch->setFieldQuery('title', $this->query);

        // description
        $descriptionMatch = new MatchQuery();
        $descriptionMatch->setFieldQuery('roomDescription', $this->query);

        // contact persons
        $contactPersonsMatch = new MatchQuery();
        $contactPersonsMatch->setFieldQuery('contactPersons', $this->query);

        return [$titleMatch, $descriptionMatch, $contactPersonsMatch];
    }

    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_SHOULD;
    }
}
