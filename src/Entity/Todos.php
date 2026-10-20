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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use DateTime;
use App\Doctrine\Type\PhpArrayType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\State\PageSize;
use App\State\RubricEntryProvider;
use App\Repository\TodosRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Todos.
 */
#[ORM\Entity(repositoryClass: TodosRepository::class)]
#[ORM\Table(name: 'todos')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ApiResource(
    shortName: 'Todo',
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/todos',
            uriVariables: ['roomId' => new Link(fromClass: Room::class, identifiers: ['itemId'])],
        ),
        new Get(uriTemplate: '/todos/{itemId}'),
    ],
    // The export's field list and shape: the interface hands out what the export
    // carries, a null included.
    normalizationContext: ['groups' => ['export'], 'skip_null_values' => false],
    security: "is_granted('ROLE_API_READ')",
    provider: RubricEntryProvider::class,
    paginationItemsPerPage: PageSize::DEFAULT,
    paginationMaximumItemsPerPage: PageSize::MAXIMUM,
    paginationClientItemsPerPage: true,
)]
class Todos implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    // items.item_id is the id source; the todos row reuses it
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'When the entry becomes visible in the room; null if it is visible right away. '
            .'Entries not visible yet are part of the export.',
    )]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    private string $title;

    #[ORM\Column(name: 'date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'status', type: Types::INTEGER, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Its status, as an id into the room\'s task statuses.')]
    private int $status = 1;

    #[ORM\Column(name: 'minutes', type: Types::FLOAT, precision: 10, scale: 0, nullable: true)]
    private ?float $minutes = null;

    #[ORM\Column(name: 'time_type', type: Types::SMALLINT, nullable: false)]
    private int $timeType = 1;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'As HTML, the way CommSy stores it.')]
    private ?string $description = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'extras', type: PhpArrayType::NAME, nullable: true)]
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
     * A task lives in a room; its context is never anything else.
     */
    public function getRoomId(): int
    {
        return (int) $this->contextId;
    }

    /**
     * Only material and section keep versions.
     */
    public function getVersionId(): int
    {
        return self::UNVERSIONED;
    }

    /**
     * Time booked on it, if any.
     *
     * The column is called `minutes` but holds a number in whatever unit
     * `time_type` names, so a bare `minutes: 3` can mean three days. The two are
     * handed on together and named.
     *
     * @return array{amount: float, unit: string}|null
     */
    #[Groups(['export'])]
    #[ApiProperty(schema: [
        'type' => ['object', 'null'],
        'description' => 'Time booked on it, in the unit named; null if none is booked.',
        'additionalProperties' => false,
        'required' => ['amount', 'unit'],
        'properties' => [
            'amount' => ['type' => 'number'],
            'unit' => ['type' => 'string', 'enum' => ['minutes', 'hours', 'days']],
        ],
    ])]
    public function getTimeSpent(): ?array
    {
        if (empty($this->minutes)) {
            return null;
        }

        $unit = match ($this->timeType) {
            2 => 'hours',
            3 => 'days',
            default => 'minutes',
        };

        return ['amount' => (float) $this->minutes, 'unit' => $unit];
    }

    /**
     * When the task is due. The column says `date`, the form says `due date` and
     * legacy says `end_date`; all three are this one value.
     */
    #[Groups(['export'])]
    #[ApiProperty(description: 'When it is due.')]
    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->date;
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
