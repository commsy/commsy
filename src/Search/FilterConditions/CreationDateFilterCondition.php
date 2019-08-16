<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Range;

class CreationDateFilterCondition implements FilterConditionInterface
{
    /**
     * @var \DateTime|null $startDate
     */
    private $startDate;

    /**
     * @var \DateTime|null $endDate
     */
    private $endDate;

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime|null $startDate
     * @return CreationDateFilterCondition
     */
    public function setStartDate(?\DateTime $startDate): CreationDateFilterCondition
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime|null $endDate
     * @return CreationDateFilterCondition
     */
    public function setEndDate(?\DateTime $endDate): CreationDateFilterCondition
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

        // TODO: when using PHP >=7.2, the DateTime class constants are defined in DateTimeInterface (i.e., \DateTime::RFC3339 -> \DateTimeInterface::RFC3339)
        $rangeParams = [];
        if ($this->startDate !== null) {
            $rangeParams["gte"] = $this->startDate->format(\DateTime::RFC3339);
        }
        if ($this->endDate !== null) {
            $rangeParams["lte"] = $this->endDate->format(\DateTime::RFC3339);
        }

        $creationDateRange= new Range();
        $creationDateRange->addField("creationDate", $rangeParams);

        return [$creationDateRange];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}