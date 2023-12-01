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
 * Portfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'portfolio')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['modifier_id'], name: 'modifier_id')]
class Portfolio
{
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER)]
    private int $creatorId;

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER)]
    private int $modifierId;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: false)]
    private string $description;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private DateTimeInterface $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private DateTimeInterface $modificationDate;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'template', type: Types::BOOLEAN, nullable: false)]
    private string $template = '-1';
}
