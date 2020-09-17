<?php
namespace App\Model;

/**
 * Data class that represents a time pulse template
 *
 * Time pulse templates are used to generate the actual time pulse entries
 * (as labels via cs_time_manager)
 * @see TimePulsesService
 */
class TimePulseTemplate
{
    /**
     * @var integer|null
     */
    private $id;

    /**
     * @var integer
     */
    private $contextId;

    /**
     * @var string|null
     */
    private $titleGerman;

    /**
     * @var string|null
     */
    private $titleEnglish;

    /**
     * @var integer
     */
    private $startDay;

    /**
     * @var integer
     */
    private $startMonth;

    /**
     * @var integer
     */
    private $endDay;

    /**
     * @var integer
     */
    private $endMonth;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): TimePulseTemplate
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     */
    public function setContextId(int $contextId): TimePulseTemplate
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitleGerman(): ?string
    {
        return $this->titleGerman;
    }

    /**
     * @param string $titleGerman
     */
    public function setTitleGerman(string $titleGerman): TimePulseTemplate
    {
        $this->titleGerman = $titleGerman;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitleEnglish(): ?string
    {
        return $this->titleEnglish;
    }

    /**
     * @param string $titleEnglish
     */
    public function setTitleEnglish(string $titleEnglish): TimePulseTemplate
    {
        $this->titleEnglish = $titleEnglish;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    /**
     * @param int $startDay
     */
    public function setStartDay(int $startDay): TimePulseTemplate
    {
        $this->startDay = $startDay;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStartMonth(): ?int
    {
        return $this->startMonth;
    }

    /**
     * @param int $startMonth
     */
    public function setStartMonth(int $startMonth): TimePulseTemplate
    {
        $this->startMonth = $startMonth;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEndDay(): ?int
    {
        return $this->endDay;
    }

    /**
     * @param int $endDay
     */
    public function setEndDay(int $endDay): TimePulseTemplate
    {
        $this->endDay = $endDay;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    /**
     * @param int $endMonth
     */
    public function setEndMonth(int $endMonth): TimePulseTemplate
    {
        $this->endMonth = $endMonth;
        return $this;
    }

    /**
     * Comparison callback for sorting two items first by start month & day, then by end month & day
     *
     * @param TimePulseTemplate $a first item
     * @param TimePulseTemplate $b second item
     * @return int compare result
     */
    public function compare(TimePulseTemplate $a, TimePulseTemplate $b)
    {
        $cmp = $a->getStartMonth() <=> $b->getStartMonth();
        if ($cmp !== 0) {
            return $cmp;
        }

        $cmp = $a->getStartDay() <=> $b->getStartDay();
        if ($cmp !== 0) {
            return $cmp;
        }

        $cmp = $a->getEndMonth() <=> $b->getEndMonth();
        if ($cmp !== 0) {
            return $cmp;
        }

        $cmp = $a->getEndDay() <=> $b->getEndDay();

        return $cmp;
    }
}
