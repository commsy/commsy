<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Room
 *
 * @ORM\Table(name="room", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="activity", columns={"activity"}), @ORM\Index(name="deleter_id", columns={"deleter_id"}), @ORM\Index(name="deletion_date", columns={"deletion_date"}), @ORM\Index(name="room_description", columns={"room_description"}), @ORM\Index(name="contact_persons", columns={"contact_persons"}), @ORM\Index(name="title", columns={"title"}), @ORM\Index(name="modifier_id", columns={"modifier_id"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="lastlogin", columns={"lastlogin"})})
 * @ORM\Entity(repositoryClass="CommsyBundle\Repository\RoomRepository")
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="item_id")
     */
    private $creator;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="item_id")
     */
    private $modifier;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="item_id", nullable=true)
     */
    private $deleter;

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
    private $openForGuests = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="continuous", type="boolean", nullable=false)
     */
    private $continuous = '-1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="template", type="integer", nullable=false)
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
     * @ORM\ManyToMany(targetEntity="Room")
     * @ORM\JoinTable(name="link_items",
     *     joinColumns={
     *         @ORM\JoinColumn(name="first_item_id", referencedColumnName="item_id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="second_item_id", referencedColumnName="item_id")
     *     }
     * )
     */
    private $communityRooms;

    public function __construct() {
        $this->communityRooms = new ArrayCollection();
    }

    public function isIndexable()
    {
        return ($this->deleter == null && $this->deletionDate == null);
    }

    public function getLanguage()
    {
        $extras = $this->getExtras();

        if (isset($extras['LANGUAGE'])) {
            return $extras['LANGUAGE'];
        }

        return 'user';
    }

    public function setLanguage($language)
    {
        $extras = $this->getExtras();
        $extras['LANGUAGE'] = $language;
        $this->setExtras($extras);

        return $this;
    }

    public function getLogo()
    {
        return '';
    }

    public function getAccessCheck()
    {
        $extras = $this->getExtras();

        if (isset($extras['CHECKNEWMEMBERS'])) {
            $checkNewMembers = $extras['CHECKNEWMEMBERS'];

            $mapping = array(
                -1 => 'never',
                2 => 'sometimes',
                3 => 'code',
            );

            if (isset($mapping[$checkNewMembers])) {
                return $mapping[$checkNewMembers];
            }
        }

        return 'always';
    }

    public function setAccessCheck($access)
    {
        $mapping = array(
            'never' => -1,
            'sometimes' => 2,
            'code' => 3,
        );

        $extras = $this->getExtras();
        $extras['CHECKNEWMEMBERS'] = $mapping[$access];
        $this->setExtras($extras);

        return $this;
    }

    public function isProjectRoom()
    {
        return $this->type === 'project';
    }

    public function isCommunityRoom()
    {
        return $this->type === 'community';
    }

    public function isMaterialOpenForGuests()
    {
        $extras = $this->getExtras();
        if (isset($extras['MATERIAL_GUESTS'])) {
            $materialOpenForGuests = $extras['MATERIAL_GUESTS'];

            return $materialOpenForGuests === 1;
        }

        return false;
    }

    public function setIsMaterialOpenForGuests($open)
    {
        $extras = $this->getExtras();
        $extras['MATERIAL_GUESTS'] = $open;
        $this->setExtras($extras);

        return $this;
    }

    public function getCommunityRooms()
    {
        return $this->communityRooms;
    }

    public function isAssignmentRestricted()
    {
        $extras = $this->getExtras();
        if (isset($extras['ROOMASSOCIATION'])) {
            $roomAssociation = $extras['ROOMASSOCIATION'];

            return $roomAssociation === 'onlymembers';
        }

        return false;
    }

    public function setAssignmentRestricted($isRestricted)
    {
        $roomAssociation = 'forall';

        if ($isRestricted) {
            $roomAssociation = 'onlymembers';
        }

        $extras = $this->getExtras();
        $extras['ROOMASSOCIATION'] = $roomAssociation;
        $this->setExtras($extras);
    }

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
     * @param mbarray $extras
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
     * @return mbarray
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
     * Set openForGuests
     *
     * @param boolean $openForGuests
     *
     * @return Room
     */
    public function setOpenForGuests($openForGuests)
    {
        $this->openForGuests = $openForGuests;

        return $this;
    }

    /**
     * Get openForGuests
     *
     * @return boolean
     */
    public function getOpenForGuests()
    {
        return $this->openForGuests;
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

    /**
     * Set creator
     *
     * @param \App\Entity\User $creator
     *
     * @return Room
     */
    public function setCreator(\App\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \App\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param \App\Entity\User $modifier
     *
     * @return Room
     */
    public function setModifier(\App\Entity\User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return \App\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set deleter
     *
     * @param \App\Entity\User $deleter
     *
     * @return Room
     */
    public function setDeleter(\App\Entity\User $deleter = null)
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return \App\Entity\User
     */
    public function getDeleter()
    {
        return $this->deleter;
    }

    /**
     * Add communityRoom
     *
     * @param \App\Entity\Room $communityRoom
     *
     * @return Room
     */
    public function addCommunityRoom(\App\Entity\Room $communityRoom)
    {
        $this->communityRooms[] = $communityRoom;

        return $this;
    }

    /**
     * Remove communityRoom
     *
     * @param \App\Entity\Room $communityRoom
     */
    public function removeCommunityRoom(\App\Entity\Room $communityRoom)
    {
        $this->communityRooms->removeElement($communityRoom);
    }
}
