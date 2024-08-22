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
use App\Message\CronTaskRun;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CronTaskRunHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CronManager $cronManager,
    ) {
    }

    public function __invoke(CronTaskRun $cronTaskRun): void
    {
        $cronName = $cronTaskRun->getCronName();

        $this->cronManager->execute($cronName);

        // Dispatch a new finished message which can also be handled asynchronously
        // and will update the last run time
        $this->messageBus->dispatch(new CronTaskFinished($cronName));
    }
}
