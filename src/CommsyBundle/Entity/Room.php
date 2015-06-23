<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Room
 *
 * @ORM\Table(name="room", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="activity", columns={"activity"}), @ORM\Index(name="deleter_id", columns={"deleter_id"}), @ORM\Index(name="deletion_date", columns={"deletion_date"}), @ORM\Index(name="room_description", columns={"room_description"}), @ORM\Index(name="contact_persons", columns={"contact_persons"}), @ORM\Index(name="title", columns={"title"}), @ORM\Index(name="modifier_id", columns={"modifier_id"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="lastlogin", columns={"lastlogin"})})
 * @ORM\Entity
 */
class Room
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
     * @ORM\Column(name="extras", type="mbarray", nullable=true)
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
    private $type = 'project';

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
     * @ORM\Column(name="room_description", type="string", length=10000, nullable=true)
     */
    private $roomDescription;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;



    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return Room
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creatorId
     *
     * @param integer $creatorId
     *
     * @return Room
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set modifierId
     *
     * @param integer $modifierId
     *
     * @return Room
     */
    public function setModifierId($modifierId)
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    /**
     * Get modifierId
     *
     * @return integer
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return Room
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId
     *
     * @return integer
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Room
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return Room
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate
     *
     * @param \DateTime $deletionDate
     *
     * @return Room
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Room
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set extras
     *
     * @param string $extras
     *
     * @return Room
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Room
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set activity
     *
     * @param integer $activity
     *
     * @return Room
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return integer
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Room
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Room
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set isOpenForGuests
     *
     * @param boolean $isOpenForGuests
     *
     * @return Room
     */
    public function setIsOpenForGuests($isOpenForGuests)
    {
        $this->isOpenForGuests = $isOpenForGuests;

        return $this;
    }

    /**
     * Get isOpenForGuests
     *
     * @return boolean
     */
    public function getIsOpenForGuests()
    {
        return $this->isOpenForGuests;
    }

    /**
     * Set continuous
     *
     * @param boolean $continuous
     *
     * @return Room
     */
    public function setContinuous($continuous)
    {
        $this->continuous = $continuous;

        return $this;
    }

    /**
     * Get continuous
     *
     * @return boolean
     */
    public function getContinuous()
    {
        return $this->continuous;
    }

    /**
     * Set template
     *
     * @param boolean $template
     *
     * @return Room
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return boolean
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set contactPersons
     *
     * @param string $contactPersons
     *
     * @return Room
     */
    public function setContactPersons($contactPersons)
    {
        $this->contactPersons = $contactPersons;

        return $this;
    }

    /**
     * Get contactPersons
     *
     * @return string
     */
    public function getContactPersons()
    {
        return $this->contactPersons;
    }

    /**
     * Set roomDescription
     *
     * @param string $roomDescription
     *
     * @return Room
     */
    public function setRoomDescription($roomDescription)
    {
        $this->roomDescription = $roomDescription;

        return $this;
    }

    /**
     * Get roomDescription
     *
     * @return string
     */
    public function getRoomDescription()
    {
        return $this->roomDescription;
    }

    /**
     * Set lastlogin
     *
     * @param \DateTime $lastlogin
     *
     * @return Room
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }
}
