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

use App\Utils\EntityDatesTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Section.
 */
#[ORM\Entity]
#[ORM\Table(name: 'section')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['material_item_id'], name: 'material_item_id')]
class Section
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $itemId;

    #[ORM\Column(name: 'version_id', type: Types::INTEGER)]
    // #[ORM\Id] // commented out to allow for pinned sections on a room's home page
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $versionId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: false)]
    private string $creatorId = '0';

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: true)]
    private ?int $modifierId = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'number', type: Types::SMALLINT, nullable: false)]
    private string $number = '0';

    #[ORM\ManyToOne(targetEntity: 'Materials', inversedBy: 'sections')]
    #[ORM\JoinColumn(name: 'material_item_id', referencedColumnName: 'item_id')]
    #[ORM\JoinColumn(name: 'version_id', referencedColumnName: 'version_id')]
    private ?Materials $material = null;

    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private string $public = '0';

    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setVersionId(int $versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function setContextId(int $contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): int
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
