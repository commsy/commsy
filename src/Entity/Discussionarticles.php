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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'discussionarticles')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'discussion_id', columns: ['discussion_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Discussionarticles
{
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $itemId = 0;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'discussion_id', type: Types::INTEGER)]
    private ?int $discussionId = 0;

    #[ORM\ManyToOne(targetEntity: 'Discussions', inversedBy: 'discussionarticles')]
    #[ORM\JoinColumn(name: 'discussion_id', referencedColumnName: 'item_id')]
    private ?Discussions $discussion = null;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER)]
    private ?int $creatorId = 0;

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: true)]
    private ?int $modifierId = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private \DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'position', type: Types::STRING, length: 255)]
    private ?string $position = '1';

    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN)]
    private ?bool $public = false;

    public function __construct()
    {
        $this->creationDate = new \DateTime('0000-00-00 00:00:00');
    }

    /**
     * Set discussion.
     *
     * @return Discussion
     */
    public function setDiscussion(Discussions $discussion = null)
    {
        $this->discussion = $discussion;

        return $this;
    }

    /**
     * Get discussion.
     *
     * @return Discussions
     */
    public function getDiscussion()
    {
        return $this->discussion;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Discussionarticles
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set discussionId.
     *
     * @param int $discussionId
     *
     * @return Discussionarticles
     */
    public function setDiscussionId($discussionId)
    {
        $this->discussionId = $discussionId;

        return $this;
    }

    /**
     * Get discussionId.
     *
     * @return int
     */
    public function getDiscussionId()
    {
        return $this->discussionId;
    }

    /**
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return Discussionarticles
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set modifierId.
     *
     * @param int $modifierId
     *
     * @return Discussionarticles
     */
    public function setModifierId($modifierId)
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    /**
     * Get modifierId.
     *
     * @return int
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Discussionarticles
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId.
     *
     * @return int
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime $creationDate
     *
     * @return Discussionarticles
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate.
     *
     * @param \DateTime $modificationDate
     *
     * @return Discussionarticles
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate.
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate.
     *
     * @param \DateTime $deletionDate
     *
     * @return Discussionarticles
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate.
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Discussionarticles
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return Discussionarticles
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Discussionarticles
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Discussionarticles
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }
}
