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

namespace App\Security\Permission\Resolver;

use App\Entity\Annotations;
use App\Entity\Announcement;
use App\Entity\Assessments;
use App\Entity\Dates;
use App\Entity\Discussionarticles;
use App\Entity\Discussions;
use App\Entity\Files;
use App\Entity\Labels;
use App\Entity\LinkItems;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Section;
use App\Entity\Step;
use App\Entity\Tag;
use App\Entity\Tasks;
use App\Entity\Todos;
use App\Entity\User;
use App\Files\FilePermissionChecker;
use App\Repository\FilesRepository;
use App\Room\RoomEditChecker;
use App\Room\RoomViewChecker;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Dispatcher\ItemEditDispatcher;
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Security\Permission\Subject\ItemViewSubjectFactory;
use App\User\UserEditChecker;
use App\User\UserViewChecker;
use cs_file_item;
use cs_item;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Single entry point that maps a legacy `cs_item` to the matching
 * Doctrine-side checker for the SEE / EDIT verbs.
 *
 * Voters and other "I have a legacy item, can the actor do X to it?"
 * call sites consume this rather than calling `$item->maySee()` /
 * `$item->mayEdit()` directly. The legacy wrappers underneath have
 * the same dispatch logic — once those wrappers go away in Phase 5
 * (and the last `cs_item` typed argument here gets replaced with a
 * Doctrine entity + type tuple), this resolver is the API that
 * survives.
 *
 * Each method returns false when conversion fails (e.g. the
 * corresponding Doctrine row doesn't exist) — callers don't need to
 * handle null entities themselves.
 */
final readonly class PermissionResolver
{
    public function __construct(
        private LegacyPermissionBridge $legacyBridge,
        private EntityManagerInterface $entityManager,
        private FilesRepository $filesRepository,
        private ItemViewSubjectFactory $subjectFactory,
        private ItemViewChecker $itemViewChecker,
        private ItemEditDispatcher $itemEditDispatcher,
        private UserViewChecker $userViewChecker,
        private UserEditChecker $userEditChecker,
        private RoomViewChecker $roomViewChecker,
        private RoomEditChecker $roomEditChecker,
        private FilePermissionChecker $filePermissionChecker,
    ) {
    }

    /**
     * Maps a legacy `cs_item::getType()` (the `items.type` discriminator
     * column value, NOT `getItemType()` which returns the in-memory
     * subtype like `'topic'` / `'group'`) to the concrete Doctrine
     * subclass FQCN.
     *
     * Background: the rubric subclasses (Materials, Discussions, …) are
     * registered in `Items`'s {@see \Doctrine\ORM\Mapping\DiscriminatorMap}
     * but do NOT extend `Items` in PHP — see the comment on
     * {@see ItemViewSubjectFactory::fromItem()}. As a consequence,
     * `$em->getRepository(Items::class)->find($id)` returns the right
     * subclass instance scalar-wise, but DOES NOT hydrate the
     * subclass-specific ManyToOne associations declared via
     * {@see \App\Utils\EntityUsersTrait} (`creator`, `modifier`, `deleter`).
     * That kills creator-based SEE / EDIT branches (own-deactivated entry,
     * private editing creator bypass).
     *
     * Looking up via the concrete repository works correctly. So the
     * resolver routes through this map before calling `$em->find($fqcn, $id)`.
     */
    private const TYPE_TO_CLASS = [
        'material'     => Materials::class,
        'discussion'   => Discussions::class,
        'discarticle'  => Discussionarticles::class,
        'date'         => Dates::class,
        'announcement' => Announcement::class,
        'todo'         => Todos::class,
        'annotation'   => Annotations::class,
        'label'        => Labels::class,
        'task'         => Tasks::class,
        'section'      => Section::class,
        'step'         => Step::class,
        'link_item'    => LinkItems::class,
        'assessments'  => Assessments::class,
        'tag'          => Tag::class,
    ];

    /**
     * @param cs_item             $item        the legacy item being inspected
     * @param cs_user_item        $actorLegacy the viewer (legacy currentUserItem)
     * @param Room|null           $currentRoom the browsing context (null for portal-level)
     */
    public function canSee(cs_item $item, cs_user_item $actorLegacy, ?Room $currentRoom = null): bool
    {
        $actor = $this->legacyBridge->userFromLegacy($actorLegacy);
        if ($actor === null) {
            return false;
        }

        if ($item instanceof cs_user_item) {
            $target = $this->legacyBridge->userFromLegacy($item);
            return $target !== null && $this->userViewChecker->canSee($actor, $target, $currentRoom);
        }

        if ($item instanceof cs_room_item) {
            $room = $this->legacyBridge->roomFromLegacy($item);
            return $room !== null && $this->roomViewChecker->canSee($actor, $room, $currentRoom);
        }

        if ($item instanceof cs_file_item) {
            $file = $this->loadFile($item->getFileID());
            return $file !== null && $this->filePermissionChecker->canSee($actor, $file, $currentRoom);
        }

        return $this->itemViewCheckerFromLegacy($actor, $item, $currentRoom);
    }

    /**
     * @param cs_item             $item        the legacy item being inspected
     * @param cs_user_item        $actorLegacy the viewer (legacy currentUserItem)
     * @param Room|null           $currentRoom the browsing context (null for portal-level)
     */
    public function canEdit(cs_item $item, cs_user_item $actorLegacy, ?Room $currentRoom = null): bool
    {
        $actor = $this->legacyBridge->userFromLegacy($actorLegacy);
        if ($actor === null) {
            return false;
        }

        if ($item instanceof cs_user_item) {
            $target = $this->legacyBridge->userFromLegacy($item);
            return $target !== null && $this->userEditChecker->canEdit($actor, $target);
        }

        if ($item instanceof cs_room_item) {
            $room = $this->legacyBridge->roomFromLegacy($item);
            return $room !== null && $this->roomEditChecker->canEdit($actor, $room, $currentRoom);
        }

        if ($item instanceof cs_file_item) {
            $file = $this->loadFile($item->getFileID());
            return $file !== null && $this->filePermissionChecker->canEdit($actor, $file);
        }

        $entity = $this->loadEntityFor($item);
        return $entity !== null && $this->itemEditDispatcher->canEdit($actor, $entity);
    }

    /**
     * Loads the concrete Doctrine subclass entity for a generic-rubric
     * `cs_item`. Returns null if the type is not a managed item-table
     * subtype, or the row doesn't exist.
     */
    private function loadEntityFor(cs_item $item): ?object
    {
        $fqcn = self::TYPE_TO_CLASS[$item->getType()] ?? null;
        if ($fqcn === null) {
            return null;
        }
        return $this->entityManager->find($fqcn, $item->getItemID());
    }

    private function loadFile(int $filesId): ?Files
    {
        return $this->filesRepository->find($filesId);
    }

    /**
     * Generic-rubric SEE fallback. Loads the polymorphic Items
     * subclass, builds an ItemViewSubject, asks the checker.
     */
    private function itemViewCheckerFromLegacy(User $actor, cs_item $item, ?Room $currentRoom): bool
    {
        $entity = $this->loadEntityFor($item);
        if ($entity === null) {
            return false;
        }
        $subject = $this->subjectFactory->fromItem($entity, $currentRoom);
        return $this->itemViewChecker->canSee($actor, $subject, $currentRoom);
    }
}
