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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Todos.
 */
#[ORM\Entity]
#[ORM\Table(name: 'todos')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Todos
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId = '0';

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

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    private $modificationDate;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?DateTime $activationDate = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'date', type: 'datetime', nullable: true)]
    private $date;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status', type: 'integer', nullable: false)]
    private $status = '1';

    /**
     * @var float
     */
    #[ORM\Column(name: 'minutes', type: 'float', precision: 10, scale: 0, nullable: true)]
    private $minutes;

    /**
     * @var int
     */
    #[ORM\Column(name: 'time_type', type: 'smallint', nullable: false)]
    private $timeType = '1';

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 16777215, nullable: true)]
    private $description;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private $public = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private $extras;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'locking_date', type: 'datetime', nullable: true)]
    private $lockingDate;

    /**
     * @var int
     */
    #[ORM\Column(name: 'locking_user_id', type: 'integer', nullable: true)]
    private $lockingUserId;

    #[ORM\OneToMany(targetEntity: 'Step', mappedBy: 'todo')]
    private Collection $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    /**
     * Add steps.
     *
     * @return Materials
     */
    public function addSteps(Step $step)
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Remove steps.
     */
    public function removeSteps(Step $step)
    {
        $this->stepss->removeElement($step);
    }

    /**
     * Get stepss.
     *
     * @return Collection
     */
    public function getSteps()
    {
        return $this->steps;
    }

    public function isIndexable()
    {
        return null == $this->deleterId && null == $this->deletionDate;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Todos
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
     * @return Todos
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
     * Set creationDate.
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
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate.
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
     * Get modificationDate.
     *
     * @return DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
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
     * Set deletionDate.
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
     * Get deletionDate.
     *
     * @return DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set title.
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
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set date.
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
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set minutes.
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
     * Get minutes.
     *
     * @return float
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    /**
     * Set timeType.
     *
     * @param int $timeType
     *
     * @return Todos
     */
    public function setTimeType($timeType)
    {
        $this->timeType = $timeType;

        return $this;
    }

    /**
     * Get timeType.
     *
     * @return int
     */
    public function getTimeType()
    {
        return $this->timeType;
    }

    /**
     * Set description.
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
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Todos
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
     * Set extras.
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
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set lockingDate.
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
     * Get lockingDate.
     *
     * @return DateTime
     */
    public function getLockingDate()
    {
        return $this->lockingDate;
    }

    /**
     * Set lockingUserId.
     *
     * @param int $lockingUserId
     *
     * @return Todos
     */
    public function setLockingUserId($lockingUserId)
    {
        $this->lockingUserId = $lockingUserId;

        return $this;
    }

    /**
     * Get lockingUserId.
     *
     * @return int
     */
    public function getLockingUserId()
    {
        return $this->lockingUserId;
    }

    /**
     * Set creator.
     *
     * @return Todos
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
     * @return Todos
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
}
