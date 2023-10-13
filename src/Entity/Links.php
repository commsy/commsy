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
 * Links.
 */
#[ORM\Entity]
#[ORM\Table(name: 'links')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['link_type'], name: 'link_type')]
#[ORM\Index(columns: ['from_item_id'], name: 'from_item_id')]
#[ORM\Index(columns: ['from_version_id'], name: 'from_version_id')]
#[ORM\Index(columns: ['to_item_id'], name: 'to_item_id')]
#[ORM\Index(columns: ['to_version_id'], name: 'to_version_id')]
class Links
{
    #[ORM\Column(name: 'from_item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $fromItemId = 0;

    #[ORM\Column(name: 'from_version_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $fromVersionId = 0;

    #[ORM\Column(name: 'to_item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $toItemId = 0;

    #[ORM\Column(name: 'to_version_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $toVersionId = 0;

    #[ORM\Column(name: 'link_type', type: Types::STRING, length: 30)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $linkType = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'x', type: Types::INTEGER, nullable: true)]
    private ?int $x = null;

    #[ORM\Column(name: 'y', type: Types::INTEGER, nullable: true)]
    private ?int $y = null;
}
