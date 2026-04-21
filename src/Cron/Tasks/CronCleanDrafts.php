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
 * Nightly cron that purges abandoned "draft" rows — items created by the
 * "new entry" form flow but never persisted by the user (e.g. the user
 * closed the tab after the draft was prepared but before saving).
 *
 * Historically this task delegated to the legacy `cs_item::delete()`
 * cascade (which in turn soft-deletes the rubric-specific row and the
 * `items` twin with `deleter_id = 0` because the cron has no authenticated
 * user). As part of #5082 we dispatch through the {@see RubricDeleter}
 * implementations instead, preserving the exact same soft-delete semantics
 * — the deleted drafts are eventually hard-deleted by the hard-delete cron,
 * just like any other soft-deleted row.
 *
 * Every draft-capable rubric now has a dedicated deleter:
 * - `setDraftStatus(1)` is called for announcement, annotation, date,
 *   discussion, discarticle, material, section, todo, step, label (incl.
 *   group as a label subtype).
 * - All of the above route through a `RubricDeleter` (primary types) or
 *   through a sub-entry method on the parent deleter (section/step/
 *   discarticle).
 *
 * The previously temporary legacy fallback has therefore been removed;
 * any future rubric draft type is expected to register its own
 * `RubricDeleter` before shipping.
 */
class CronCleanDrafts implements CronTaskInterface
{
    /**
     * Sentinel deleter id used when no user is in session (cron context).
     * Matches the legacy `cs_*_manager::delete()` convention of
     * `$current_user->getItemID() ?: 0`.
     */
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
     * Dispatches primary rubric-item types (announcement, annotation, date,
     * discussion, material, todo) to their matching RubricDeleter.
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
     * sub-entry method on their parent rubric's deleter. Sub-entries have
     * no RubricType case of their own because they are owned by the parent.
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
