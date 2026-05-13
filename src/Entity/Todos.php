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
use App\Utils\EntityUsersTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Todos.
 */
#[ORM\Entity]
#[ORM\Table(name: 'todos')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Todos
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'status', type: Types::INTEGER, nullable: false)]
    private int $status = 1;

    #[ORM\Column(name: 'minutes', type: Types::FLOAT, precision: 10, scale: 0, nullable: true)]
    private ?float $minutes = null;

    #[ORM\Column(name: 'time_type', type: Types::SMALLINT, nullable: false)]
    private int $timeType = 1;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\OneToMany(targetEntity: 'Step', mappedBy: 'todo')]
    private Collection $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    /**
     * Add steps.
     */
    public function addSteps(Step $step): static
    {
        $this->steps[] = $step;

        return $this;
    }

    public function removeSteps(Step $step): void
    {
        $this->steps->removeElement($step);
    }

    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function isIndexable()
    {
        return null === $this->deleter && null === $this->deletionDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     */
    public function setContextId($contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * Set activationDate.
     */
    public function setActivationDate(DateTime $activationDate): static
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set date.
     *
     * @param DateTime $date
     */
    public function setDate($date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * Set status.
     */
    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set minutes.
     *
     * @param float $minutes
     */
    public function setMinutes($minutes): static
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getMinutes(): ?float
    {
        return $this->minutes;
    }

    /**
     * Set timeType.
     *
     * @param int $timeType
     */
    public function setTimeType($timeType): static
    {
        $this->timeType = $timeType;

        return $this;
    }

    public function getTimeType(): int
    {
        return $this->timeType;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function setExtras(array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

}
