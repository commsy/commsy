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
 * ItemLinkFile.
 */
#[ORM\Entity]
#[ORM\Table(name: 'item_link_file')]
class ItemLinkFile
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_iid', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $itemIid = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'item_vid', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $itemVid = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'file_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $fileId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletionDate = null;
}
