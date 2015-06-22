<?php
namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="materials")
 */
class Material
{
    /**
     * @ORM\Column(type="integer", name="item_id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="version_id")
     * @ORM\Id
     */
    private $versionId;

    /**
     * @ORM\Column(type="integer", name="context_id")
     */
    private $contextId;

    /**
     * @ORM\Column(type="integer", name="creator_id")
     */
    private $creatorId;

    /**
     * @ORM\Column(type="integer", name="deleter_id", nullable=true)
     */
    private $deleterId;

    /**
     * @ORM\Column(type="datetime", name="creation_date")
     */
    private $creationDate;

    /**
     * @ORM\Column(type="integer", name="modifier_id", nullable=true)
     */
    private $modifierId;

    /**
     * @ORM\Column(type="datetime", name="modification_date", nullable=true)
     */
    private $modificationDate;

    /**
     * @ORM\Column(type="datetime", name="deletion_date", nullable=true)
     */
    private $deletionDate;

    /**
     * @ORM\Column(type="string", name="title", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", name="description", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", name="author", length=200, nullable=true)
     */
    private $author;

    /**
     * @ORM\Column(type="string", name="publishing_date", length=20, nullable=true)
     */
    private $publishingDate;

    /**
     * @ORM\Column(type="boolean", name="public")
     */
    private $public;

    /**
     * @ORM\Column(type="smallint", name="world_public")
     */
    private $worldPublic;

    /**
     * @ORM\Column(type="text", name="extras", nullable=true)
     */
    private $extras;

    /**
     * @ORM\Column(type="boolean", name="new_hack")
     */
    private $newHack;

    /**
     * @ORM\Column(type="integer", name="copy_of", nullable=true)
     */
    private $copyOf;

    /**
     * @ORM\Column(type="string", name="workflow_status", length=255)
     */
    private $workflowStatus;

    /**
     * @ORM\Column(type="datetime", name="workflow_resubmission_date", nullable=true)
     */
    private $workflowResubmissionDate;

    /**
     * @ORM\Column(type="datetime", name="workflow_validity_date", nullable=true)
     */
    private $workflowValidityDate;

    /**
     * @ORM\Column(type="datetime", name="locking_date", nullable=true)
     */
    private $lockingDate;

    /**
     * @ORM\Column(type="integer", name="locking_user_id", nullable=true)
     */
    private $lockingUserId;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set versionId
     *
     * @param integer $versionId
     *
     * @return Material
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    /**
     * Get versionId
     *
     * @return integer
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return Material
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
     * @return Material
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
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return Material
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
     * @return Material
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
     * Set modifierId
     *
     * @param integer $modifierId
     *
     * @return Material
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
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return Material
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
     * @return Material
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
     * @return Material
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
     * Set description
     *
     * @param string $description
     *
     * @return Material
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set author
     *
     * @param string $author
     *
     * @return Material
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set publishingDate
     *
     * @param string $publishingDate
     *
     * @return Material
     */
    public function setPublishingDate($publishingDate)
    {
        $this->publishingDate = $publishingDate;

        return $this;
    }

    /**
     * Get publishingDate
     *
     * @return string
     */
    public function getPublishingDate()
    {
        return $this->publishingDate;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Material
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
     * Set worldPublic
     *
     * @param integer $worldPublic
     *
     * @return Material
     */
    public function setWorldPublic($worldPublic)
    {
        $this->worldPublic = $worldPublic;

        return $this;
    }

    /**
     * Get worldPublic
     *
     * @return integer
     */
    public function getWorldPublic()
    {
        return $this->worldPublic;
    }

    /**
     * Set extras
     *
     * @param array $extras
     *
     * @return Material
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return array
     */
    public function getExtras()
    {
        return mb_unserialize($this->extras);
    }

    /**
     * Set newHack
     *
     * @param boolean $newHack
     *
     * @return Material
     */
    public function setNewHack($newHack)
    {
        $this->newHack = $newHack;

        return $this;
    }

    /**
     * Get newHack
     *
     * @return boolean
     */
    public function getNewHack()
    {
        return $this->newHack;
    }

    /**
     * Set copyOf
     *
     * @param integer $copyOf
     *
     * @return Material
     */
    public function setCopyOf($copyOf)
    {
        $this->copyOf = $copyOf;

        return $this;
    }

    /**
     * Get copyOf
     *
     * @return integer
     */
    public function getCopyOf()
    {
        return $this->copyOf;
    }

    /**
     * Set workflowStatus
     *
     * @param string $workflowStatus
     *
     * @return Material
     */
    public function setWorkflowStatus($workflowStatus)
    {
        $this->workflowStatus = $workflowStatus;

        return $this;
    }

    /**
     * Get workflowStatus
     *
     * @return string
     */
    public function getWorkflowStatus()
    {
        return $this->workflowStatus;
    }

    /**
     * Set workflowResubmissionDate
     *
     * @param \DateTime $workflowResubmissionDate
     *
     * @return Material
     */
    public function setWorkflowResubmissionDate($workflowResubmissionDate)
    {
        $this->workflowResubmissionDate = $workflowResubmissionDate;

        return $this;
    }

    /**
     * Get workflowResubmissionDate
     *
     * @return \DateTime
     */
    public function getWorkflowResubmissionDate()
    {
        return $this->workflowResubmissionDate;
    }

    /**
     * Set workflowValidityDate
     *
     * @param \DateTime $workflowValidityDate
     *
     * @return Material
     */
    public function setWorkflowValidityDate($workflowValidityDate)
    {
        $this->workflowValidityDate = $workflowValidityDate;

        return $this;
    }

    /**
     * Get workflowValidityDate
     *
     * @return \DateTime
     */
    public function getWorkflowValidityDate()
    {
        return $this->workflowValidityDate;
    }

    /**
     * Set lockingDate
     *
     * @param \DateTime $lockingDate
     *
     * @return Material
     */
    public function setLockingDate($lockingDate)
    {
        $this->lockingDate = $lockingDate;

        return $this;
    }

    /**
     * Get lockingDate
     *
     * @return \DateTime
     */
    public function getLockingDate()
    {
        return $this->lockingDate;
    }

    /**
     * Set lockingUserId
     *
     * @param integer $lockingUserId
     *
     * @return Material
     */
    public function setLockingUserId($lockingUserId)
    {
        $this->lockingUserId = $lockingUserId;

        return $this;
    }

    /**
     * Get lockingUserId
     *
     * @return integer
     */
    public function getLockingUserId()
    {
        return $this->lockingUserId;
    }
}
