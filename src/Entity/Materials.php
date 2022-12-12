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

use App\Repository\MaterialsRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Materials.
 */
#[ORM\Entity(repositoryClass: MaterialsRepository::class)]
#[ORM\Table(name: 'materials')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'modifier_id', columns: ['modifier_id'])]
class Materials
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id')]
    private ?User $deleter = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private $creationDate = '0000-00-00 00:00:00';

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    private $modificationDate;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?DateTime $activationDate = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 16777215, nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'author', type: 'string', length: 200, nullable: true)]
    private $author;

    /**
     * @var string
     */
    #[ORM\Column(name: 'publishing_date', type: 'string', length: 20, nullable: true)]
    private $publishingDate;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private $public = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'world_public', type: 'smallint', nullable: false)]
    private $worldPublic = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: 'mbarray', nullable: true)]
    private $extras;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'new_hack', type: 'boolean', nullable: false)]
    private $newHack = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'copy_of', type: 'integer', nullable: true)]
    private $copyOf;

    /**
     * @var string
     */
    #[ORM\Column(name: 'workflow_status', type: 'string', length: 255, nullable: false)]
    private $workflowStatus = '3_none';

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'workflow_resubmission_date', type: 'datetime', nullable: true)]
    private $workflowResubmissionDate;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'workflow_validity_date', type: 'datetime', nullable: true)]
    private $workflowValidityDate;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'locking_date', type: 'datetime', nullable: true)]
    private $lockingDate;

    /**
     * @var int
     */
    #[ORM\Column(name: 'locking_user_id', type: 'integer', nullable: true)]
    private $lockingUserId;

    #[ORM\OneToMany(targetEntity: 'Section', mappedBy: 'material')]
    private Collection $sections;

    /**
     * @param int $itemId
     * @param int $versionId
     */
    public function __construct(#[ORM\Id]
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $itemId, /**
     * Todo: Id.
     */
    #[ORM\Column(name: 'version_id', type: 'integer')]
    private $versionId)
    {
        $this->sections = new ArrayCollection();
    }

    /**
     * Add section.
     *
     * @return Materials
     */
    public function addSection(Section $section)
    {
        $this->sections[] = $section;

        return $this;
    }

    /**
     * Remove section.
     */
    public function removeSection(Section $section)
    {
        $this->sections->removeElement($section);
    }

    /**
     * Get sections.
     *
     * @return Collection
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return Materials
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
     * @return Materials
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
     * @return Materials
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
     * Set creationDate.
     *
     * @param DateTime $creationDate
     *
     * @return Materials
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
     * Set modificationDate.
     *
     * @param DateTime $modificationDate
     *
     * @return Materials
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
     * Set activationDate.
     */
    public function setActivationDate(DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate.
     */
    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * Set deletionDate.
     *
     * @param DateTime $deletionDate
     *
     * @return Materials
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
     * Set title.
     *
     * @param string $title
     *
     * @return Materials
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
     * @return Materials
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
     * Set author.
     *
     * @param string $author
     *
     * @return Materials
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set publishingDate.
     *
     * @param string $publishingDate
     *
     * @return Materials
     */
    public function setPublishingDate($publishingDate)
    {
        $this->publishingDate = $publishingDate;

        return $this;
    }

    /**
     * Get publishingDate.
     *
     * @return string
     */
    public function getPublishingDate()
    {
        return $this->publishingDate;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Materials
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
     * Set worldPublic.
     *
     * @param int $worldPublic
     *
     * @return Materials
     */
    public function setWorldPublic($worldPublic)
    {
        $this->worldPublic = $worldPublic;

        return $this;
    }

    /**
     * Get worldPublic.
     *
     * @return int
     */
    public function getWorldPublic()
    {
        return $this->worldPublic;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Materials
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
     * Set newHack.
     *
     * @param bool $newHack
     *
     * @return Materials
     */
    public function setNewHack($newHack)
    {
        $this->newHack = $newHack;

        return $this;
    }

    /**
     * Get newHack.
     *
     * @return bool
     */
    public function getNewHack()
    {
        return $this->newHack;
    }

    /**
     * Set copyOf.
     *
     * @param int $copyOf
     *
     * @return Materials
     */
    public function setCopyOf($copyOf)
    {
        $this->copyOf = $copyOf;

        return $this;
    }

    /**
     * Get copyOf.
     *
     * @return int
     */
    public function getCopyOf()
    {
        return $this->copyOf;
    }

    /**
     * Set workflowStatus.
     *
     * @param string $workflowStatus
     *
     * @return Materials
     */
    public function setWorkflowStatus($workflowStatus)
    {
        $this->workflowStatus = $workflowStatus;

        return $this;
    }

    /**
     * Get workflowStatus.
     *
     * @return string
     */
    public function getWorkflowStatus()
    {
        return $this->workflowStatus;
    }

    /**
     * Set workflowResubmissionDate.
     *
     * @param DateTime $workflowResubmissionDate
     *
     * @return Materials
     */
    public function setWorkflowResubmissionDate($workflowResubmissionDate)
    {
        $this->workflowResubmissionDate = $workflowResubmissionDate;

        return $this;
    }

    /**
     * Get workflowResubmissionDate.
     *
     * @return DateTime
     */
    public function getWorkflowResubmissionDate()
    {
        return $this->workflowResubmissionDate;
    }

    /**
     * Set workflowValidityDate.
     *
     * @param DateTime $workflowValidityDate
     *
     * @return Materials
     */
    public function setWorkflowValidityDate($workflowValidityDate)
    {
        $this->workflowValidityDate = $workflowValidityDate;

        return $this;
    }

    /**
     * Get workflowValidityDate.
     *
     * @return DateTime
     */
    public function getWorkflowValidityDate()
    {
        return $this->workflowValidityDate;
    }

    /**
     * Set lockingDate.
     *
     * @param DateTime $lockingDate
     *
     * @return Materials
     */
    public function setLockingDate($lockingDate)
    {
        $this->lockingDate = $lockingDate;

        return $this;
    }

    /**
     * Get lockingDate.
     *
     * @return DateTime
     */
    public function getLockingDate()
    {
        return $this->lockingDate;
    }

    /**
     * Set lockingUserId.
     *
     * @param int $lockingUserId
     *
     * @return Materials
     */
    public function setLockingUserId($lockingUserId)
    {
        $this->lockingUserId = $lockingUserId;

        return $this;
    }

    /**
     * Get lockingUserId.
     *
     * @return int
     */
    public function getLockingUserId()
    {
        return $this->lockingUserId;
    }

    public function isIndexable()
    {
        return null == $this->deleter && null == $this->deletionDate;
    }

    /**
     * Set creator.
     *
     * @return Materials
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set deleter.
     *
     * @return Materials
     */
    public function setDeleter(User $deleter = null)
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter.
     *
     * @return User
     */
    public function getDeleter()
    {
        return $this->deleter;
    }

    /**
     * Set modifier.
     *
     * @return Materials
     */
    public function setModifier(User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier.
     *
     * @return User
     */
    public function getModifier()
    {
        return $this->modifier;
    }
}
