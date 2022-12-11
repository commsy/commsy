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

use Elastica\Query\MultiMatch;

class TitleQueryCondition implements QueryConditionInterface
{
    private ?string $title = null;

    public function setTitle(string $title): TitleQueryCondition
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return MultiMatch[]
     */
    public function getConditions(): array
    {
        if ('' === $this->title) {
            return [];
        }

        $titleMatch = new MultiMatch();
        $titleMatch->setQuery($this->title);
        $titleMatch->setType('most_fields');

        $fields = [
            // first level title
            'title^5',
            'title.raw^20',

            // discussion articles
            'discussionarticles.subject^1.3',

            // others
            'steps.title',
            'sections.title',
        ];

        $titleMatch->setFields($fields);

        return [$titleMatch];
    }

    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
