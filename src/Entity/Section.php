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

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Section.
 */
#[ORM\Entity]
#[ORM\Table(name: 'section')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'material_item_id', columns: ['material_item_id'])]
class Section
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $itemId = '0';
    /**
     * @var int
     */
    #[ORM\Column(name: 'version_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $versionId = '0';
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
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private DateTime $creationDate;
    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    private $modificationDate;
    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;
    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 16_777_215, nullable: true)]
    private $description;
    /**
     * @var int
     */
    #[ORM\Column(name: 'number', type: 'smallint', nullable: false)]
    private $number = '0';
    #[ORM\ManyToOne(targetEntity: 'Materials', inversedBy: 'sections')]
    #[ORM\JoinColumn(name: 'material_item_id', referencedColumnName: 'item_id')]
    #[ORM\JoinColumn(name: 'version_id', referencedColumnName: 'version_id')]
    private ?Materials $material = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private $extras;
    /**
     * @var bool
     */
    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private $public = '0';

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return Section
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
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
     * Set versionId.
     *
     * @param int $versionId
     *
     * @return Section
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    /**
     * Get versionId.
     *
     * @return int
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Section
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
     * @return Section
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
     * @return Section
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
     * Set creationDate.
     *
     * @param DateTime $creationDate
     *
     * @return Section
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Section
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
     * Set deletionDate.
     *
     * @param DateTime $deletionDate
     *
     * @return Section
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate.
     *
     * @return DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set modificationDate.
     *
     * @param DateTime $modificationDate
     *
     * @return Section
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate.
     *
     * @return DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Section
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

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Section
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
     * Set number.
     *
     * @param int $number
     *
     * @return Section
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Section
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
     * @return Section
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

    /**
     * Set material.
     *
     * @return Section
     */
    public function setMaterial(Materials $material = null)
    {
        $this->material = $material;

        return $this;
    }

    /**
     * Get material.
     *
     * @return Materials
     */
    public function getMaterial()
    {
        return $this->material;
    }
}
