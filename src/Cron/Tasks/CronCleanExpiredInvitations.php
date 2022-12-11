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

namespace App\Cron\Tasks;

use App\Services\InvitationsService;

class CronCleanExpiredInvitations implements CronTaskInterface
{
    public function __construct(private InvitationsService $invitationsService)
    {
    }

    public function run(?\DateTimeImmutable $lastRun): void
    {
        $this->invitationsService->deleteExpiredInvitations();
    }

    public function getSummary(): string
    {
        return 'Delete expired invitations';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}
