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
        // - boost multi-term matches (i.e. if 2 or more terms from the query match)
        $titleMatchMulti = new MatchQuery();
        $titleMatchMulti->setFieldQuery('title', $this->query);
        $titleMatchMulti->setFieldBoost('title', 20.0);
        // - '2<-1': if there are 1 or 2 terms both are required, but for more (n) terms only n-1 terms are required
        $titleMatchMulti->setFieldMinimumShouldMatch('title', '2<-1');

        // - don't ignore but use the default boost (1.0) if just a single term matches
        $titleMatch = new MatchQuery();
        $titleMatch->setFieldQuery('title', $this->query);

        // description
        $descriptionMatchMulti = new MatchQuery();
        $descriptionMatchMulti->setFieldQuery('roomDescription', $this->query);
        $descriptionMatchMulti->setFieldBoost('roomDescription', 5.0);
        $descriptionMatchMulti->setFieldMinimumShouldMatch('roomDescription', '2<-1');

        $descriptionMatch = new MatchQuery();
        $descriptionMatch->setFieldQuery('roomDescription', $this->query);

        // contact persons
        $contactPersonsMatchMulti = new MatchQuery();
        $contactPersonsMatchMulti->setFieldQuery('contactPersons', $this->query);
        $contactPersonsMatchMulti->setFieldBoost('contactPersons', 5.0);
        $contactPersonsMatchMulti->setFieldMinimumShouldMatch('contactPersons', '2<-1');

        $contactPersonsMatch = new MatchQuery();
        $contactPersonsMatch->setFieldQuery('contactPersons', $this->query);

        return [$titleMatchMulti, $titleMatch, $descriptionMatchMulti, $descriptionMatch, $contactPersonsMatchMulti, $contactPersonsMatch];
    }

    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_SHOULD;
    }
}
