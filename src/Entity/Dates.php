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
use Doctrine\ORM\Mapping as ORM;

/**
 * Dates.
 */
#[ORM\Entity]
#[ORM\Table(name: 'dates')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Dates
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 16_777_215, nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'start_time', type: 'string', length: 100, nullable: true)]
    private $startTime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'end_time', type: 'string', length: 100, nullable: true)]
    private $endTime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'start_day', type: 'string', length: 100, nullable: false)]
    private $startDay;

    /**
     * @var string
     */
    #[ORM\Column(name: 'end_day', type: 'string', length: 100, nullable: true)]
    private $endDay;

    /**
     * @var string
     */
    #[ORM\Column(name: 'place', type: 'string', length: 100, nullable: true)]
    private $place;

    #[ORM\Column(name: 'datetime_start', type: 'datetime', nullable: false)]
    private DateTime $datetimeStart;

    #[ORM\Column(name: 'datetime_end', type: 'datetime', nullable: false)]
    private DateTime $datetimeEnd;

    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private string $public = '0';

    #[ORM\Column(name: 'date_mode', type: 'boolean', nullable: false)]
    private string $dateMode = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private $extras;

    /**
     * @var string
     */
    #[ORM\Column(name: 'color', type: 'string', length: 255, nullable: true)]
    private $color;

    /**
     * @var int
     */
    #[ORM\Column(name: 'recurrence_id', type: 'integer', nullable: true)]
    private $recurrenceId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'recurrence_pattern', type: 'text', length: 65535, nullable: true)]
    private $recurrencePattern;

    #[ORM\Column(name: 'external', type: 'boolean', nullable: false)]
    private string $external = '0';

    #[ORM\Column(name: 'whole_day', type: 'boolean', nullable: false)]
    private string $wholeDay = '0';

    /**
     * @var uid
     */
    #[ORM\Column(name: 'uid', type: 'string', length: 255, nullable: true)]
    private $uid;

    /**
     * @var int
     */
    #[ORM\Column(name: 'calendar_id', type: 'integer', nullable: true)]
    private $calendarId;

    public function isIndexable()
    {
        return null == $this->deleterId && null == $this->deletionDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Dates
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Dates
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId.
     *
     * @return int
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set activationDate.
     */
    public function setActivationDate(DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate.
     */
    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Dates
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Dates
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set startTime.
     *
     * @param string $startTime
     *
     * @return Dates
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param string $endTime
     *
     * @return Dates
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set startDay.
     *
     * @param string $startDay
     *
     * @return Dates
     */
    public function setStartDay($startDay)
    {
        $this->startDay = $startDay;

        return $this;
    }

    /**
     * Get startDay.
     *
     * @return string
     */
    public function getStartDay()
    {
        return $this->startDay;
    }

    /**
     * Set endDay.
     *
     * @param string $endDay
     *
     * @return Dates
     */
    public function setEndDay($endDay)
    {
        $this->endDay = $endDay;

        return $this;
    }

    /**
     * Get endDay.
     *
     * @return string
     */
    public function getEndDay()
    {
        return $this->endDay;
    }

    /**
     * Set place.
     *
     * @param string $place
     *
     * @return Dates
     */
    public function setPlace($place)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place.
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set datetimeStart.
     *
     * @param DateTime $datetimeStart
     *
     * @return Dates
     */
    public function setDatetimeStart($datetimeStart)
    {
        $this->datetimeStart = $datetimeStart;

        return $this;
    }

    /**
     * Get datetimeStart.
     *
     * @return DateTime
     */
    public function getDatetimeStart()
    {
        return $this->datetimeStart;
    }

    /**
     * Set datetimeEnd.
     *
     * @param DateTime $datetimeEnd
     *
     * @return Dates
     */
    public function setDatetimeEnd($datetimeEnd)
    {
        $this->datetimeEnd = $datetimeEnd;

        return $this;
    }

    /**
     * Get datetimeEnd.
     *
     * @return DateTime
     */
    public function getDatetimeEnd()
    {
        return $this->datetimeEnd;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Dates
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set dateMode.
     *
     * @param bool $dateMode
     *
     * @return Dates
     */
    public function setDateMode($dateMode)
    {
        $this->dateMode = $dateMode;

        return $this;
    }

    /**
     * Get dateMode.
     *
     * @return bool
     */
    public function getDateMode()
    {
        return $this->dateMode;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Dates
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set color.
     *
     * @param string $color
     *
     * @return Dates
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set recurrenceId.
     *
     * @param int $recurrenceId
     *
     * @return Dates
     */
    public function setRecurrenceId($recurrenceId)
    {
        $this->recurrenceId = $recurrenceId;

        return $this;
    }

    /**
     * Get recurrenceId.
     *
     * @return int
     */
    public function getRecurrenceId()
    {
        return $this->recurrenceId;
    }

    /**
     * Set recurrencePattern.
     *
     * @param string $recurrencePattern
     *
     * @return Dates
     */
    public function setRecurrencePattern($recurrencePattern)
    {
        $this->recurrencePattern = $recurrencePattern;

        return $this;
    }

    /**
     * Get recurrencePattern.
     *
     * @return string
     */
    public function getRecurrencePattern()
    {
        return $this->recurrencePattern;
    }

    /**
     * Set creator.
     *
     * @return Dates
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier.
     *
     * @return Dates
     */
    public function setModifier(User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier.
     *
     * @return User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set calendarId.
     *
     * @param int $calendarId
     *
     * @return Dates
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    /**
     * Get calendarId.
     *
     * @return int
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Set external.
     *
     * @param bool $external
     *
     * @return Dates
     */
    public function setExternal($external)
    {
        $this->external = $external;

        return $this;
    }

    /**
     * Get external.
     *
     * @return bool
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * Set uid.
     *
     * @param string $uid
     *
     * @return Dates
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set whole day.
     *
     * @param bool $wholeDay
     *
     * @return Dates
     */
    public function setWholeDay($wholeDay)
    {
        $this->wholeDay = $wholeDay;

        return $this;
    }

    /**
     * Get whole day.
     *
     * @return bool
     */
    public function getWholeDay()
    {
        return $this->wholeDay;
    }
}
