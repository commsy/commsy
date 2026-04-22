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

use App\Rubric\Discussion\DiscussionDeleter;
use App\Rubric\Material\MaterialDeleter;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Rubric\Todo\TodoDeleter;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Nightly cron that soft-deletes abandoned draft rows (items created by the
 * "new entry" form flow but never persisted). Dispatches through the
 * registered {@see RubricDeleter} implementations; hard-delete happens
 * later via the regular hard-delete cron.
 */
class CronCleanDrafts implements CronTaskInterface
{
    /** Legacy parity: deleter_id = 0 when no user is bound (cron context). */
    private const SYSTEM_DELETER_ID = 0;

    private readonly cs_environment $legacyEnvironment;

    /**
     * @var array<string, RubricDeleter>
     */
    private readonly array $deleterByRubricType;

    /**
     * @param iterable<RubricDeleter> $rubricDeleters
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        #[AutowireIterator('app.rubric.deleter')]
        iterable $rubricDeleters,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $map = [];
        foreach ($rubricDeleters as $deleter) {
            $map[$deleter->rubricType()->value] = $deleter;
        }
        $this->deleterByRubricType = $map;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $drafts = $itemManager->getAllDraftItems();

        foreach ($drafts as $draft) {
            $itemId = (int) $draft['item_id'];
            $type = (string) $draft['type'];

            if ($this->deleteViaRubricDeleter($type, $itemId)) {
                continue;
            }

            $this->deleteViaSubEntryDeleter($type, $itemId);
        }
    }

    public function getSummary(): string
    {
        return 'Delete drafts';
    }

    /**
     * Dispatches primary rubric-item types to their matching RubricDeleter.
     */
    private function deleteViaRubricDeleter(string $type, int $itemId): bool
    {
        $rubricType = RubricType::tryFromLegacyString($type);
        if ($rubricType === null) {
            return false;
        }

        $deleter = $this->deleterByRubricType[$rubricType->value] ?? null;
        if ($deleter === null) {
            return false;
        }

        $deleter->softDeleteItem($itemId, self::SYSTEM_DELETER_ID);

        return true;
    }

    /**
     * Dispatches sub-entry types (section, step, discarticle) to the
     * sub-entry method on their parent rubric's deleter.
     */
    private function deleteViaSubEntryDeleter(string $type, int $itemId): bool
    {
        switch ($type) {
            case 'section':
                $deleter = $this->deleterByRubricType[RubricType::Material->value] ?? null;
                if ($deleter instanceof MaterialDeleter) {
                    $deleter->deleteSection($itemId, self::SYSTEM_DELETER_ID);

                    return true;
                }

                return false;

            case 'step':
                $deleter = $this->deleterByRubricType[RubricType::Todo->value] ?? null;
                if ($deleter instanceof TodoDeleter) {
                    $deleter->deleteStep($itemId, self::SYSTEM_DELETER_ID);

                    return true;
                }

                return false;

            case 'discarticle':
                $deleter = $this->deleterByRubricType[RubricType::Discussion->value] ?? null;
                if ($deleter instanceof DiscussionDeleter) {
                    $deleter->deleteArticle($itemId, self::SYSTEM_DELETER_ID);

                    return true;
                }

                return false;
        }

        return false;
    }
}
