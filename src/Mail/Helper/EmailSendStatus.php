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


namespace App\Mail\Helper;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class EmailSendStatus
{
    public function __construct(
        private bool $success,
        private int $numRecipients
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getNumRecipients(): int
    {
        return $this->numRecipients;
    }
}
