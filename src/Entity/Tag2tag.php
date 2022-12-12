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
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag2tag.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag2tag')]
#[ORM\Index(name: 'from_item_id', columns: ['from_item_id'])]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'deletion_date', columns: ['deletion_date'])]
#[ORM\Index(name: 'deleter_id', columns: ['deleter_id'])]
class Tag2tag
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'link_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $linkId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'from_item_id', type: 'integer', nullable: false)]
    private $fromItemId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'to_item_id', type: 'integer', nullable: false)]
    private $toItemId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: false)]
    private $contextId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private $creatorId = '0';

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var int
     */
    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: false)]
    private $modifierId = '0';

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'sorting_place', type: 'boolean', nullable: true)]
    private $sortingPlace;
}
