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
 * Tag2tag.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag2tag')]
#[ORM\Index(columns: ['from_item_id'], name: 'from_item_id')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['deletion_date'], name: 'deletion_date')]
#[ORM\Index(columns: ['deleter_id'], name: 'deleter_id')]
class Tag2tag
{
    #[ORM\Column(name: 'link_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $linkId = null;

    #[ORM\Column(name: 'from_item_id', type: Types::INTEGER)]
    private int $fromItemId;

    #[ORM\Column(name: 'to_item_id', type: Types::INTEGER)]
    private int $toItemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER)]
    private int $creatorId;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?DateTime $creationDate = null;

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: false)]
    private string $modifierId = '0';

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?DateTime $modificationDate = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'sorting_place', type: Types::BOOLEAN, nullable: true)]
    private ?bool $sortingPlace = null;
}
