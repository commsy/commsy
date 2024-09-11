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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'reader_user_idx', columns: ['user_id'])]
#[ORM\Table(name: 'reader')]
#[ORM\UniqueConstraint(name: 'reader_unique_idx', columns: ['item_id', 'version_id', 'user_id', 'read_date'])]
class Reader
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    private int $itemId;

    #[ORM\Column(name: 'version_id', type: Types::INTEGER)]
    private int $versionId;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private int $userId;

    #[ORM\Column(name: 'read_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $readDate;

    public function __construct()
    {
        $this->readDate = new DateTime();
    }

    #[ORM\PreUpdate]
    public function updateReadDate(PreUpdateEventArgs $eventArgs): void
    {
        $this->readDate = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Reader
    {
        $this->id = $id;
        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): Reader
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function setVersionId(int $versionId): Reader
    {
        $this->versionId = $versionId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Reader
    {
        $this->userId = $userId;
        return $this;
    }

    public function getReadDate(): DateTime
    {
        return $this->readDate;
    }

    public function setReadDate(DateTime $readDate): Reader
    {
        $this->readDate = $readDate;
        return $this;
    }
}
