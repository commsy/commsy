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

class SingleContextTitleFilterCondition implements FilterConditionInterface
{
    private ?string $contextTitle = null;

    public function setContextTitle(string $contextTitle): SingleContextTitleFilterCondition
    {
        $this->contextTitle = $contextTitle;

        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ('all' === $this->contextTitle) {
            return [];
        }

        $contextTerm = new Terms('context.title');
        $contextTerm->setTerms([$this->contextTitle]);

        return [$contextTerm];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
