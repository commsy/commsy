<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommSy item class implementing Doctrine Class Table Inheritance
 *
 * @ORM\Entity
 * @ORM\Table(name="items", indexes={
 *     @ORM\Index(name="parent_idx", columns={"parent_id"}),
 *     @ORM\Index(name="discriminator_idx", columns={"discriminator"})
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *     "room"="CommSy\RoomBundle\Entity\Room",
 *     "project"="CommSy\RoomBundle\Entity\Room"
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class Item
{
    /**
     * The item id
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The parent id
     * @var int
     *
     * @ORM\Column(type="integer", name="parent_id", nullable=true)
     */
    private $parentId;

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
     * Returns the unique item id
     * 
     * @return int item id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Triggers on inital persist
     *
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
        $this->modifiedAt = new \DateTime();
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