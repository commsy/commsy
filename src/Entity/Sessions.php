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
 * Sessions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'sessions')]
class Sessions
{
    #[ORM\Column(name: 'sess_id', type: Types::BINARY)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?string $sessId = null;

    #[ORM\Column(name: 'sess_data', type: Types::BLOB, length: 65535, nullable: false)]
    private string $sessData;

    #[ORM\Column(name: 'sess_lifetime', type: Types::INTEGER, nullable: false)]
    private int $sessLifetime;

    #[ORM\Column(name: 'sess_time', type: Types::INTEGER, nullable: false)]
    private int $sessTime;
}
