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

class DescriptionQueryCondition implements QueryConditionInterface
{
    private ?string $description = null;

    public function setDescription(string $description): DescriptionQueryCondition
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return MultiMatch[]
     */
    public function getConditions(): array
    {
        if ('' === $this->description) {
            return [];
        }

        $descriptionMatch = new MultiMatch();
        $descriptionMatch->setQuery($this->description);
        $descriptionMatch->setType('most_fields');

        $fields = [
            // description
            'description^1.7',

            // discussion articles
            'discussionarticles.description^1.3',

            // others
            'steps.description',
            'sections.description',
        ];

        $descriptionMatch->setFields($fields);

        return [$descriptionMatch];
    }

    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
