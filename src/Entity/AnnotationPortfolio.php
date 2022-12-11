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
 * AnnotationPortfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'annotation_portfolio')]
#[ORM\Index(name: 'row', columns: ['row', 'column'])]
class AnnotationPortfolio
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'p_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $pId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'a_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $aId = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'row', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $row = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'column', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $column = 0;
}
