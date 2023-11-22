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
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $userId;

    #[ORM\Column(name: 'rss', type: Types::STRING, length: 32)]
    private string $rss;

    #[ORM\Column(name: 'ical', type: Types::STRING, length: 32)]
    private string $ical;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Hash
    {
        $this->userId = $userId;
        return $this;
    }

    public function getRss(): string
    {
        return $this->rss;
    }

    public function setRss(string $rss): Hash
    {
        $this->rss = $rss;
        return $this;
    }

    public function getIcal(): string
    {
        return $this->ical;
    }

    public function setIcal(string $ical): Hash
    {
        $this->ical = $ical;
        return $this;
    }
}
