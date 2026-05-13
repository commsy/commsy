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

namespace Tests\Unit\Rubric\Label;

use App\Entity\Account;
use App\Entity\Labels;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Rubric\Label\LabelPermissionOverride;
use App\Rubric\RubricType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class LabelPermissionOverrideTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private LabelPermissionOverride $override;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->override = new LabelPermissionOverride($this->userRepository);
    }

    public function testRubricTypeIsLabel(): void
    {
        self::assertSame(RubricType::Label, $this->override->rubricType());
    }

    // ---- System label

    public function testDeniesSystemLabel(): void
    {
        $label = $this->label(contextId: 42, type: 'buzzword', extras: ['SYSTEM_LABEL' => 1]);
        $actor = $this->user(itemId: 5, status: 3, contextId: 42); // moderator

        self::assertFalse(
            $this->override->canEdit($actor, $label),
            'System labels are read-only even for moderators',
        );
    }

    public function testNonSystemLabelDefersToDefault(): void
    {
        $label = $this->label(contextId: 42, type: 'buzzword', extras: []);
        $actor = $this->user(itemId: 5, status: 3, contextId: 42);

        self::assertNull($this->override->canEdit($actor, $label));
    }

    // ---- Group subtype

    public function testGroupSubtypeDefersForModerator(): void
    {
        $label = $this->label(contextId: 42, type: 'group');
        $actor = $this->user(itemId: 5, status: 3, contextId: 42);

        self::assertNull(
            $this->override->canEdit($actor, $label),
            'Moderator on a group: defer to default for lock check',
        );
    }

    public function testGroupSubtypeDefersForCreator(): void
    {
        $creator = $this->user(itemId: 99, status: 2);
        $label = $this->label(contextId: 42, type: 'group');
        $label->setCreator($creator);

        $actor = $this->user(itemId: 99, status: 2, contextId: 42);

        self::assertNull(
            $this->override->canEdit($actor, $label),
            'Creator: defer to default for lock check',
        );
    }

    public function testGroupSubtypeDeniesNonModeratorNonCreator(): void
    {
        $creator = $this->user(itemId: 99, status: 2);
        $label = $this->label(contextId: 42, type: 'group');
        $label->setCreator($creator);

        $actor = $this->user(itemId: 5, status: 2, contextId: 42); // member, not creator

        self::assertFalse(
            $this->override->canEdit($actor, $label),
            'Regular member must NOT edit group labels (#391)',
        );
    }

    public function testGroupSubtypeDeniesWhenActorHasNoMembershipInContext(): void
    {
        $label = $this->label(contextId: 42, type: 'group');
        $actor = $this->user(itemId: 5, status: 2, contextId: 99); // wrong context
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        self::assertFalse($this->override->canEdit($actor, $label));
    }

    public function testGroupSubtypeUsesCrossContextLookupWhenNeeded(): void
    {
        $label = $this->label(contextId: 42, type: 'group');
        $portalActor = $this->user(itemId: 5, status: 2, contextId: 7); // portal-level
        $portalActor->setAccount(new Account());

        $roomMembership = $this->user(itemId: 105, status: 3, contextId: 42);
        $this->userRepository
            ->expects(self::once())
            ->method('findInContext')
            ->willReturn($roomMembership);

        // Resolved to a moderator → defer
        self::assertNull($this->override->canEdit($portalActor, $label));
    }

    // ---- Misc

    public function testDefersForNonLabelItem(): void
    {
        $actor = $this->user(itemId: 5);
        self::assertNull($this->override->canEdit($actor, new Materials()));
    }

    private function user(int $itemId, int $status = 2, ?int $contextId = null): User
    {
        $u = (new User())->setStatus($status);
        $u->itemId = $itemId;
        $u->userId = 'user-' . $itemId;
        if ($contextId !== null) {
            $u->setRoom((new Room())->setItemId($contextId));
        }
        return $u;
    }

    private function label(int $contextId, string $type, array $extras = []): Labels
    {
        $l = new Labels();
        $l->setContextId($contextId);
        $l->setType($type);
        $l->setName('label-' . $type);
        if ($extras !== []) {
            $l->setExtras($extras);
        }
        return $l;
    }
}
