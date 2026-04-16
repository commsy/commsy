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

namespace App\Action\Delete;

use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_environment;
use cs_item;
use cs_material_item;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Generic delete strategy used by all rubric controllers as the default.
 *
 * Dispatches deletion to the matching {@see RubricDeleter} (identified by
 * `rubricType()->value` == `cs_item::getItemType()`) so that UI deletions go
 * through exactly the same code path as the user-footprint erasure flow
 * ({@see \App\Rubric\UserContentDeleter}).
 *
 * Items without a registered `RubricDeleter` (e.g. topics, groups, users)
 * fall back to the legacy `cs_item::delete()` cascade. Material keeps its
 * multi-version fast path via `deleteAllVersions()` — this will move into
 * {@see \App\Rubric\Material\MaterialDeleter} once that deleter is migrated
 * away from legacy delegation.
 */
class DeleteGeneric implements DeleteInterface
{
    protected cs_environment $legacyEnvironment;

    /** @var array<string, RubricDeleter>|null Lazy index by rubricType()->value. */
    private ?array $deleterMap = null;

    /**
     * @param iterable<RubricDeleter> $rubricDeleters
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        protected MarkedService $markedService,
        #[AutowireIterator('app.rubric.deleter')]
        private readonly iterable $rubricDeleters,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $rubricType = RubricType::tryFromLegacyString($item->getItemType());

        if ($rubricType === RubricType::Material) {
            /** @var cs_material_item $item */
            $item->deleteAllVersions();
        } elseif ($rubricType !== null && ($deleter = $this->findDeleter($rubricType)) !== null) {
            $deleterId = $this->legacyEnvironment->getCurrentUserItem()->getItemID();
            $deleter->deleteItem($item->getItemId(), $deleterId);
        } else {
            $item->delete();
        }

        $this->markedService->removeItemFromClipboard($item->getItemId());
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        return null;
    }

    private function findDeleter(RubricType $type): ?RubricDeleter
    {
        if ($this->deleterMap === null) {
            $this->deleterMap = [];
            foreach ($this->rubricDeleters as $deleter) {
                $this->deleterMap[$deleter->rubricType()->value] = $deleter;
            }
        }

        return $this->deleterMap[$type->value] ?? null;
    }
}
