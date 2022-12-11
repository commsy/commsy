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
 * Hash.
 */
#[ORM\Entity]
#[ORM\Table(name: 'hash')]
#[ORM\Index(name: 'rss', columns: ['rss'])]
#[ORM\Index(name: 'ical', columns: ['ical'])]
class Hash
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'user_item_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $userItemId = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'rss', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true)]
    private ?string $rss = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ical', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true)]
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
