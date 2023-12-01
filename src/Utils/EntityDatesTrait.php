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

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait EntityDatesTrait
{
    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $modificationDate;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $deletionDate;

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

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setModificationDate(DateTime $modificationDate): self
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    public function getModificationDate(): DateTime
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
