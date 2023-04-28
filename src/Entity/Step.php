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
use Doctrine\ORM\Mapping as ORM;

/**
 * Step.
 */
#[ORM\Entity]
#[ORM\Table(name: 'step')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'todo_item_id', columns: ['todo_item_id'])]
class Step
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private string $creatorId = '0';

    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: true)]
    private $modifierId;

    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

    #[ORM\Column(name: 'description', type: 'text', length: 16_777_215, nullable: true)]
    private $description;

    #[ORM\Column(name: 'minutes', type: 'float', precision: 10, scale: 0, nullable: false)]
    private string $minutes = '0';

    #[ORM\Column(name: 'time_type', type: 'smallint', nullable: false)]
    private string $timeType = '1';

    #[ORM\Column(name: 'todo_item_id', type: 'integer', nullable: false)]
    private $todoItemId;

    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private $extras;

    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private string $public = '0';

    #[ORM\ManyToOne(targetEntity: 'Todos', inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'todo_item_id', referencedColumnName: 'item_id')]
    private ?Todos $todo = null;

    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Step
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
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return Step
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set modifierId.
     *
     * @param int $modifierId
     *
     * @return Step
     */
    public function setModifierId($modifierId)
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    /**
     * Get modifierId.
     *
     * @return int
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Step
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
     * Set title.
     *
     * @param string $title
     *
     * @return Step
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
     * @return Step
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
     * Set minutes.
     *
     * @param float $minutes
     *
     * @return Step
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
     * @return Step
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
     * Set todoItemId.
     *
     * @param int $todoItemId
     *
     * @return Step
     */
    public function setTodoItemId($todoItemId)
    {
        $this->todoItemId = $todoItemId;

        return $this;
    }

    /**
     * Get todoItemId.
     *
     * @return int
     */
    public function getTodoItemId()
    {
        return $this->todoItemId;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Step
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
     * Set public.
     *
     * @param bool $public
     *
     * @return Step
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
     * Set todo.
     *
     * @return Section
     */
    public function setTodo(Todos $todo = null)
    {
        $this->todo = $todo;

        return $this;
    }

    /**
     * Get todo.
     *
     * @return Todos
     */
    public function getTodo()
    {
        return $this->todo;
    }
}
