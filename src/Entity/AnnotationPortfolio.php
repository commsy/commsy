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
 * AnnotationPortfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'annotation_portfolio')]
#[ORM\Index(columns: ['row', 'column'], name: 'row')]
class AnnotationPortfolio
{
    #[ORM\Column(name: 'p_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $pId = 0;

    #[ORM\Column(name: 'a_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $aId = 0;

    #[ORM\Column(name: 'row', type: Types::INTEGER)]
    private ?int $row = 0;

    #[ORM\Column(name: 'column', type: Types::INTEGER)]
    private ?int $column = 0;
}
