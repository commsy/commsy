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

use DateTime;
use DateTimeInterface;
use Elastica\Query\Range;

class ModificationDateFilterCondition implements FilterConditionInterface
{
    private ?DateTime $startDate = null;

    private ?DateTime $endDate = null;

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): ModificationDateFilterCondition
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): ModificationDateFilterCondition
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return Range[]
     */
    public function getConditions(): array
    {
        if (null === $this->startDate && null === $this->endDate) {
            return [];
        }

        $rangeParams = [];
        if (null !== $this->startDate) {
            $rangeParams['gte'] = $this->startDate->format(DateTimeInterface::RFC3339);
        }
        if (null !== $this->endDate) {
            $rangeParams['lte'] = $this->endDate->format(DateTimeInterface::RFC3339);
        }

        $modificationDateRange = new Range();
        $modificationDateRange->addField('modificationDate', $rangeParams);

        return [$modificationDateRange];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
