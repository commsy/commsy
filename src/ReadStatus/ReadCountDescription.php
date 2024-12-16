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

namespace App\ReadStatus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class ReadCountDescription
{
    public function __construct(
        private int $readTotal,
        private int $readSinceModification,
        private int $userTotal
    ) {}

    public function getReadTotal(): int
    {
        return $this->readTotal;
    }

    public function getReadSinceModification(): int
    {
        return $this->readSinceModification;
    }

    public function getUserTotal(): int
    {
        return $this->userTotal;
    }
}
