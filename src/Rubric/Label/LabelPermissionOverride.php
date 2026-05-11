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

declare(strict_types=1);

namespace App\Rubric\Label;

use App\Entity\Labels;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Rubric\RubricPermissionOverride;
use App\Rubric\RubricType;

/**
 * Label-domain edit override. Handles the two legacy edge cases the
 * `cs_label_item` family layers on top of `cs_item::mayEdit`:
 *
 *   - System labels (`SYSTEM_LABEL` extra = 1, used for `ALL` and
 *     `GROUP_ALL_DESC` seeds) → read-only for everyone, including
 *     moderators and root. The legacy `cs_label_item::mayEdit` denies
 *     before delegating to the parent body.
 *
 *   - Group subtype (`labels.type = 'group'`, modeled by the legacy
 *     `cs_group_item extends cs_label_item`) → in addition to the
 *     default checks, only the moderator or the creator may edit.
 *     The default `cs_item::mayEdit` treats `public = 1` as
 *     "any member can edit"; the group subtype intentionally denies
 *     that path (see #391 in the legacy comment).
 *
 * Rubric routing: the dispatcher maps `items.type = 'label'` to
 * {@see RubricType::Label}. The `labels.type` subtype (`group`,
 * `topic`, `buzzword`, …) is read off the Doctrine `Labels` entity
 * here, because there is no second-level Rubric enum for that.
 */
final readonly class LabelPermissionOverride implements RubricPermissionOverride
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function rubricType(): RubricType
    {
        return RubricType::Label;
    }

    public function canEdit(User $actor, object $item): ?bool
    {
        if (!$item instanceof Labels) {
            return null;
        }

        if ($this->isSystemLabel($item)) {
            return false;
        }

        if ($item->getType() === 'group') {
            return $this->verdictForGroupSubtype($actor, $item);
        }

        return null;
    }

    private function isSystemLabel(Labels $item): bool
    {
        $extras = $item->getExtras();
        if (!is_array($extras)) {
            return false;
        }
        return isset($extras['SYSTEM_LABEL']) && (int) $extras['SYSTEM_LABEL'] === 1;
    }

    /**
     * Tighten the default: only moderator OR creator may edit a group
     * label. If neither matches, deny outright. If one matches, defer
     * to the default checker so the lock-check still applies.
     */
    private function verdictForGroupSubtype(User $actor, Labels $item): ?bool
    {
        $membership = $this->resolveMembershipInContext($actor, $item->getContextId());
        if ($membership === null) {
            return false;
        }

        if ($membership->isModerator()) {
            return null; // defer to default — moderator may edit subject to lock check
        }

        $creatorId = $item->getCreator()?->getItemId();
        if ($creatorId !== null && $creatorId === $membership->getItemId()) {
            return null; // defer — creator may edit subject to lock check
        }

        return false; // not moderator, not creator → deny
    }

    private function resolveMembershipInContext(User $actor, ?int $contextId): ?User
    {
        if ($contextId === null) {
            return null;
        }
        if ($actor->getContextId() === $contextId) {
            return $actor;
        }
        if ($actor->getAccount() === null) {
            return null;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $contextId);
    }
}
