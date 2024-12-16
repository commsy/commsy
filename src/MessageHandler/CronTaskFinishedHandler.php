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

namespace App\MessageHandler;

use App\Cron\CronManager;
use App\Message\CronTaskFinished;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CronTaskFinishedHandler
{
    public function __construct(
        private CronManager $cronManager,
    ) {
    }

    public function __invoke(CronTaskFinished $cronTaskFinished): void
    {
        $cronTask = $cronTaskFinished->getCronName();

        $this->cronManager->updateLastRun($cronTask);
    }
}
