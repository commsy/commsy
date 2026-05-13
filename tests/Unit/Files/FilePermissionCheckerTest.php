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

namespace Tests\Unit\Files;

use App\Entity\Account;
use App\Entity\Files;
use App\Entity\Materials;
use App\Entity\User;
use App\Files\FilePermissionChecker;
use App\Repository\ItemLinkFileRepository;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ExternalViewerChecker;
use App\Security\Permission\Checker\ItemEditChecker;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Dispatcher\ItemEditDispatcher;
use App\Security\Permission\Subject\ItemViewSubject;
use App\Security\Permission\Subject\ItemViewSubjectFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class FilePermissionCheckerTest extends TestCase
{
    private ItemLinkFileRepository&MockObject $linkFileRepository;
    private ItemRepository&MockObject $itemRepository;
    private ItemViewChecker&MockObject $itemViewChecker;
    private ItemEditChecker&MockObject $itemEditChecker;
    private ItemEditDispatcher&MockObject $itemEditDispatcher;
    private ItemViewSubjectFactory&MockObject $subjectFactory;
    private ExternalViewerChecker&MockObject $externalViewerChecker;
    private UserRepository&MockObject $userRepository;
    private FilePermissionChecker $checker;

    protected function setUp(): void
    {
        $this->linkFileRepository = $this->createMock(ItemLinkFileRepository::class);
        $this->itemRepository = $this->createMock(ItemRepository::class);
        $this->itemViewChecker = $this->createMock(ItemViewChecker::class);
        $this->itemEditChecker = $this->createMock(ItemEditChecker::class);
        $this->itemEditDispatcher = $this->createMock(ItemEditDispatcher::class);
        $this->subjectFactory = $this->createMock(ItemViewSubjectFactory::class);
        $this->externalViewerChecker = $this->createMock(ExternalViewerChecker::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->checker = new FilePermissionChecker(
            $this->linkFileRepository,
            $this->itemRepository,
            $this->itemViewChecker,
            $this->itemEditChecker,
            $this->itemEditDispatcher,
            $this->subjectFactory,
            $this->externalViewerChecker,
            $this->userRepository,
        );
    }

    // ---- canSee

    public function testCanSeeReturnsFalseWhenNoLinkedItems(): void
    {
        $file = $this->file(filesId: 1);
        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([]);

        self::assertFalse($this->checker->canSee(new User(), $file));
    }

    public function testCanSeeGrantsAsSoonAsAnyLinkedItemIsVisible(): void
    {
        $file = $this->file(filesId: 1);
        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([10, 20]);

        $linked1 = new Materials();
        $linked2 = new Materials();
        $this->itemRepository->method('find')->willReturnMap([[10, $linked1], [20, $linked2]]);

        $subject = $this->subject();
        $this->subjectFactory->method('fromItem')->willReturn($subject);
        $this->itemViewChecker->method('canSee')->willReturnOnConsecutiveCalls(false, true);

        self::assertTrue($this->checker->canSee(new User(), $file));
    }

    /**
     * Tombstone filtering now lives in ItemViewChecker via the
     * `hasOverwrittenContent` flag on ItemViewSubject — FilePermissionChecker
     * no longer carries type-specific knowledge. This test pins that
     * the checker still does NOT short-circuit on its own: it always
     * builds a subject and delegates the verdict to the view checker.
     */
    public function testCanSeeAlwaysDefersOverwrittenContentDecisionToViewChecker(): void
    {
        $file = $this->file(filesId: 1);
        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([10]);
        $this->itemRepository->method('find')->willReturn(new Materials());

        // Factory + checker are mocked, so we only verify that BOTH are
        // consulted in the loop, regardless of any per-item state. The
        // factory builds the subject (with whatever flags it computes),
        // the checker decides — here it denies.
        $this->subjectFactory->expects(self::once())->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->expects(self::once())->method('canSee')->willReturn(false);

        self::assertFalse($this->checker->canSee(new User(), $file));
    }

    // ---- canEdit

    public function testCanEditReturnsFalseForReadOnlyUser(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $actor = (new User())->setStatus(4);  // ReadOnly

        self::assertFalse($this->checker->canEdit($actor, $file));
    }

    public function testCanEditGrantsForRoot(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $actor = (new User())->setStatus(3)->setUserId('root');

        self::assertTrue($this->checker->canEdit($actor, $file));
    }

    public function testCanEditGrantsForFileCreatorInContext(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $file->setCreatorId(99);

        $actor = $this->user(itemId: 99, status: 2, contextId: 42);

        // creator → grant directly, no dispatcher needed
        $this->itemEditDispatcher->expects(self::never())->method('canEdit');

        self::assertTrue($this->checker->canEdit($actor, $file));
    }

    public function testCanEditGrantsForModeratorInContext(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $actor = $this->user(itemId: 5, status: 3, contextId: 42);  // moderator

        self::assertTrue($this->checker->canEdit($actor, $file));
    }

    public function testCanEditFanOutToLinkedItemsWhenActorIsNotCreatorNorModerator(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $file->setCreatorId(99);  // someone else, not the actor
        $actor = $this->user(itemId: 5, status: 2, contextId: 42);

        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([10, 20]);
        $linked1 = new Materials();
        $linked2 = new Materials();
        $this->itemRepository->method('find')->willReturnMap([[10, $linked1], [20, $linked2]]);

        $this->itemEditDispatcher
            ->method('canEdit')
            ->willReturnOnConsecutiveCalls(false, true);

        self::assertTrue($this->checker->canEdit($actor, $file));
    }

    public function testCanEditDeniesWhenActorHasNoMembershipInContext(): void
    {
        $file = $this->file(filesId: 1, contextId: 42);
        $actor = $this->user(itemId: 5, status: 2, contextId: 99);  // wrong context
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        self::assertFalse($this->checker->canEdit($actor, $file));
    }

    // ---- canExternalViewerSee

    public function testCanExternalViewerSeeReturnsFalseWhenNoLinkedItems(): void
    {
        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([]);
        $this->externalViewerChecker->expects(self::never())->method('isViewerOf');

        self::assertFalse($this->checker->canExternalViewerSee(1, 'alice'));
    }

    /**
     * Replicates the legacy `[0]`-only quirk verbatim.
     */
    public function testCanExternalViewerSeeOnlyConsultsFirstLinkedItem(): void
    {
        $this->linkFileRepository->method('findLinkedItemIds')->willReturn([100, 200, 300]);
        $this->externalViewerChecker
            ->expects(self::once())
            ->method('isViewerOf')
            ->with(100, 'alice')
            ->willReturn(true);

        self::assertTrue($this->checker->canExternalViewerSee(1, 'alice'));
    }

    // ---- helpers

    private function file(int $filesId, ?int $contextId = 42): Files
    {
        $f = new Files();
        $idProp = new ReflectionProperty(Files::class, 'filesId');
        $idProp->setValue($f, $filesId);
        if ($contextId !== null) {
            $f->setContextId($contextId);
        }
        return $f;
    }

    private function user(int $itemId, int $status = 2, ?int $contextId = null): User
    {
        $u = (new User())->setStatus($status);
        $u->itemId = $itemId;
        $u->userId = 'user-' . $itemId;  // isRoot() reads userId
        if ($contextId !== null) {
            $u->setRoom((new \App\Entity\Room())->setItemId($contextId));
        }
        return $u;
    }

    private function subject(): ItemViewSubject
    {
        return new ItemViewSubject(
            itemId: 99,
            contextId: 42,
            creatorId: null,
            isDeactivated: false,
            contextIsDeleted: false,
        );
    }
}
