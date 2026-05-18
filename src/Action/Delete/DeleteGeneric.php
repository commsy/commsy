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
use App\Services\CurrentUserResolver;
use App\Services\MarkedService;
use App\User\UserMembershipDeleter;
use cs_item;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Generic delete strategy used by rubric controllers.
 *
 * Dispatches to the matching {@see RubricDeleter} (by
 * `rubricType()->value` == `cs_item::getItemType()`) so UI deletions share
 * the code path of {@see \App\Rubric\UserContentDeleter}. `cs_user_item`
 * is routed through {@see UserMembershipDeleter}. Anything else raises a
 * {@see LogicException} to surface missing deleters rather than fall back
 * to legacy.
 */
class DeleteGeneric implements DeleteInterface
{
    /** @var array<string, RubricDeleter>|null Lazy index by rubricType()->value. */
    private ?array $deleterMap = null;

    /**
     * @param iterable<RubricDeleter> $rubricDeleters
     */
    public function __construct(
        private readonly CurrentUserResolver $currentUserResolver,
        protected MarkedService $markedService,
        private readonly UserMembershipDeleter $userMembershipDeleter,
        #[AutowireIterator('app.rubric.deleter')]
        private readonly iterable $rubricDeleters,
    ) {
    }

    public function delete(cs_item $item): void
    {
        $rubricType = RubricType::tryFromLegacyString($item->getItemType());

        if ($rubricType !== null && ($deleter = $this->findDeleter($rubricType)) !== null) {
            $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
            $deleter->softDeleteItem($item->getItemId(), $deleterId);
        } elseif ($item->getItemType() === CS_USER_TYPE) {
            $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
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
