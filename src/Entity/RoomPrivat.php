<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RoomPrivat
 *
 * @ORM\Table(name="room_privat", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="lastlogin", columns={"lastlogin"})})
 * @ORM\Entity(repositoryClass="App\Repository\RoomPrivateRepository")
 */
class RoomPrivat
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
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
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
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity", type="integer", nullable=false)
     */
    private $activity = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    private $type = 'privateroom';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open_for_guests", type="boolean", nullable=false)
     */
    private $isOpenForGuests = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="continuous", type="boolean", nullable=false)
     */
    private $continuous = '-1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="template", type="boolean", nullable=false)
     */
    private $template = '-1';

    /**
     * @var string
     *
     * @ORM\Column(name="contact_persons", type="string", length=255, nullable=true)
     */
    private $contactPersons;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     * @return RoomPrivat
     */
    public function setItemId(int $itemId): RoomPrivat
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
     * @return RoomPrivat
     */
    public function setContextId(int $contextId): RoomPrivat
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatorId(): int
    {
        return $this->creatorId;
    }

    /**
     * @param int $creatorId
     * @return RoomPrivat
     */
    public function setCreatorId(int $creatorId): RoomPrivat
    {
        $this->creatorId = $creatorId;
        return $this;
    }

    /**
     * @return int
     */
    public function getModifierId(): int
    {
        return $this->modifierId;
    }

    /**
     * @param int $modifierId
     * @return RoomPrivat
     */
    public function setModifierId(int $modifierId): RoomPrivat
    {
        $this->modifierId = $modifierId;
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
     * @return RoomPrivat
     */
    public function setDeleterId(int $deleterId): RoomPrivat
    {
        $this->deleterId = $deleterId;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return RoomPrivat
     */
    public function setCreationDate(\DateTime $creationDate): RoomPrivat
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModificationDate(): \DateTime
    {
        return $this->modificationDate;
    }

    /**
     * @param \DateTime $modificationDate
     * @return RoomPrivat
     */
    public function setModificationDate(\DateTime $modificationDate): RoomPrivat
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeletionDate(): \DateTime
    {
        return $this->deletionDate;
    }

    /**
     * @param \DateTime $deletionDate
     * @return RoomPrivat
     */
    public function setDeletionDate(\DateTime $deletionDate): RoomPrivat
    {
        $this->deletionDate = $deletionDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return RoomPrivat
     */
    public function setTitle(string $title): RoomPrivat
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtras(): string
    {
        return $this->extras;
    }

    /**
     * @param string $extras
     * @return RoomPrivat
     */
    public function setExtras(string $extras): RoomPrivat
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RoomPrivat
     */
    public function setStatus(string $status): RoomPrivat
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getActivity(): int
    {
        return $this->activity;
    }

    /**
     * @param int $activity
     * @return RoomPrivat
     */
    public function setActivity(int $activity): RoomPrivat
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RoomPrivat
     */
    public function setType(string $type): RoomPrivat
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @param bool $public
     * @return RoomPrivat
     */
    public function setPublic(bool $public): RoomPrivat
    {
        $this->public = $public;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpenForGuests(): bool
    {
        return $this->isOpenForGuests;
    }

    /**
     * @param bool $isOpenForGuests
     * @return RoomPrivat
     */
    public function setIsOpenForGuests(bool $isOpenForGuests): RoomPrivat
    {
        $this->isOpenForGuests = $isOpenForGuests;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContinuous(): bool
    {
        return $this->continuous;
    }

    /**
     * @param bool $continuous
     * @return RoomPrivat
     */
    public function setContinuous(bool $continuous): RoomPrivat
    {
        $this->continuous = $continuous;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->template;
    }

    /**
     * @param bool $template
     * @return RoomPrivat
     */
    public function setTemplate(bool $template): RoomPrivat
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactPersons(): string
    {
        return $this->contactPersons;
    }

    /**
     * @param string $contactPersons
     * @return RoomPrivat
     */
    public function setContactPersons(string $contactPersons): RoomPrivat
    {
        $this->contactPersons = $contactPersons;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return RoomPrivat
     */
    public function setDescription(string $description): RoomPrivat
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastlogin(): \DateTime
    {
        return $this->lastlogin;
    }

    /**
     * @param \DateTime $lastlogin
     * @return RoomPrivat
     */
    public function setLastlogin(\DateTime $lastlogin): RoomPrivat
    {
        $this->lastlogin = $lastlogin;
        return $this;
    }
}

