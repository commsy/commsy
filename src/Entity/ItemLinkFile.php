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

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ItemLinkFile.
 */
#[ORM\Entity]
#[ORM\Table(name: 'item_link_file')]
class ItemLinkFile
{
    #[ORM\Column(name: 'item_iid', type: Types::INTEGER)]
    #[ORM\Id]
    private int $itemId;

    #[ORM\Column(name: 'item_vid', type: Types::INTEGER)]
    #[ORM\Id]
    private int $itemVersionId;

    #[ORM\OneToOne(inversedBy: 'itemLink', targetEntity: Files::class)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'files_id', nullable: false)]
    #[ORM\Id]
    private Files $file;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): self
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getItemVersionId(): int
    {
        return $this->itemVersionId;
    }

    public function setItemVersionId(int $itemVersionId): self
    {
        $this->itemVersionId = $itemVersionId;
        return $this;
    }
}
