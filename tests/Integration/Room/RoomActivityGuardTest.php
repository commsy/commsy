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

namespace Tests\Integration\Room;

use App\Entity\Portal;
use App\Entity\Room;
use App\Message\WorkspaceActivityStateTransitions;
use App\MessageHandler\WorkspaceActivityStateTransitionsHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;
use Tests\Factory\AccountFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;

/**
 * Same contract as on the account side: the guard of the room activity
 * workflow decides, it does not write.
 *
 * A community room that still carries project rooms is exempt from
 * deprovisioning. That rollback used to happen inside the guard, which is
 * evaluated several times per transition and also when nothing is applied —
 * so merely asking the workflow what was possible changed the room, and
 * whether it reached the database depended on whoever flushed next.
 */
final class RoomActivityGuardTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    public function testAskingTheWorkflowLeavesTheRoomAlone(): void
    {
        $room = $this->createCommunityRoomWithProject(Room::ACTIVITY_ACTIVE_NOTIFIED);
        $stateUpdated = $room->getActivityStateUpdated()->format('Y-m-d H:i:s');

        $workflow = self::getContainer()->get(Registry::class)->get($room, 'room_activity');
        self::assertSame([], $workflow->getEnabledTransitions($room), 'the protection must block');

        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->reload($room);
        self::assertSame(Room::ACTIVITY_ACTIVE_NOTIFIED, $reloaded->getActivityState());
        self::assertSame(
            $stateUpdated,
            $reloaded->getActivityStateUpdated()?->format('Y-m-d H:i:s'),
            'the guard must not have touched the deadline',
        );
    }

    public function testTheHandlerRollsAProtectedRoomBack(): void
    {
        $room = $this->createCommunityRoomWithProject(Room::ACTIVITY_ACTIVE_NOTIFIED);

        $this->handle($room);

        $reloaded = $this->reload($room);
        self::assertSame(Room::ACTIVITY_ACTIVE, $reloaded->getActivityState());
        self::assertNull($reloaded->getActivityStateUpdated());
    }

    public function testTheHandlerLeavesAnAbandonedRoomAlone(): void
    {
        $room = $this->createCommunityRoomWithProject(Room::ACTIVITY_ABANDONED);

        $this->handle($room);

        self::assertSame(
            Room::ACTIVITY_ABANDONED,
            $this->reload($room)->getActivityState(),
            'abandoned is terminal — its deletion is already on its way',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    private function createCommunityRoomWithProject(string $state): Room
    {
        $portal = PortalFactory::createOne(['clearInactiveRoomsFeatureEnabled' => true]);

        $community = RoomFactory::new()->community()->create([
            'portal' => $portal,
            'contextId' => $portal->getId(),
            'activityState' => $state,
            'activityStateUpdated' => new DateTime('-400 days'),
        ]);
        $project = RoomFactory::new()->project()->create([
            'portal' => $portal,
            'contextId' => $portal->getId(),
        ]);

        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
        ]);
        $creator = RoomUserFactory::new()->asModerator()->create([
            'account' => $account,
            'room' => $community,
        ]);
        LinkItemFactory::createOne([
            'room' => $community,
            'creator' => $creator,
            'firstItemId' => $community->getItemId(),
            'secondItemId' => $project->getItemId(),
        ]);

        return $community;
    }

    private function handle(Room $room): void
    {
        $handler = self::getContainer()->get(WorkspaceActivityStateTransitionsHandler::class);
        $handler(new WorkspaceActivityStateTransitions([$room->getItemId()]));

        $this->entityManager->clear();
    }

    private function reload(Room $room): Room
    {
        return $this->entityManager->getRepository(Room::class)->find($room->getItemId());
    }
}
