<?php


namespace App\Search\FilterConditions;


use DateTime;
use DateTimeInterface;
use Elastica\Query\Range;

class ModificationDateFilterCondition implements FilterConditionInterface
{
    /**
     * @var DateTime|null $startDate
     */
    private ?DateTime $startDate;

    /**
     * @var DateTime|null $endDate
     */
    private ?DateTime $endDate;

    /**
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime|null $startDate
     * @return ModificationDateFilterCondition
     */
    public function setStartDate(?DateTime $startDate): ModificationDateFilterCondition
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime|null $endDate
     * @return ModificationDateFilterCondition
     */
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
        if ($this->startDate === null && $this->endDate === null) {
            return [];
        }

        $rangeParams = [];
        if ($this->startDate !== null) {
            $rangeParams["gte"] = $this->startDate->format(DateTimeInterface::RFC3339);
        }
        if ($this->endDate !== null) {
            $rangeParams["lte"] = $this->endDate->format(DateTimeInterface::RFC3339);
        }

        $modificationDateRange= new Range();
        $modificationDateRange->addField("modificationDate", $rangeParams);

        return [$modificationDateRange];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}