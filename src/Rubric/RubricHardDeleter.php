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

namespace App\Rubric;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

/**
 * Orchestrator for post-grace-period physical removal of soft-deleted
 * rubric items. Iterates every registered {@see RubricDeleter} and
 * delegates the bulk DELETE to each; shared auxiliary tables are handled
 * elsewhere (see {@see \App\Cron\Tasks\CronHardDelete}).
 */
readonly class RubricHardDeleter
{
    /**
     * @param iterable<RubricDeleter> $rubricDeleters
     */
    public function __construct(
        #[AutowireIterator('app.rubric.deleter')]
        private iterable $rubricDeleters,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Runs every registered rubric deleter and returns the total number of
     * rows physically removed. A failing deleter is logged and skipped so
     * one rubric's failure does not starve the others.
     */
    public function hardDeleteOlderThan(int $days): int
    {
        $count = 0;

        foreach ($this->rubricDeleters as $deleter) {
            try {
                $count += $deleter->hardDeleteOlderThan($days);
            } catch (Throwable $e) {
                $this->logger->error(sprintf(
                    'RubricHardDeleter: %s failed: %s',
                    $deleter::class,
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }
}
