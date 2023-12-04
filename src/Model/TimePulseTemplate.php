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

namespace App\Model;

/**
 * Data class that represents a time pulse template.
 *
 * Time pulse templates are used to generate the actual time pulse entries
 * (as labels via cs_time_manager)
 *
 * @see TimePulsesService
 */
class TimePulseTemplate
{
    private ?int $id = null;

    private ?int $contextId = null;

    private ?string $titleGerman = null;

    private ?string $titleEnglish = null;

    private ?int $startDay = null;

    private ?int $startMonth = null;

    private ?int $endDay = null;

    private ?int $endMonth = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): TimePulseTemplate
    {
        $this->id = $id;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): TimePulseTemplate
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getTitleGerman(): ?string
    {
        return $this->titleGerman;
    }

    public function setTitleGerman(string $titleGerman): TimePulseTemplate
    {
        $this->titleGerman = $titleGerman;

        return $this;
    }

    public function getTitleEnglish(): ?string
    {
        return $this->titleEnglish;
    }

    public function setTitleEnglish(string $titleEnglish): TimePulseTemplate
    {
        $this->titleEnglish = $titleEnglish;

        return $this;
    }

    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    public function setStartDay(int $startDay): TimePulseTemplate
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartMonth(): ?int
    {
        return $this->startMonth;
    }

    public function setStartMonth(int $startMonth): TimePulseTemplate
    {
        $this->startMonth = $startMonth;

        return $this;
    }

    public function getEndDay(): ?int
    {
        return $this->endDay;
    }

    public function setEndDay(int $endDay): TimePulseTemplate
    {
        $this->endDay = $endDay;

        return $this;
    }

    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    public function setEndMonth(int $endMonth): TimePulseTemplate
    {
        $this->endMonth = $endMonth;

        return $this;
    }

    /**
     * Comparison callback for sorting two items first by start month & day, then by end month & day.
     *
     * @param TimePulseTemplate $a first item
     * @param TimePulseTemplate $b second item
     *
     * @return int compare result
     */
    public static function compare(TimePulseTemplate $a, TimePulseTemplate $b): int
    {
        $cmp = $a->getStartMonth() <=> $b->getStartMonth();
        if (0 !== $cmp) {
            return $cmp;
        }

        $cmp = $a->getStartDay() <=> $b->getStartDay();
        if (0 !== $cmp) {
            return $cmp;
        }

        $cmp = $a->getEndMonth() <=> $b->getEndMonth();
        if (0 !== $cmp) {
            return $cmp;
        }

        $cmp = $a->getEndDay() <=> $b->getEndDay();

        return $cmp;
    }
}
