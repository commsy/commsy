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

namespace App\Rubric\Annotation;

use App\Entity\Annotations;
use App\Entity\User;
use App\Rubric\RubricPermissionOverride;
use App\Rubric\RubricType;
use App\Security\Permission\Checker\ExternalViewerChecker;

/**
 * Annotation-specific edit override. The legacy `cs_annotation_item::mayEdit`
 * adds one branch on top of the default `cs_item::mayEdit`: if all the
 * standard checks (root / in-context moderator / creator / public)
 * deny, the actor can still edit when registered as an *external
 * viewer* of the annotation's linked item.
 *
 * Implementation strategy:
 *   - We grant immediately (return `true`) if the external-viewer
 *     allow-list matches — that supersedes the default in any case
 *     where the actor would otherwise be denied.
 *   - Otherwise we return `null`, deferring to the default
 *     {@see \App\Security\Permission\Checker\ItemEditChecker}. This
 *     preserves the legacy `default OR external_viewer` shape.
 */
final readonly class AnnotationPermissionOverride implements RubricPermissionOverride
{
    public function __construct(
        private ExternalViewerChecker $externalViewerChecker,
    ) {
    }

    public function rubricType(): RubricType
    {
        return RubricType::Annotation;
    }

    public function canEdit(User $actor, object $item): ?bool
    {
        if (!$item instanceof Annotations) {
            // The dispatcher only routes here when the rubric matches,
            // so a non-Annotations item indicates a wiring bug — defer
            // to the default rather than asserting at runtime.
            return null;
        }

        $linkedItemId = $item->getLinkedItemId();
        if ($linkedItemId === null || $linkedItemId <= 0) {
            return null;
        }

        if ($this->externalViewerChecker->isViewerOf($linkedItemId, $actor->getUserId())) {
            return true;
        }

        return null;
    }
}
