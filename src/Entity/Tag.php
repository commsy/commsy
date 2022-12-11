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

use Doctrine\ORM\Mapping as ORM;

/**
 * Tag.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
class Tag
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private $creatorId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: true)]
    private $modifierId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id')]
    private ?\App\Entity\User $deleter = null;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

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
     * @return Tag
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
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return Tag
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
     * @return Tag
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
     * Set deleter.
     *
     * @return Labels
     */
    public function setDeleter(User $deleter = null)
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter.
     *
     * @return \App\Entity\User
     */
    public function getDeleter()
    {
        return $this->deleter;
    }

    /**
     * Set creationDate.
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
     * @return Tag
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
     * @return Tag
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
     * Set title.
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
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
