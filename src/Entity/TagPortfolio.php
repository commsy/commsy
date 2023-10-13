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
 * TagPortfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag_portfolio')]
#[ORM\Index(columns: ['row', 'column'], name: 'row')]
class TagPortfolio
{
    #[ORM\Column(name: 'p_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $pId;

    #[ORM\Column(name: 't_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $tId;

    #[ORM\Column(name: 'row', type: Types::INTEGER, nullable: true)]
    private int $row;

    #[ORM\Column(name: 'column', type: Types::INTEGER, nullable: true)]
    private int $column;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $description = null;
}
