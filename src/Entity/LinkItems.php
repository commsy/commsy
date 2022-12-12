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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * LinkItems.
 */
#[ORM\Entity]
#[ORM\Table(name: 'link_items')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'first_item_id', columns: ['first_item_id'])]
#[ORM\Index(name: 'second_item_id', columns: ['second_item_id'])]
#[ORM\Index(name: 'first_item_type', columns: ['first_item_type'])]
#[ORM\Index(name: 'second_item_type', columns: ['second_item_type'])]
#[ORM\Index(name: 'deletion_date', columns: ['deletion_date'])]
#[ORM\Index(name: 'deleter_id', columns: ['deleter_id'])]
class LinkItems
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $itemId = 0;
    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: Types::INTEGER)]
    private ?int $creatorId = 0;
    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;
    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $creationDate;
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $modificationDate = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'first_item_id', type: Types::INTEGER)]
    private ?int $firstItemId = 0;
    /**
     * @var string
     */
    #[ORM\Column(name: 'first_item_type', type: Types::STRING, length: 15, nullable: true)]
    private ?string $firstItemType = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'second_item_id', type: Types::INTEGER)]
    private ?int $secondItemId = 0;
    /**
     * @var string
     */
    #[ORM\Column(name: 'second_item_type', type: Types::STRING, length: 15, nullable: true)]
    private ?string $secondItemType = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'sorting_place', type: Types::INTEGER, nullable: true)]
    private ?int $sortingPlace = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $extras = null;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }
}
