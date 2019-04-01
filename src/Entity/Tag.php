<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tag
 *
 * @ORM\Table(name="tag", indexes={@ORM\Index(name="context_id", columns={"context_id"})})
 * @ORM\Entity
 */
class Tag
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="item_id")
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
     * @return Tag
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
     * @return Tag
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
     * @return Tag
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
     * Set deleter
     *
     * @param \App\Entity\User $deleter
     *
     * @return Labels
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
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Tag
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
     * @return Tag
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
     * @return Tag
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
     * @return Tag
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
}
