<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Discussions
 *
 * @ORM\Table(name="discussions", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"})})
 * @ORM\Entity
 */
class Discussions
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
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=200, nullable=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="latest_article_item_id", type="integer", nullable=true)
     */
    private $latestArticleItemId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="latest_article_modification_date", type="datetime", nullable=true)
     */
    private $latestArticleModificationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="discussion_type", type="string", length=10, nullable=false)
     */
    private $discussionType = 'simple';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="locking_date", type="datetime", nullable=true)
     */
    private $lockingDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="locking_user_id", type="integer", nullable=true)
     */
    private $lockingUserId;

    /**
     * @ORM\OneToMany(targetEntity="Discussionarticles", mappedBy="discussion")
     */
    private $discussionarticles;

    public function isIndexable()
    {
        return ($this->deleterId == null && $this->deletionDate == null);
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
     * @return Discussions
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
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return Discussions
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
     * @return Discussions
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
     * @return Discussions
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
     * @return Discussions
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
     * @return Discussions
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
     * Set latestArticleItemId
     *
     * @param integer $latestArticleItemId
     *
     * @return Discussions
     */
    public function setLatestArticleItemId($latestArticleItemId)
    {
        $this->latestArticleItemId = $latestArticleItemId;

        return $this;
    }

    /**
     * Get latestArticleItemId
     *
     * @return integer
     */
    public function getLatestArticleItemId()
    {
        return $this->latestArticleItemId;
    }

    /**
     * Set latestArticleModificationDate
     *
     * @param \DateTime $latestArticleModificationDate
     *
     * @return Discussions
     */
    public function setLatestArticleModificationDate($latestArticleModificationDate)
    {
        $this->latestArticleModificationDate = $latestArticleModificationDate;

        return $this;
    }

    /**
     * Get latestArticleModificationDate
     *
     * @return \DateTime
     */
    public function getLatestArticleModificationDate()
    {
        return $this->latestArticleModificationDate;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Discussions
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set discussionType
     *
     * @param string $discussionType
     *
     * @return Discussions
     */
    public function setDiscussionType($discussionType)
    {
        $this->discussionType = $discussionType;

        return $this;
    }

    /**
     * Get discussionType
     *
     * @return string
     */
    public function getDiscussionType()
    {
        return $this->discussionType;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Discussions
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
     * Set extras
     *
     * @param string $extras
     *
     * @return Discussions
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
     * Set lockingDate
     *
     * @param \DateTime $lockingDate
     *
     * @return Discussions
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
     * @return Discussions
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

    /**
     * Set creator
     *
     * @param \App\Entity\User $creator
     *
     * @return Discussions
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
     * @return Discussions
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
     * Add discussionarticle
     *
     * @param \App\Entity\Discussionarticles $discussionarticle
     *
     * @return Materials
     */
    public function addDiscussionarticle(\App\Entity\Discussionarticles $discussionarticle)
    {
        $this->discussionarticles[] = $discussionarticle;

        return $this;
    }

    /**
     * Remove discussionarticle
     *
     * @param \App\Entity\Discussionarticles $discussionarticle
     */
    public function removeDiscussionarticle(\App\Entity\Discussionarticles $discussionarticle)
    {
        $this->discussionarticles->removeElement($discussionarticle);
    }

    /**
     * Get discussionarticles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDiscussionarticles()
    {
        return $this->discussionarticles;
    }
}
