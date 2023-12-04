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

use App\Repository\FilesRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Files.
 */
#[ORM\Entity(repositoryClass: FilesRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'files')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Files
{
    #[ORM\Column(name: 'files_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $filesId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: true)]
    private ?int $creatorId = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'filename', type: Types::STRING, length: 255)]
    private string $filename;

    #[ORM\Column(name: 'filepath', type: Types::STRING, length: 255)]
    private string $filepath;

    #[ORM\Column(name: 'size', type: Types::INTEGER, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?Portal $portal = null;

    #[ORM\OneToOne(mappedBy: 'file', targetEntity: ItemLinkFile::class, cascade: ['persist', 'remove'])]
    private ItemLinkFile $itemLink;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $lockingId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lockingDate = null;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }

    public function getContent(): string|bool|null
    {
        $filePath = $this->getFilepath();

        if (file_exists($filePath)) {
            return file_get_contents(
                $filePath,
                'r'
            );
        } else {
            return null;
        }
    }

    public function getFilesId(): int
    {
        return $this->filesId;
    }

    public function setContextId($contextId): self
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setCreatorId($creatorId): self
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function setDeleterId($deleterId): self
    {
        $this->deleterId = $deleterId;

        return $this;
    }


    public function getDeleterId(): ?int
    {
        return $this->deleterId;
    }

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    #[ORM\PreUpdate]
    public function setModificationDateValue()
    {
        $this->modificationDate = new DateTime('now');
    }

    public function setModificationDate(DateTimeInterface $modificationDate): self
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): DateTimeInterface
    {
        return $this->modificationDate;
    }

    public function setDeletionDate($deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getDeletionDate(): ?DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilepath(string $filepath): self
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function getPortal(): ?Portal
    {
        return $this->portal;
    }

    public function setPortal(?Portal $portal): self
    {
        $this->portal = $portal;

        return $this;
    }

    public function getItemLink(): ItemLinkFile
    {
        return $this->itemLink;
    }

    public function setItemLink(ItemLinkFile $itemLink): self
    {
        $this->itemLink = $itemLink;

        return $this;
    }

    public function getLockingId(): ?string
    {
        return $this->lockingId;
    }

    public function setLockingId(?string $lockingId): self
    {
        $this->lockingId = $lockingId;

        return $this;
    }

    public function getLockingDate(): ?DateTimeInterface
    {
        return $this->lockingDate;
    }

    public function setLockingDate(?DateTimeInterface $lockingDate): self
    {
        $this->lockingDate = $lockingDate;

        return $this;
    }
}
