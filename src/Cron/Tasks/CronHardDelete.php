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

use App\Assessment\AssessmentDeleter;
use App\Files\FileDeleter;
use App\Legacy\LegacyAuxHardDeleter;
use App\Room\RoomHardDeleter;
use App\Rubric\RubricHardDeleter;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class CronHardDelete implements CronTaskInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private RoomHardDeleter $roomHardDeleter,
        private RubricHardDeleter $rubricHardDeleter,
        private LegacyAuxHardDeleter $legacyAuxHardDeleter,
        private FileDeleter $fileDeleter,
        private AssessmentDeleter $assessmentDeleter,
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $deleteDays = $this->parameterBag->get('commsy.settings.delete_days');
        if (empty($deleteDays) || !is_numeric($deleteDays)) {
            return;
        }
        $deleteDays = (int) $deleteDays;

        $this->rubricHardDeleter->hardDeleteOlderThan($deleteDays);

        // Files first: item_link_file FK-references files, and filesystem
        // cleanup needs the row data.
        $this->fileDeleter->hardDeleteExpiredFiles($deleteDays);

        $this->legacyAuxHardDeleter->hardDeleteLinkItemRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTagRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTag2TagPivotRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTaskRows($deleteDays);
        // Legacy had no deleteReallyOlderThan on cs_assessments_manager,
        // so soft-deleted rows accumulated forever — this closes that gap.
        $this->assessmentDeleter->hardDeleteOlderThan($deleteDays);
        // `user` before `items`: the items twin (type='user') is the
        // parent row, so deleting the user child first leaves items
        // ready to drop in the items pass below.
        $this->legacyAuxHardDeleter->hardDeleteUserRows($deleteDays);
        // `items` last: referenced as shared twin by every rubric row above,
        // and now also by the user rows just deleted.
        $this->legacyAuxHardDeleter->hardDeleteItemsRows($deleteDays);

        $this->roomHardDeleter->hardDeleteRoomsOlderThan($deleteDays);
        $this->roomHardDeleter->hardDeletePortalsOlderThan($deleteDays);
    }

    public function getSummary(): string
    {
        return 'Finally delete soft deleted items';
    }
}
