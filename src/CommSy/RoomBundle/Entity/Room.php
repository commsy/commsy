<?php
namespace CommSy\RoomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Room.php
 *
 * CommSy room class
 *
 * @ORM\Entity
 * @ORM\Table(name="room")
 * @ORM\HasLifecycleCallbacks()
 */
class Room
{
    /**
     * The room id
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * The parent room, if it does exist
     * @var int
     *
     * @ORM\OneToOne(targetEntity="CommSy\RoomBundle\Entity\Room")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parentRoom;

    /**
     * The room creator
     * @var CommSy\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="CommSy\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     */
    private $creator;

    /**
     * Last user who modified the room
     * @var CommSy\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="CommSy\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="id", nullable=true)
     */
    private $modifier;

    /**
     * User who deleted the room
     * @var CommSy\UserBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="CommSy\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="id", nullable=true)
     */
    private $deleter;

    /**
     * Date the rooms has been created
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * Date the room has been last modified
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="modified_at")
     */
    private $modifiedAt;

    /**
     * Date the room has been deleted
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    private $deletedAt;

    /**
     * The room title
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * Serialized extras
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $extras;

    /**
     * The room status
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * Room activity counter
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $activity;

    /**
     * Room type
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * Public flag
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $public;

    /**
     * OpenForGuests flag
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="open_for_guests")
     */
    private $openForGuests;

    /**
     * Continues flag
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $continuous;

    /**
     * Template flag
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $template;

    /**
     * Contact Persons
     * @var string
     *
     * @ORM\Column(type="string", name="contact_persons", length=255)
     */
    private $contactpersons;

    /**
     * The room description
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * Date of last user entering
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="last_login")
     */
    private $lastLogin;

    /**
     * Returns the room unique id
     * 
     * @return int room id
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Triggers on inital persist
     *
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Triggers before the database update operation, but is
     * not called for DQL UPDATE statements
     *
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->modifiedAt = new \DateTime();
    }

    /**
     * Triggers before the database remove operation, but is
     * not called FOR DQL DELETE statements
     *
     * @ORM\PreRemove
     */
    public function onPreRemove()
    {
        $this->deletedAt = new \DateTime();
    }
}