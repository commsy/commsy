<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Todos
 *
 * @ORM\Table(name="todos", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"})})
 * @ORM\Entity
 */
class Todos
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
     * @var DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="activation_date", type="datetime")
     */
    private ?DateTime $activationDate;

    /**
     * @var DateTime
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
     * @var DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status = '1';

    /**
     * @var float
     *
     * @ORM\Column(name="minutes", type="float", precision=10, scale=0, nullable=true)
     */
    private $minutes;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_type", type="smallint", nullable=false)
     */
    private $timeType = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var DateTime
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
     * @ORM\OneToMany(targetEntity="Step", mappedBy="todo")
     */
    private $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    /**
     * Add steps
     *
     * @param \App\Entity\Step $step
     *
     * @return Materials
     */
    public function addSteps(\App\Entity\Step $step)
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Remove steps
     *
     * @param \App\Entity\Step $step
     */
    public function removeSteps(\App\Entity\Step $step)
    {
        $this->stepss->removeElement($step);
    }

    /**
     * Get stepss
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSteps()
    {
        return $this->steps;
    }


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
     * @return Todos
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
     * @return Todos
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
     * @param DateTime $creationDate
     *
     * @return Todos
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param DateTime $modificationDate
     *
     * @return Todos
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set activationDate
     *
     * @param DateTime $activationDate
     *
     * @return Todos
     */
    public function setActivationDate(DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate
     *
     * @return DateTime|null
     */
    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * Set deletionDate
     *
     * @param DateTime $deletionDate
     *
     * @return Todos
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return DateTime
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
     * @return Todos
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
     * Set date
     *
     * @param DateTime $date
     *
     * @return Todos
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Todos
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set minutes
     *
     * @param float $minutes
     *
     * @return Todos
     */
    public function setMinutes($minutes)
    {
        $this->minutes = $minutes;

        return $this;
    }

    /**
     * Get minutes
     *
     * @return float
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    /**
     * Set timeType
     *
     * @param integer $timeType
     *
     * @return Todos
     */
    public function setTimeType($timeType)
    {
        $this->timeType = $timeType;

        return $this;
    }

    /**
     * Get timeType
     *
     * @return integer
     */
    public function getTimeType()
    {
        return $this->timeType;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Todos
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
     * Set public
     *
     * @param boolean $public
     *
     * @return Todos
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
     * Set extras
     *
     * @param string $extras
     *
     * @return Todos
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
     * Set lockingDate
     *
     * @param DateTime $lockingDate
     *
     * @return Todos
     */
    public function setLockingDate($lockingDate)
    {
        $this->lockingDate = $lockingDate;

        return $this;
    }

    /**
     * Get lockingDate
     *
     * @return DateTime
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
     * @return Todos
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
     * @return Todos
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
     * @return Todos
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
}
