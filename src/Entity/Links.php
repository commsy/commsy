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
 * Links.
 */
#[ORM\Entity]
#[ORM\Table(name: 'links')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'link_type', columns: ['link_type'])]
#[ORM\Index(name: 'from_item_id', columns: ['from_item_id'])]
#[ORM\Index(name: 'from_version_id', columns: ['from_version_id'])]
#[ORM\Index(name: 'to_item_id', columns: ['to_item_id'])]
#[ORM\Index(name: 'to_version_id', columns: ['to_version_id'])]
class Links
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'from_item_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $fromItemId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'from_version_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $fromVersionId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'to_item_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $toItemId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'to_version_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $toVersionId = 0;

    /**
     * @var string
     */
    #[ORM\Column(name: 'link_type', type: \Doctrine\DBAL\Types\Types::STRING, length: 30)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $linkType = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

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

    /**
     * @var int
     */
    #[ORM\Column(name: 'x', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $x = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'y', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $y = null;
}
