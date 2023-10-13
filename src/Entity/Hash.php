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
 * Hash.
 */
#[ORM\Entity]
#[ORM\Table(name: 'hash')]
#[ORM\Index(columns: ['rss'], name: 'rss')]
#[ORM\Index(columns: ['ical'], name: 'ical')]
class Hash
{
    #[ORM\Column(name: 'user_item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $userItemId = null;

    #[ORM\Column(name: 'rss', type: Types::STRING, length: 32, nullable: true)]
    private ?string $rss = null;

    #[ORM\Column(name: 'ical', type: Types::STRING, length: 32, nullable: true)]
    private ?string $ical = null;

    /**
     * Get iCal.
     *
     * @return string
     */
    public function getICal()
    {
        return $this->iCal;
    }
}
