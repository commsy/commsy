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

namespace App\Entity;

use App\Utils\EntityDatesTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dates.
 */
#[ORM\Entity]
#[ORM\Table(name: 'dates')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Dates
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'start_time', type: Types::STRING, length: 100, nullable: true)]
    private ?string $startTime = null;

    #[ORM\Column(name: 'end_time', type: Types::STRING, length: 100, nullable: true)]
    private ?string $endTime = null;

    #[ORM\Column(name: 'start_day', type: Types::STRING, length: 100, nullable: false)]
    private string $startDay;

    #[ORM\Column(name: 'end_day', type: Types::STRING, length: 100, nullable: true)]
    private ?string $endDay = null;

    #[ORM\Column(name: 'place', type: Types::STRING, length: 100, nullable: true)]
    private ?string $place = null;

    #[ORM\Column(name: 'datetime_start', type: Types::DATETIME_MUTABLE, nullable: false)]
    private DateTime $datetimeStart;

    #[ORM\Column(name: 'datetime_end', type: Types::DATETIME_MUTABLE, nullable: false)]
    private DateTime $datetimeEnd;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'date_mode', type: Types::BOOLEAN, nullable: false)]
    private bool $dateMode = false;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, length: 65535, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'color', type: Types::STRING, length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(name: 'recurrence_id', type: Types::INTEGER, nullable: true)]
    private ?int $recurrenceId = null;
    #
    #[ORM\Column(name: 'recurrence_pattern', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $recurrencePattern = null;

    #[ORM\Column(name: 'external', type: Types::BOOLEAN, nullable: false)]
    private bool $external = false;

    #[ORM\Column(name: 'whole_day', type: Types::BOOLEAN, nullable: false)]
    private bool $wholeDay = false;

    #[ORM\Column(name: 'uid', type: Types::STRING, length: 255, nullable: true)]
    private ?string $uid = null;

    #[ORM\Column(name: 'calendar_id', type: Types::INTEGER, nullable: true)]
    private ?int $calendarId = null;

    public function isIndexable()
    {
        return null == $this->deleterId && null == $this->deletionDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setDeleterId(?int $deleterId): static
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    public function getDeleterId(): ?int
    {
        return $this->deleterId;
    }

    public function setActivationDate(?DateTime $activationDate): static
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setStartTime(?string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setStartDay(string $startDay): static
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartDay(): string
    {
        return $this->startDay;
    }

    public function setEndDay(?string $endDay): static
    {
        $this->endDay = $endDay;

        return $this;
    }

    public function getEndDay(): ?string
    {
        return $this->endDay;
    }

    public function setPlace(?string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setDatetimeStart(DateTime $datetimeStart): static
    {
        $this->datetimeStart = $datetimeStart;

        return $this;
    }

    public function getDatetimeStart(): DateTime
    {
        return $this->datetimeStart;
    }

    public function setDatetimeEnd(DateTime $datetimeEnd): static
    {
        $this->datetimeEnd = $datetimeEnd;

        return $this;
    }

    public function getDatetimeEnd(): DateTime
    {
        return $this->datetimeEnd;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setDateMode(bool $dateMode): static
    {
        $this->dateMode = $dateMode;

        return $this;
    }

    public function getDateMode(): bool
    {
        return $this->dateMode;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setRecurrenceId(?int $recurrenceId): static
    {
        $this->recurrenceId = $recurrenceId;

        return $this;
    }

    public function getRecurrenceId(): ?int
    {
        return $this->recurrenceId;
    }

    public function setRecurrencePattern(?string $recurrencePattern): static
    {
        $this->recurrencePattern = $recurrencePattern;

        return $this;
    }

    public function getRecurrencePattern(): ?string
    {
        return $this->recurrencePattern;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(?User $modifier): static
    {
        $this->modifier = $modifier;

        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    public function setCalendarId(?int $calendarId): static
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    public function getCalendarId(): ?int
    {
        return $this->calendarId;
    }

    public function setExternal(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    public function getExternal(): bool
    {
        return $this->external;
    }

    public function setUid(?string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setWholeDay(bool $wholeDay): static
    {
        $this->wholeDay = $wholeDay;

        return $this;
    }

    public function getWholeDay(): bool
    {
        return $this->wholeDay;
    }
}
