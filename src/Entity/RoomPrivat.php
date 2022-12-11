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

use Doctrine\ORM\Mapping as ORM;

/**
 * RoomPrivat.
 */
#[ORM\Entity(repositoryClass: \App\Repository\RoomPrivateRepository::class)]
#[ORM\Table(name: 'room_privat')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'status', columns: ['status'])]
#[ORM\Index(name: 'lastlogin', columns: ['lastlogin'])]
class RoomPrivat
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId = '0';
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private ?int $contextId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private $creatorId = '0';
    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: true)]
    private ?int $modifierId = null;
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private ?int $deleterId = null;
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private \DateTime $creationDate;
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    private \DateTime $modificationDate;
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private ?\DateTime $deletionDate = null;
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private ?string $title = null;
    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private ?string $extras = null;
    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    private ?string $status = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'activity', type: 'integer', nullable: false)]
    private $activity = '0';
    #[ORM\Column(name: 'type', type: 'string', length: 20, nullable: false)]
    private string $type = 'privateroom';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private $public = '0';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_open_for_guests', type: 'boolean', nullable: false)]
    private $isOpenForGuests = '0';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'continuous', type: 'boolean', nullable: false)]
    private $continuous = '-1';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'template', type: 'boolean', nullable: false)]
    private $template = '-1';
    #[ORM\Column(name: 'contact_persons', type: 'string', length: 255, nullable: true)]
    private ?string $contactPersons = null;
    #[ORM\Column(name: 'description', type: 'text', length: 65535, nullable: true)]
    private ?string $description = null;
    #[ORM\Column(name: 'lastlogin', type: 'datetime', nullable: true)]
    private ?\DateTime $lastlogin = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->creationDate = new \DateTime('0000-00-00 00:00:00');
        $this->modificationDate = new \DateTime('0000-00-00 00:00:00');
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): RoomPrivat
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): RoomPrivat
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getCreatorId(): int
    {
        return $this->creatorId;
    }

    public function setCreatorId(int $creatorId): RoomPrivat
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    public function getModifierId(): int
    {
        return $this->modifierId;
    }

    public function setModifierId(int $modifierId): RoomPrivat
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    public function getDeleterId(): int
    {
        return $this->deleterId;
    }

    public function setDeleterId(int $deleterId): RoomPrivat
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): RoomPrivat
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getModificationDate(): \DateTime
    {
        return $this->modificationDate;
    }

    public function setModificationDate(\DateTime $modificationDate): RoomPrivat
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getDeletionDate(): \DateTime
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(\DateTime $deletionDate): RoomPrivat
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): RoomPrivat
    {
        $this->title = $title;

        return $this;
    }

    public function getExtras(): string
    {
        return $this->extras;
    }

    public function setExtras(string $extras): RoomPrivat
    {
        $this->extras = $extras;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): RoomPrivat
    {
        $this->status = $status;

        return $this;
    }

    public function getActivity(): int
    {
        return $this->activity;
    }

    public function setActivity(int $activity): RoomPrivat
    {
        $this->activity = $activity;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): RoomPrivat
    {
        $this->type = $type;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): RoomPrivat
    {
        $this->public = $public;

        return $this;
    }

    public function isOpenForGuests(): bool
    {
        return $this->isOpenForGuests;
    }

    public function setIsOpenForGuests(bool $isOpenForGuests): RoomPrivat
    {
        $this->isOpenForGuests = $isOpenForGuests;

        return $this;
    }

    public function isContinuous(): bool
    {
        return $this->continuous;
    }

    public function setContinuous(bool $continuous): RoomPrivat
    {
        $this->continuous = $continuous;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): RoomPrivat
    {
        $this->template = $template;

        return $this;
    }

    public function getContactPersons(): string
    {
        return $this->contactPersons;
    }

    public function setContactPersons(string $contactPersons): RoomPrivat
    {
        $this->contactPersons = $contactPersons;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): RoomPrivat
    {
        $this->description = $description;

        return $this;
    }

    public function getLastlogin(): \DateTime
    {
        return $this->lastlogin;
    }

    public function setLastlogin(\DateTime $lastlogin): RoomPrivat
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Set the room's slug (a unique textual identifier for this room).
     */
    public function setSlug(?string $slug): void
    {
        $slug = !empty($slug) ? strtolower($slug) : null;

        $this->slug = $slug;
    }

    /**
     * Get the room's slug (a unique textual identifier for this room).
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
