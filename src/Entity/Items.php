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
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A row of the cross-rubric `items` index.
 *
 * This is deliberately NOT the root of a Doctrine inheritance hierarchy.
 * It used to carry an InheritanceType('JOINED') plus a DiscriminatorMap
 * listing the rubric entities, but none of them ever extended this class,
 * so Doctrine registered no subclasses and instead instantiated the mapped
 * class while filling only the `items` columns — leaving everything the
 * rubric table owns uninitialised. That is what made a typed property such
 * as Materials::$versionId unreadable and what forced version_id out of the
 * Materials identifier in the first place (see #5009).
 *
 * The table is an index over heterogeneous content, not a supertype: it
 * answers "which entries exist in this room, pinned/draft/deleted?" across
 * rubrics. To go from a row here to the rubric entity, ask
 * {@see \App\Item\TypedEntityResolver} — the mapping from `type` to entity
 * class is explicit there rather than implied by Doctrine.
 */
#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['type'], name: 'type')]
class Items
{
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $itemId = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    /**
     * The rubric this row belongs to, e.g. 'material' or 'announcement'.
     * Formerly Doctrine's discriminator column; now an ordinary field so the
     * value stays readable without implying an inheritance hierarchy.
     */
    #[ORM\Column(name: 'type', type: Types::STRING, length: 15)]
    private string $type = '';

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
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
