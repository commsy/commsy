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
 * Orchestrator for the post-grace-period physical removal of soft-deleted
 * rubric items. Replaces the per-manager `cs_*_manager::deleteReallyOlderThan()`
 * calls that {@see \App\Cron\Tasks\CronHardDelete} used to make in a loop
 * over legacy item-type constants.
 *
 * The orchestrator is deliberately thin — each {@see RubricDeleter} knows
 * which tables are rubric-owned (primary table plus any sub-entry table
 * like `section` / `step` / `discussionarticles`) and does the bulk DELETE
 * itself. This keeps rubric-specific concerns out of the central cron
 * task and mirrors the shape of {@see \App\Room\RoomHardDeleter}.
 *
 * Auxiliary tables that are not owned by any single rubric (shared
 * `items` twin rows, `links`, `link_items`, `tag`, `tag2tag`) and the two
 * tables with non-standard hard-delete semantics (`files` needs FS
 * cleanup, `item_link_file` is a pure join table) stay on the legacy
 * {@see \cs_manager::deleteReallyOlderThan()} path for now — see the
 * kept-legacy list in {@see \App\Cron\Tasks\CronHardDelete}.
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
     * Runs every registered rubric deleter's {@see RubricDeleter::hardDeleteOlderThan()}
     * and returns the total number of rows physically removed across all
     * rubrics — useful for cron summary logging.
     *
     * A failing deleter is logged and skipped so one rubric's failure does
     * not starve the others out of their cleanup pass.
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
