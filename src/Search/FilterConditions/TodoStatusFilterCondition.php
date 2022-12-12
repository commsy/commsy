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

use Elastica\Query\AbstractQuery;
use Elastica\Query\Range;
use Elastica\Query\Term;

class TodoStatusFilterCondition implements FilterConditionInterface
{
    /**
     * @var int|null Todo status (1: 'pending', 2: 'in progress', 3: 'done')
     */
    private ?int $todoStatus = null;

    public function setTodoStatus(int $todoStatus): self
    {
        $this->todoStatus = $todoStatus;

        return $this;
    }

    /**
     * @return AbstractQuery[]
     */
    public function getConditions(): array
    {
        $todoFilters = [];

        switch ($this->todoStatus) {
            case 1:
                // pending
                $todoTerm = new Term();
                $todoTerm->setTerm('status', 1);
                $todoFilters[] = $todoTerm;
                break;
            case 2:
                // in progress
                $todoTerm = new Term();
                $todoTerm->setTerm('status', 2);
                $todoFilters[] = $todoTerm;
                break;
            case 3:
                // done
                $todoTerm = new Term();
                $todoTerm->setTerm('status', 3);
                $todoFilters[] = $todoTerm;
                break;
            case 4:
                // not done (pending + in progress)
                $todoRange = new Range();
                $todoRange->addField('status', [
                    'gte' => 1,
                    'lt' => 3,
                ]);
                $todoFilters[] = $todoRange;
                break;
        }

        return $todoFilters;
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
