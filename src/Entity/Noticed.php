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
 * Noticed.
 */
#[ORM\Entity]
#[ORM\Table(name: 'noticed')]
class Noticed
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $itemId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'version_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $versionId = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'user_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $userId = '0';

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'read_date', type: 'datetime')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $readDate = '0000-00-00 00:00:00';
}
