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

use App\Repository\ItemRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'annotation' => 'Annotations',
    'announcement' => 'Announcement',
    'assessments' => 'Assessments',
    'community' => 'Room',
    'date' => 'Dates',
    'discarticle' => 'Discussionarticles',
    'discussion' => 'Discussions',
    'grouproom' => 'Room',
    'label' => 'Labels',
    'link_item' => 'LinkItems',
    'material' => 'Materials',
    'portfolio' => 'Portfolio',
    'privateroom' => 'RoomPrivat',
    'project' => 'Room',
    'section' => 'Section',
    'server' => 'Server',
    'step' => 'Step',
    'tag' => 'Tag',
    'task' => 'Tasks',
    'todo' => 'Todos',
    'user' => 'User'
])]
#[ORM\Table(name: 'items')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['type'], name: 'type')]
class Items
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    private $modificationDate;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'draft', type: Types::BOOLEAN, options: ['default' => 0])]
    private bool $draft = false;

    #[ORM\Column(name: 'pinned', type: Types::BOOLEAN, options: ['default' => 0])]
    private bool $pinned = false;

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     * @return Items
     */
    public function setItemId(int $itemId): Items
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     * @return Items
     */
    public function setContextId(int $contextId): Items
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return int
     */
    public function getDeleterId(): int
    {
        return $this->deleterId;
    }

    /**
     * @param int $deleterId
     * @return Items
     */
    public function setDeleterId(int $deleterId): Items
    {
        $this->deleterId = $deleterId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDeletionDate(): DateTime
    {
        return $this->deletionDate;
    }

    /**
     * @param DateTime $deletionDate
     * @return Items
     */
    public function setDeletionDate(DateTime $deletionDate): Items
    {
        $this->deletionDate = $deletionDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModificationDate(): DateTime
    {
        return $this->modificationDate;
    }

    /**
     * @param DateTime $modificationDate
     * @return Items
     */
    public function setModificationDate(DateTime $modificationDate): Items
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * @param DateTime|null $activationDate
     * @return Items
     */
    public function setActivationDate(?DateTime $activationDate): Items
    {
        $this->activationDate = $activationDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->draft;
    }

    /**
     * @param bool $draft
     * @return Items
     */
    public function setDraft(bool $draft): Items
    {
        $this->draft = $draft;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }
}
