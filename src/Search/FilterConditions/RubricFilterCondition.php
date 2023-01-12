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

namespace App\Search\FilterConditions;

use Elastica\Query\Terms;

class RubricFilterCondition implements FilterConditionInterface
{
    private ?string $rubric = null;

    public function setRubric(string $rubric): RubricFilterCondition
    {
        $this->rubric = $rubric;

        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ('all' === $this->rubric) {
            return [];
        }

        $rubricTerms = new Terms('rubric');
        $rubricTerms->setTerms([$this->rubric]);

        return [$rubricTerms];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
