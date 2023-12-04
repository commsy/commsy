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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Step.
 */
#[ORM\Entity]
#[ORM\Table(name: 'step')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['todo_item_id'], name: 'todo_item_id')]
class Step
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: false)]
    private ?int $creatorId = null;

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: true)]
    private ?int $modifierId = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
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

    public function setCreatorId(?int $creatorId): static
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function setModifierId(?int $modifierId): static
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    public function getModifierId(): ?int
    {
        return $this->modifierId;
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
