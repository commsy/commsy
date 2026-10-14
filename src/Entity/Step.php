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
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\Repository\StepRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Step.
 */
#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\Table(name: 'step')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['todo_item_id'], name: 'todo_item_id')]
class Step implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'As HTML, the way CommSy stores it.')]
    private ?string $description = null;

    #[ORM\Column(name: 'minutes', type: Types::FLOAT, precision: 10, scale: 0, nullable: false)]
    private float $minutes = 0.0;

    #[ORM\Column(name: 'time_type', type: Types::SMALLINT, nullable: false)]
    private ?int $timeType = 1;

    #[ORM\Column(name: 'todo_item_id', type: Types::INTEGER, nullable: false)]
    private int $todoItemId;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, length: 65535, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\ManyToOne(targetEntity: 'Todos', inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'todo_item_id', referencedColumnName: 'item_id')]
    private ?Todos $todo = null;

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

    /**
     * A step lives in the room its task lives in.
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

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
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

    public function setMinutes(float $minutes): static
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getMinutes(): float
    {
        return $this->minutes;
    }

    public function setTimeType(?int $timeType): static
    {
        $this->timeType = $timeType;

        return $this;
    }

    public function getTimeType(): ?int
    {
        return $this->timeType;
    }

    public function setTodoItemId(int $todoItemId): static
    {
        $this->todoItemId = $todoItemId;

        return $this;
    }

    public function getTodoItemId(): int
    {
        return $this->todoItemId;
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

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }
    public function setTodo(?Todos $todo): static
    {
        $this->todo = $todo;

        return $this;
    }

    public function getTodo(): ?Todos
    {
        return $this->todo;
    }
}
