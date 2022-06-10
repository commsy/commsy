<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dates
 *
 * @ORM\Table(name="dates", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"})})
 * @ORM\Entity
 */
class Dates
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="item_id")
     */
    private $creator;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="item_id")
     */
    private $modifier;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="link_modifier_item_date", type="datetime", nullable=false)
     */
    private $linkModifierItemDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="string", length=100, nullable=true)
     */
    private $startTime;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="string", length=100, nullable=true)
     */
    private $endTime;

    /**
     * @var string
     *
     * @ORM\Column(name="start_day", type="string", length=100, nullable=false)
     */
    private $startDay;

    /**
     * @var string
     *
     * @ORM\Column(name="end_day", type="string", length=100, nullable=true)
     */
    private $endDay;

    /**
     * @var string
     *
     * @ORM\Column(name="place", type="string", length=100, nullable=true)
     */
    private $place;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime_start", type="datetime", nullable=false)
     */
    private $datetimeStart = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime_end", type="datetime", nullable=false)
     */
    private $datetimeEnd = '0000-00-00 00:00:00';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="date_mode", type="boolean", nullable=false)
     */
    private $dateMode = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @var integer
     *
     * @ORM\Column(name="recurrence_id", type="integer", nullable=true)
     */
    private $recurrenceId;

    /**
     * @var string
     *
     * @ORM\Column(name="recurrence_pattern", type="text", length=65535, nullable=true)
     */
    private $recurrencePattern;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="locking_date", type="datetime", nullable=true)
     */
    private $lockingDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="locking_user_id", type="integer", nullable=true)
     */
    private $lockingUserId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="external", type="boolean", nullable=false)
     */
    private $external = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="whole_day", type="boolean", nullable=false)
     */
    private $wholeDay = '0';

    /**
     * @var uid
     *
     * @ORM\Column(name="uid", type="string", length=255, nullable=true)
     */
    private $uid;

    /**
     * @var integer
     *
     * @ORM\Column(name="calendar_id", type="integer", nullable=true)
     */
    private $calendarId;
    
    public function isIndexable()
    {
        return ($this->deleterId == null && $this->deletionDate == null);
    }


    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return Dates
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return Dates
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId
     *
     * @return integer
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Dates
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return Dates
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }


    /**
     * Set linkModifierItemDate
     *
     * @param \DateTime $linkModifierItemDate
     *
     * @return Dates
     */
    public function setLinkModifierItemDate($linkModifierItemDate)
    {
        $this->linkModifierItemDate = $linkModifierItemDate;

        return $this;
    }

    /**
     * Get linkModifierItemDate
     *
     * @return \DateTime
     */
    public function getLinkModifierItemDate()
    {
        return $this->linkModifierItemDate;
    }

    /**
     * Set deletionDate
     *
     * @param \DateTime $deletionDate
     *
     * @return Dates
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set title
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
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
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
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set startTime
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
     * Get startTime
     *
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
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
     * Get endTime
     *
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set startDay
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
     * Get startDay
     *
     * @return string
     */
    public function getStartDay()
    {
        return $this->startDay;
    }

    /**
     * Set endDay
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
     * Get endDay
     *
     * @return string
     */
    public function getEndDay()
    {
        return $this->endDay;
    }

    /**
     * Set place
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
     * Get place
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set datetimeStart
     *
     * @param \DateTime $datetimeStart
     *
     * @return Dates
     */
    public function setDatetimeStart($datetimeStart)
    {
        $this->datetimeStart = $datetimeStart;

        return $this;
    }

    /**
     * Get datetimeStart
     *
     * @return \DateTime
     */
    public function getDatetimeStart()
    {
        return $this->datetimeStart;
    }

    /**
     * Set datetimeEnd
     *
     * @param \DateTime $datetimeEnd
     *
     * @return Dates
     */
    public function setDatetimeEnd($datetimeEnd)
    {
        $this->datetimeEnd = $datetimeEnd;

        return $this;
    }

    /**
     * Get datetimeEnd
     *
     * @return \DateTime
     */
    public function getDatetimeEnd()
    {
        return $this->datetimeEnd;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Dates
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set dateMode
     *
     * @param boolean $dateMode
     *
     * @return Dates
     */
    public function setDateMode($dateMode)
    {
        $this->dateMode = $dateMode;

        return $this;
    }

    /**
     * Get dateMode
     *
     * @return boolean
     */
    public function getDateMode()
    {
        return $this->dateMode;
    }

    /**
     * Set extras
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
     * Get extras
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set color
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
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set recurrenceId
     *
     * @param integer $recurrenceId
     *
     * @return Dates
     */
    public function setRecurrenceId($recurrenceId)
    {
        $this->recurrenceId = $recurrenceId;

        return $this;
    }

    /**
     * Get recurrenceId
     *
     * @return integer
     */
    public function getRecurrenceId()
    {
        return $this->recurrenceId;
    }

    /**
     * Set recurrencePattern
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
     * Get recurrencePattern
     *
     * @return string
     */
    public function getRecurrencePattern()
    {
        return $this->recurrencePattern;
    }

    /**
     * Set lockingDate
     *
     * @param \DateTime $lockingDate
     *
     * @return Dates
     */
    public function setLockingDate($lockingDate)
    {
        $this->lockingDate = $lockingDate;

        return $this;
    }

    /**
     * Get lockingDate
     *
     * @return \DateTime
     */
    public function getLockingDate()
    {
        return $this->lockingDate;
    }

    /**
     * Set lockingUserId
     *
     * @param integer $lockingUserId
     *
     * @return Dates
     */
    public function setLockingUserId($lockingUserId)
    {
        $this->lockingUserId = $lockingUserId;

        return $this;
    }

    /**
     * Get lockingUserId
     *
     * @return integer
     */
    public function getLockingUserId()
    {
        return $this->lockingUserId;
    }

    /**
     * Set creator
     *
     * @param \App\Entity\User $creator
     *
     * @return Dates
     */
    public function setCreator(\App\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \App\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param \App\Entity\User $modifier
     *
     * @return Dates
     */
    public function setModifier(\App\Entity\User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return \App\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set calendarId
     *
     * @param integer $calendarId
     *
     * @return Dates
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    /**
     * Get calendarId
     *
     * @return integer
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Set external
     *
     * @param boolean $external
     *
     * @return Dates
     */
    public function setExternal($external)
    {
        $this->external = $external;

        return $this;
    }

    /**
     * Get external
     *
     * @return boolean
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * Set uid
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
     * Get uid
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set whole day
     *
     * @param boolean $wholeDay
     *
     * @return Dates
     */
    public function setWholeDay($wholeDay)
    {
        $this->wholeDay = $wholeDay;

        return $this;
    }

    /**
     * Get whole day
     *
     * @return boolean
     */
    public function getWholeDay()
    {
        return $this->wholeDay;
    }
}
