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
 * TagPortfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag_portfolio')]
#[ORM\Index(name: 'row', columns: ['row', 'column'])]
class TagPortfolio
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'p_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $pId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 't_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $tId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'row', type: 'integer', nullable: true)]
    private $row = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'column', type: 'integer', nullable: true)]
    private $column = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 65535, nullable: true)]
    private $description;
}
