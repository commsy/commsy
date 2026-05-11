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

namespace App\Rubric;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Per-rubric edit-permission override. Plugs into
 * {@see \App\Security\Permission\Dispatcher\ItemEditDispatcher} via
 * the `app.rubric.permission_override` tag (analog to {@see RubricDeleter}).
 *
 * This is NOT the top-level edit-permission API — most callers want
 * {@see \App\Security\Permission\Checker\ItemEditChecker} (default base)
 * or {@see \App\Security\Permission\Dispatcher\ItemEditDispatcher}
 * (full routing). An implementation of this interface is registered
 * only when a specific rubric needs to *override* the default in a way
 * the base can't express:
 *
 *   - Discussion        : "edit ignore close" — overrides isClosed gating
 *   - Discussionarticle : `public = -2` (overwritten content) → no edit
 *   - Annotation        : public + external-viewer interaction
 *   - Label             : system-labels (ALL / GROUP_ALL_DESC) are read-only
 *   - Group             : moderator/creator restriction
 *
 * Returning `null` from {@see canEdit()} means "I have no opinion for
 * this case, fall back to the default" — used by checkers that only
 * intercept some conditions.
 */
#[AutoconfigureTag('app.rubric.permission_override')]
interface RubricPermissionOverride
{
    /**
     * The rubric type this override applies to. Dispatcher uses this to
     * route to the right implementation.
     */
    public function rubricType(): RubricType;

    /**
     * Whether the actor may edit this item, OR `null` to defer to the
     * default {@see \App\Security\Permission\Checker\ItemEditChecker}.
     */
    public function canEdit(User $actor, object $item): ?bool;
}
