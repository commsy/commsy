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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Annotations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'annotations')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'linked_item_id', columns: ['linked_item_id'])]
class Annotations
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
    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: true)]
    private ?int $modifierId = null;
    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private \DateTime $creationDate;
    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;
    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletionDate = null;
    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modificationDate = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'linked_item_id', type: Types::INTEGER)]
    private ?int $linkedItemId = 0;
    /**
     * @var int
     */
    #[ORM\Column(name: 'linked_version_id', type: Types::INTEGER)]
    private ?int $linkedVersionId = 0;
    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $extras = null;
    /**
     * @var bool
     */
    #[ORM\Column(name: 'public', type: Types::BOOLEAN)]
    private ?bool $public = false;

    public function __construct()
    {
        $this->creationDate = new \DateTime('0000-00-00 00:00:00');
    }
}
