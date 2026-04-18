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
use App\User\UserMembershipDeleter;
use cs_environment;
use cs_item;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Generic delete strategy used by all rubric controllers as the default.
 *
 * Dispatches deletion to the matching {@see RubricDeleter} (identified by
 * `rubricType()->value` == `cs_item::getItemType()`) so that UI deletions go
 * through exactly the same code path as the user-footprint erasure flow
 * ({@see \App\Rubric\UserContentDeleter}).
 *
 * `cs_user_item` (room-membership rows) is routed through the dedicated
 * {@see UserMembershipDeleter} — it is not a rubric and has no
 * `RubricDeleter`, but the same soft-delete + cascade contract applies.
 * Everything else is a bug: every type reaching this strategy is either
 * a rubric (covered by a {@see RubricDeleter}) or a room membership
 * (covered by {@see UserMembershipDeleter}). Labels / topics / groups are
 * already covered by {@see \App\Rubric\Label\LabelDeleter}, and Material —
 * despite being versioned with its own `deleteAllVersions()` fast path in
 * Legacy — is no longer special-cased here: {@see \App\Rubric\Material\MaterialDeleter::deleteItem()}
 * already implements the CS_ALL semantic (wipes every version plus every
 * section version), so the generic dispatch below covers it. Anything
 * unexpected raises a {@see LogicException} so we surface the gap in a
 * test rather than silently fall back to the legacy `cs_item::delete()`
 * cascade (which is on the chopping block).
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
        private readonly UserMembershipDeleter $userMembershipDeleter,
        #[AutowireIterator('app.rubric.deleter')]
        private readonly iterable $rubricDeleters,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $rubricType = RubricType::tryFromLegacyString($item->getItemType());

        if ($rubricType !== null && ($deleter = $this->findDeleter($rubricType)) !== null) {
            $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()->getItemID();
            $deleter->deleteItem($item->getItemId(), $deleterId);
        } elseif ($item->getItemType() === CS_USER_TYPE) {
            $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()->getItemID();
            $this->userMembershipDeleter->softDeleteMembership($item->getItemId(), $deleterId);
        } else {
            throw new LogicException(sprintf(
                'DeleteGeneric has no deletion strategy for item type "%s" (id %d). '
                . 'Every rubric must be covered by a RubricDeleter; cs_user_item is routed through UserMembershipDeleter. '
                . 'If you hit this, register a deleter for the new type instead of re-introducing the legacy cs_item::delete() fallback.',
                $item->getItemType(),
                $item->getItemId()
            ));
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
