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

namespace App\Scheduler\Task;

use App\Entity\Files;
use App\Lock\FileLockManager;
use App\Repository\FilesRepository;
use DateInterval;
use DateTime;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '5 minutes')]
readonly class RemoveFileLocks
{
    public function __construct(
        private FilesRepository $filesRepository,
        private FileLockManager $fileLockManager,
    ) {
    }

    public function __invoke(): void
    {
        // Find files locked by more than two hours ago
        $threshold = (new DateTime())->sub(new DateInterval('PT2H'));
        $exceededFiles = $this->filesRepository->findAllLockedBefore($threshold);

        // Unlock all
        array_walk($exceededFiles, fn(Files $file) => $this->fileLockManager->unlock($file));
    }
}
