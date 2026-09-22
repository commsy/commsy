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
use App\Rubric\Todo\TodoStatus;
use Elastica\Query\Term;

class TodoStatusFilterCondition implements FilterConditionInterface
{
    /**
     * Everything above the three statuses of {@see TodoStatus} is a filter of
     * its own rather than a status, see {@see self::NOT_DONE}.
     */
    private ?int $todoStatus = null;

    /**
     * Not a status but a question about one: everything that is not done.
     */
    private const NOT_DONE = 4;

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
            case TodoStatus::NotStarted->value:
            case TodoStatus::InProgress->value:
            case TodoStatus::Done->value:
                $todoTerm = new Term();
                $todoTerm->setTerm('status', $this->todoStatus);
                $todoFilters[] = $todoTerm;
                break;
            case self::NOT_DONE:
                $todoRange = new Range();
                $todoRange->addField('status', [
                    'gte' => TodoStatus::NotStarted->value,
                    'lt' => TodoStatus::Done->value,
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
