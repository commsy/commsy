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
use App\Utils\EntityDatesTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Materials.
 */
#[ORM\Entity(repositoryClass: MaterialsRepository::class)]
#[ORM\Table(name: 'materials')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['modifier_id'], name: 'modifier_id')]
class Materials
{
    use EntityDatesTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $itemId;

    #[ORM\Id]
    #[ORM\Column(name: 'version_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $versionId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id')]
    private ?User $deleter = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'author', type: Types::STRING, length: 200, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(name: 'publishing_date', type: Types::STRING, length: 20, nullable: true)]
    private ?string $publishingDate = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'world_public', type: Types::BOOLEAN, nullable: false)]
    private bool $worldPublic = false;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'new_hack', type: Types::BOOLEAN, nullable: false)]
    private bool $newHack = false;

    #[ORM\Column(name: 'copy_of', type: Types::INTEGER, nullable: true)]
    private ?int $copyOf = null;

    #[ORM\Column(name: 'workflow_status', type: Types::STRING, length: 255, nullable: false)]
    private string $workflowStatus = '3_none';

    #[ORM\Column(name: 'workflow_resubmission_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $workflowResubmissionDate = null;

    #[ORM\Column(name: 'workflow_validity_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $workflowValidityDate = null;

    #[ORM\OneToMany(mappedBy: 'material', targetEntity: 'Section')]
    private Collection $sections;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    /**
     * Add section.
     */
    public function addSection(Section $section): static
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

    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function setItemId(int $itemId): static
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setVersionId(int $versionId): static
    {
        $this->versionId = $versionId;

        return $this;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setActivationDate(?DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setPublishingDate(?string $publishingDate): static
    {
        $this->publishingDate = $publishingDate;

        return $this;
    }

    public function getPublishingDate(): ?string
    {
        return $this->publishingDate;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setWorldPublic(bool $worldPublic): static
    {
        $this->worldPublic = $worldPublic;

        return $this;
    }

    public function getWorldPublic(): bool
    {
        return $this->worldPublic;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setNewHack(bool $newHack): static
    {
        $this->newHack = $newHack;

        return $this;
    }

    public function getNewHack(): bool
    {
        return $this->newHack;
    }

    public function setCopyOf(?int $copyOf): static
    {
        $this->copyOf = $copyOf;

        return $this;
    }

    public function getCopyOf(): ?int
    {
        return $this->copyOf;
    }

    public function setWorkflowStatus(string $workflowStatus): static
    {
        $this->workflowStatus = $workflowStatus;

        return $this;
    }

    public function getWorkflowStatus(): string
    {
        return $this->workflowStatus;
    }

    public function setWorkflowResubmissionDate(?DateTimeInterface $workflowResubmissionDate): static
    {
        $this->workflowResubmissionDate = $workflowResubmissionDate;

        return $this;
    }

    public function getWorkflowResubmissionDate(): ?DateTimeInterface
    {
        return $this->workflowResubmissionDate;
    }

    public function setWorkflowValidityDate(?DateTimeInterface $workflowValidityDate): static
    {
        $this->workflowValidityDate = $workflowValidityDate;

        return $this;
    }

    public function getWorkflowValidityDate(): ?DateTimeInterface
    {
        return $this->workflowValidityDate;
    }

    public function isIndexable()
    {
        return null == $this->deleter && null == $this->deletionDate;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setDeleter(?User $deleter): static
    {
        $this->deleter = $deleter;

        return $this;
    }

    public function getDeleter(): ?User
    {
        return $this->deleter;
    }

    public function setModifier(?User $modifier): static
    {
        $this->modifier = $modifier;

        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }
}
