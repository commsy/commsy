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
 * Sessions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'sessions')]
class Sessions
{
    /**
     * @var binary
     */
    #[ORM\Column(name: 'sess_id', type: 'binary')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $sessId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'sess_data', type: 'blob', length: 65535, nullable: false)]
    private $sessData;

    /**
     * @var int
     */
    #[ORM\Column(name: 'sess_lifetime', type: 'integer', nullable: false)]
    private $sessLifetime;

    /**
     * @var int
     */
    #[ORM\Column(name: 'sess_time', type: 'integer', nullable: false)]
    private $sessTime;
}
