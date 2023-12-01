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
 * UserPortfolio.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_portfolio')]
class UserPortfolio
{
    #[ORM\Column(name: 'p_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $pId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'u_id', type: \Doctrine\DBAL\Types\Types::STRING, length: 32)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $uId = null;
}
