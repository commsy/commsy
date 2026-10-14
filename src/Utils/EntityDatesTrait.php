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

namespace App\Utils;

use ApiPlatform\Metadata\ApiProperty;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait EntityDatesTrait
{
    /**
     * Nullable although every write path fills it: some tables allow NULL here
     * (a section's or a step's creation date, most rubrics' modification date),
     * and a row holding it must read as null rather than leave the property
     * unset, which fails on first access and drops the field from a document.
     *
     * The schema states this itself: the mapping, which it would go by, is
     * shared with tables that do not allow NULL.
     */
    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    #[Groups(['api', 'export'])]
    #[ApiProperty(schema: [
        'type' => ['string', 'null'],
        'format' => 'date-time',
        'description' => 'When it was created; null if no date is stored.',
    ])]
    private ?DateTime $creationDate = null;

    /**
     * Nullable for the same reason as the creation date.
     */
    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE)]
    #[Groups(['api', 'export'])]
    #[ApiProperty(schema: [
        'type' => ['string', 'null'],
        'format' => 'date-time',
        'description' => 'When it was last changed; null if no date is stored.',
    ])]
    private ?DateTime $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $deletionDate = null;

    #[ORM\PrePersist]
    public function setInitialDateValues(): void
    {
        $this->creationDate = new DateTime('now');
        $this->modificationDate = new DateTime('now');
    }

    #[ORM\PreUpdate]
    public function setModificationDateValue(): void
    {
        $this->modificationDate = new DateTime('now');
    }

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getCreationDate(): ?DateTime
    {
        return $this->creationDate;
    }

    public function setModificationDate(DateTime $modificationDate): self
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    public function getModificationDate(): ?DateTime
    {
        return $this->modificationDate;
    }

    public function setDeletionDate(?DateTime $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getDeletionDate(): ?DateTime
    {
        return $this->deletionDate;
    }
}
