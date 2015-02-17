<?php
namespace CommSy\RoomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use AppBundle\Entity\Item;

/**
 * Class Room.php
 *
 * CommSy room class
 *
 * @ORM\Entity
 * @ORM\Table(name="room")
 */
class Room extends Item
{
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
    private $contactPersons;

    /**
     * The room description
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * Date of last user entering
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="last_login", nullable=true)
     */
    private $lastLogin;

    /**
     * Room constructor
     */
    public function __construct()
    {
        $this->status = 1;
        $this->activity = 0;
        $this->type = 'project';
        $this->public = 0;
        $this->openForGuests = 0;
        $this->continuous = 0;
        $this->template = 0;
        $this->contactPersons = '';
    }

    /**
     * Return the room title
     * 
     * @return string roomt title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the room title
     * @param string $title room title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the room status
     * @param int $status room status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the room status
     *
     * @return int status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the room description
     *
     * @return string description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the room description
     * @param string $description room description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}