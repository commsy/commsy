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

namespace App\Security\Permission\Dispatcher;

use App\Entity\Discussionarticles;
use App\Entity\Section;
use App\Entity\Step;
use App\Entity\User;
use App\Item\TypedEntityResolver;
use App\Rubric\RubricPermissionOverride;
use App\Rubric\RubricType;
use App\Security\Permission\Checker\ItemEditChecker;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Orchestrates "can $actor edit $item?" across all item types.
 *
 * Routing order (mirrors `cs_item::mayEdit` body):
 *  1. Subtype-specific {@see RubricPermissionOverride} (tagged
 *     `app.rubric.permission_override`) — if registered AND its
 *     `canEdit()` returns a non-null verdict, that wins.
 *  2. Sub-entry / type-specific gates that DON'T need their own class
 *     (one-line guards):
 *       - Discussionarticles with overwritten content (`public = -2`)
 *         → false. Mirrors `cs_discussionarticle_item::mayEdit`.
 *       - Section / Step → delegate to the linked parent item
 *         (recursion through the dispatcher; the legacy switch did
 *         the same).
 *  3. Default fallback → {@see ItemEditChecker::canEdit()} (the full
 *     mayEdit body: root, in-context moderator, creator, public=1, lock).
 *
 * File items are NOT handled here; the File path lives in
 * {@see \App\Files\FilePermissionChecker} which itself calls back into
 * this dispatcher for each linked item.
 */
final readonly class ItemEditDispatcher
{
    /** @var array<string, RubricPermissionOverride> */
    private array $overridesByRubric;

    /** @param iterable<RubricPermissionOverride> $overrides */
    public function __construct(
        private ItemEditChecker $defaultChecker,
        private TypedEntityResolver $typedEntityResolver,
        #[AutowireIterator('app.rubric.permission_override')]
        iterable $overrides = [],
    ) {
        $map = [];
        foreach ($overrides as $override) {
            $map[$override->rubricType()->value] = $override;
        }
        $this->overridesByRubric = $map;
    }

    public function canEdit(User $actor, object $item): bool
    {
        $type = $this->detectType($item);

        // 1. Per-rubric override.
        $rubric = RubricType::tryFromLegacyString($type);
        if ($rubric !== null && isset($this->overridesByRubric[$rubric->value])) {
            $verdict = $this->overridesByRubric[$rubric->value]->canEdit($actor, $item);
            if ($verdict !== null) {
                return $verdict;
            }
        }

        // 2a. Discussionarticles with overwritten content (`public = -2`)
        //     can never be edited — keeps the discussion hierarchy intact
        //     while the body is a placeholder. Mirrors
        //     `cs_discussionarticle_item::mayEdit`.
        if ($item instanceof Discussionarticles && $item->hasOverwrittenContent()) {
            return false;
        }

        // 2b. Section / Step → linked parent item.
        if ($item instanceof Section || $item instanceof Step) {
            $parent = $this->resolveParentForSubEntry($item);
            if ($parent === null) {
                return false;
            }
            return $this->canEdit($actor, $parent);
        }

        // 3. Default base.
        return $this->defaultChecker->canEdit($actor, $item);
    }

    private function detectType(object $item): string
    {
        // Doctrine's discriminator column is `type` on the items table.
        // Subclasses don't expose it — derive from the concrete class.
        return match (true) {
            $item instanceof \App\Entity\Materials       => 'material',
            $item instanceof \App\Entity\Discussions     => 'discussion',
            $item instanceof \App\Entity\Discussionarticles => 'discarticle',
            $item instanceof \App\Entity\Dates           => 'date',
            $item instanceof \App\Entity\Announcement    => 'announcement',
            $item instanceof \App\Entity\Todos           => 'todo',
            $item instanceof \App\Entity\Annotations     => 'annotation',
            $item instanceof \App\Entity\Labels          => 'label',
            $item instanceof \App\Entity\Tasks           => 'task',
            $item instanceof Section                     => 'section',
            $item instanceof Step                        => 'step',
            $item instanceof \App\Entity\LinkItems       => 'link_item',
            $item instanceof \App\Entity\User            => 'user',
            default                                      => 'item',
        };
    }

    private function resolveParentForSubEntry(object $subEntry): ?object
    {
        // Section and Step both carry a parent item id; they were
        // generated by Doctrine from the legacy `section`/`step` tables
        // which still use the `linked_item_id` column.
        if ($subEntry instanceof Section) {
            $parentId = $subEntry->getMaterial()?->getItemId();
        } else {
            /** @var Step $subEntry */
            $parentId = $subEntry->getTodoItemId();
        }
        if ($parentId === null || $parentId <= 0) {
            return null;
        }
        // Must be the rubric entity, not an `items` row: canEdit() recurses on
        // the result and detectType() dispatches on the concrete class.
        return $this->typedEntityResolver->find($parentId);
    }
}
