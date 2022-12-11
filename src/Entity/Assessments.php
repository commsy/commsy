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
 * Assessments.
 */
#[ORM\Entity]
#[ORM\Table(name: 'assessments')]
#[ORM\Index(name: 'item_link_id', columns: ['item_link_id'])]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'deleter_id', columns: ['deleter_id'])]
class Assessments
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $itemId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $contextId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $creatorId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;
    #[ORM\Column(name: 'creation_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    private \DateTime $creationDate;
    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletionDate = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_link_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $itemLinkId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'assessment', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $assessment = null;

    public function __construct()
    {
        $this->creationDate = new \DateTime('0000-00-00 00:00:00');
    }
}
