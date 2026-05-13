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

namespace Tests\Unit\Security\Permission\Checker;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ExternalViewerChecker;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ItemViewCheckerTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private ExternalViewerChecker&MockObject $externalViewerChecker;
    private ItemViewChecker $checker;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->externalViewerChecker = $this->createMock(ExternalViewerChecker::class);
        $this->checker = new ItemViewChecker(
            $this->userRepository,
            $this->externalViewerChecker,
        );
    }

    // ---- step 1: item context deleted

    public function testDeniesWhenItemContextIsDeleted(): void
    {
        $subject = $this->subject(contextIsDeleted: true);
        $this->userRepository->expects(self::never())->method('findInContext');

        self::assertFalse($this->checker->canSee($this->roomMember(2), $subject));
    }

    public function testDeniesWhenItemContextIdIsNull(): void
    {
        $subject = $this->subject(contextId: null);
        self::assertFalse($this->checker->canSee($this->roomMember(2), $subject));
    }

    // ---- step 1b: tombstone (hasOverwrittenContent)

    public function testDeniesTombstonedItemEvenForRoot(): void
    {
        $root = (new User())->setStatus(3)->setUserId('root');
        $subject = $this->subject(hasOverwrittenContent: true);

        self::assertFalse(
            $this->checker->canSee($root, $subject),
            'Even root cannot see a tombstoned item (body is placeholder)',
        );
    }

    public function testDeniesTombstonedItemForMember(): void
    {
        $member = $this->roomMember(2, contextId: 42);
        $subject = $this->subject(contextId: 42, hasOverwrittenContent: true);

        self::assertFalse($this->checker->canSee($member, $subject));
    }

    // ---- step 2: root short-circuit

    public function testGrantsForRootActor(): void
    {
        $root = (new User())->setStatus(3)->setUserId('root');

        self::assertTrue($this->checker->canSee($root, $this->subject()));
    }

    // ---- step 3: membership in item's context

    public function testGrantsForRegularMemberOfItemContext(): void
    {
        $actor = $this->roomMember(2, contextId: 42);
        $this->userRepository->expects(self::never())->method('findInContext');

        self::assertTrue(
            $this->checker->canSee($actor, $this->subject(contextId: 42)),
            'Actor already in item context — no second lookup needed',
        );
    }

    public function testCrossContextLookupWhenActorLivesElsewhere(): void
    {
        $actor = $this->roomMember(2, contextId: 99); // actor in a different context
        $actor->setAccount(new Account());
        $this->userRepository
            ->expects(self::once())
            ->method('findInContext')
            ->willReturn($this->roomMember(2, contextId: 42));

        self::assertTrue(
            $this->checker->canSee($actor, $this->subject(contextId: 42)),
        );
    }

    public function testRejectsWhenCrossContextLookupReturnsNull(): void
    {
        $actor = $this->roomMember(2, contextId: 99);
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        self::assertFalse($this->checker->canSee($actor, $this->subject(contextId: 42)));
    }

    public function testRequestedMemberCannotSee(): void
    {
        $actor = $this->roomMember(1, contextId: 42); // status=1 → !isUser()
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        self::assertFalse($this->checker->canSee($actor, $this->subject(contextId: 42)));
    }

    public function testReadOnlyMemberCanSeeActivatedItem(): void
    {
        $actor = $this->roomMember(4, contextId: 42); // RO member
        self::assertTrue($this->checker->canSee($actor, $this->subject(contextId: 42)));
    }

    // ---- deactivated entries

    public function testRegularMemberCannotSeeDeactivatedItemAuthoredByOthers(): void
    {
        $actor = $this->roomMember(2, contextId: 42, itemId: 5);
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        self::assertFalse($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42, isDeactivated: true, creatorId: 999),
        ));
    }

    public function testModeratorCanSeeDeactivatedItem(): void
    {
        $actor = $this->roomMember(3, contextId: 42, itemId: 5);

        self::assertTrue($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42, isDeactivated: true, creatorId: 999),
        ));
    }

    public function testCreatorCanSeeOwnDeactivatedItem(): void
    {
        $actor = $this->roomMember(2, contextId: 42, itemId: 5);

        self::assertTrue($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42, isDeactivated: true, creatorId: 5),
        ));
    }

    // ---- step 4: external viewer fallback

    public function testGrantsViaExternalViewerWhenMembershipMissing(): void
    {
        $actor = $this->roomMember(0, contextId: 99, userId: 'external_alice');
        // Different context, no Account → membership lookup skipped.
        $this->externalViewerChecker
            ->expects(self::once())
            ->method('isViewerOf')
            ->with(7, 'external_alice')
            ->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $this->subject(itemId: 7, contextId: 42)));
    }

    // ---- step 5: guest fallback

    public function testGuestCanSeeViaCommunityGuestAccess(): void
    {
        $actor = (new User())->setStatus(0)->setUserId('guest');
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        $currentRoom = (new Room())
            ->setItemId(10)
            ->setType('community')
            ->setStatus('1')
            ->setOpenForGuests(true);

        self::assertTrue($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42),
            $currentRoom,
        ));
    }

    public function testGuestCannotSeeDeactivatedItemEvenInOpenCommunity(): void
    {
        $actor = (new User())->setStatus(0)->setUserId('guest');
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        $currentRoom = (new Room())
            ->setItemId(10)
            ->setType('community')
            ->setStatus('1')
            ->setOpenForGuests(true);

        self::assertFalse($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42, isDeactivated: true),
            $currentRoom,
        ));
    }

    public function testGuestFallbackHonorsCommunityOnlyQuirk(): void
    {
        // Project room with openForGuests=true must NOT trigger the
        // guest fallback — legacy quirk replicated.
        $actor = (new User())->setStatus(0)->setUserId('guest');
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        $currentRoom = (new Room())
            ->setItemId(10)
            ->setType('project')
            ->setStatus('1')
            ->setOpenForGuests(true);

        self::assertFalse($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42),
            $currentRoom,
        ));
    }

    public function testRequestedActorCanSeeViaGuestFallback(): void
    {
        // Legacy considers `isRequested()` (status 1) eligible too.
        $actor = (new User())->setStatus(1)->setUserId('alice');
        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        $currentRoom = (new Room())
            ->setItemId(10)
            ->setType('community')
            ->setStatus('1')
            ->setOpenForGuests(true);

        self::assertTrue($this->checker->canSee(
            $actor,
            $this->subject(contextId: 42),
            $currentRoom,
        ));
    }

    // ---- helpers

    private function subject(
        int $itemId = 7,
        ?int $contextId = 42,
        ?int $creatorId = null,
        bool $isDeactivated = false,
        bool $contextIsDeleted = false,
        bool $hasOverwrittenContent = false,
    ): ItemViewSubject {
        return new ItemViewSubject(
            itemId: $itemId,
            contextId: $contextId,
            creatorId: $creatorId,
            isDeactivated: $isDeactivated,
            contextIsDeleted: $contextIsDeleted,
            hasOverwrittenContent: $hasOverwrittenContent,
        );
    }

    private function roomMember(
        int $status,
        ?int $contextId = null,
        int $itemId = 1,
        string $userId = 'alice',
    ): User {
        $user = (new User())
            ->setStatus($status)
            ->setUserId($userId);
        $user->itemId = $itemId;

        if ($contextId !== null) {
            $room = (new Room())->setItemId($contextId)->setType('project')->setStatus('1')->setOpenForGuests(false);
            $user->setRoom($room);
        }
        return $user;
    }
}
