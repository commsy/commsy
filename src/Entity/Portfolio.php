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
 * Portfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'portfolio')]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'modifier_id', columns: ['modifier_id'])]
class Portfolio
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private $creatorId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: false)]
    private $modifierId = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 16777215, nullable: false)]
    private $description;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private $creationDate;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    private $modificationDate;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'template', type: 'boolean', nullable: false)]
    private $template = '-1';
}
