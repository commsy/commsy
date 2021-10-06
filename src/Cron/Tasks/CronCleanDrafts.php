<?php

namespace App\Cron\Tasks;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronCleanDrafts implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $drafts = $itemManager->getAllDraftItems();

        foreach ($drafts as $draft) {
            $manager = $this->legacyEnvironment->getManager($draft['type']);
            $item = $manager->getItem($draft['item_id']);

            if ($item) {
                $item->delete();
            }
        }
    }

    public function getSummary(): string
    {
        return 'Delete drafts';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}