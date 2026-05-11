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

namespace App\Files;

use App\Entity\Discussionarticles;
use App\Entity\Files;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\ItemLinkFileRepository;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ExternalViewerChecker;
use App\Security\Permission\Checker\ItemEditChecker;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Dispatcher\ItemEditDispatcher;
use App\Security\Permission\Subject\ItemViewSubjectFactory;

/**
 * Doctrine-only port of `cs_file_item`'s permission methods.
 *
 * A file inherits visibility AND editability from the items linked to
 * it — a file has no own permission surface beyond a creator id and a
 * context. The legacy class collapses all three permission methods
 * (`maySee`, `mayEdit`, `mayExternalViewerSeeLinkedItem`) into a loop
 * over `getLinkedItems()`; this checker does the same but:
 *
 *   - `canSee`               → iterates linked items, asks
 *                              {@see ItemViewChecker} per item
 *   - `canEdit`              → runs the file's own moderator/creator
 *                              check, falls back to "user can edit
 *                              ANY linked item" via
 *                              {@see ItemEditDispatcher}
 *   - `canExternalViewerSee` → 1:1 replication of the legacy
 *                              `[0]`-only quirk (deferred-fix tracked
 *                              in the permission-refactor follow-ups
 *                              memory)
 */
final readonly class FilePermissionChecker
{
    public function __construct(
        private ItemLinkFileRepository $linkFileRepository,
        private ItemRepository $itemRepository,
        private ItemViewChecker $itemViewChecker,
        private ItemEditChecker $itemEditChecker,
        private ItemEditDispatcher $itemEditDispatcher,
        private ItemViewSubjectFactory $subjectFactory,
        private ExternalViewerChecker $externalViewerChecker,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Whether the actor may see the file. True iff at least one linked
     * item is visible to the actor and is not soft-overwritten content.
     *
     * Mirrors `cs_file_item::maySee` → `maySeeLinkedItem`.
     */
    public function canSee(User $actor, Files $file, ?Room $currentContext = null): bool
    {
        foreach ($this->loadActiveLinkedItems($file) as $linkedItem) {
            if ($this->hasOverwrittenContent($linkedItem)) {
                continue;
            }
            $subject = $this->subjectFactory->fromItem($linkedItem, $currentContext);
            if ($this->itemViewChecker->canSee($actor, $subject, $currentContext)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether the actor may edit the file. Mirrors
     * `cs_file_item::mayEdit` 1:1: a read-only actor never; root
     * always; in-context moderator always; the file's own creator
     * always; anyone else only if they may edit at least one of the
     * file's linked items.
     */
    public function canEdit(User $actor, Files $file): bool
    {
        if ($actor->isReadOnlyUser()) {
            return false;
        }
        if ($actor->isRoot()) {
            return true;
        }

        $membership = $this->resolveMembershipInContext($actor, $file->getContextId());
        if ($membership === null || !$membership->isUser()) {
            return false;
        }

        if ($membership->isModerator()) {
            return true;
        }

        // File creator can always edit their own file. Files entity
        // exposes the int FK directly (no EntityUsersTrait — no
        // modifier_id column on the files table).
        $fileCreatorId = $file->getCreatorId();
        if ($fileCreatorId !== null && $fileCreatorId === $membership->getItemId()) {
            return true;
        }

        // Fan out across linked items — any editable linked item makes
        // the file editable. The dispatcher applies the right per-rubric
        // edit semantics for each linked item.
        foreach ($this->loadActiveLinkedItems($file) as $linkedItem) {
            if ($this->itemEditDispatcher->canEdit($actor, $linkedItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * External-viewer fallback. Replicates the legacy first-link-only
     * quirk verbatim — see the deferred-fix entry in the permission-
     * refactor follow-ups memory.
     */
    public function canExternalViewerSee(int $fileId, string $username): bool
    {
        $itemIds = $this->linkFileRepository->findLinkedItemIds($fileId);
        if ($itemIds === []) {
            return false;
        }
        return $this->externalViewerChecker->isViewerOf($itemIds[0], $username);
    }

    /**
     * @return iterable<object> active linked items, lazily loaded via
     *                          ItemRepository::find() (note: the rubric
     *                          subclasses don't actually `extends Items`
     *                          in PHP — duck-typing applies)
     */
    private function loadActiveLinkedItems(Files $file): iterable
    {
        foreach ($this->linkFileRepository->findLinkedItemIds($file->getFilesId()) as $itemId) {
            $item = $this->itemRepository->find($itemId);
            if ($item !== null) {
                yield $item;
            }
        }
    }

    /**
     * The only legacy `getHasOverwrittenContent()` override lives on
     * `cs_discussionarticle_item`: `public = -2` means "body replaced
     * with placeholder text after deletion to keep the discussion
     * hierarchy intact". Everything else returns false.
     */
    private function hasOverwrittenContent(object $item): bool
    {
        return $item instanceof Discussionarticles
            && (string) $item->getPublic() === '-2';
    }

    private function resolveMembershipInContext(User $actor, int $contextId): ?User
    {
        if ($actor->getContextId() === $contextId) {
            return $actor;
        }
        if ($actor->getAccount() === null) {
            return null;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $contextId);
    }
}
