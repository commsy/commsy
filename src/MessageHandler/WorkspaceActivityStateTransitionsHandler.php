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

namespace App\MessageHandler;

use App\Entity\Room;
use App\Message\WorkspaceActivityStateTransitions;
use App\Repository\RoomRepository;
use App\Room\RoomManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

#[AsMessageHandler]
class WorkspaceActivityStateTransitionsHandler
{
    /**
     * States a started deprovisioning is rolled back from. `abandoned` is
     * terminal — its deletion is already on its way and must not be undone.
     */
    private const array ROLLED_BACK_STATES = [
        Room::ACTIVITY_ACTIVE_NOTIFIED,
        Room::ACTIVITY_IDLE,
        Room::ACTIVITY_IDLE_NOTIFIED,
    ];

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowInterface $roomActivityStateMachine,
        private readonly RoomManager $roomManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(WorkspaceActivityStateTransitions $message): void
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $roomActivityObject = $this->roomRepository->find($id);
            if (!$roomActivityObject) {
                continue;
            }

            // A community room that still carries project rooms is exempt from
            // deprovisioning. The workflow guard blocks the same case, but the
            // write belongs here, where the flush is.
            if ($this->stillCarriesProjectRooms($roomActivityObject)) {
                if (in_array($roomActivityObject->getActivityState(), self::ROLLED_BACK_STATES, true)) {
                    $this->roomManager->resetInactivity($roomActivityObject, false, true, false);
                }

                continue;
            }

            // One broken room must not stop the rest of the batch: a failure
            // here used to abort the whole message, so nothing was stored even
            // for the rooms already handled, and messenger retried all of them.
            try {
                $this->applyTransitions($roomActivityObject);
            } catch (Throwable $exception) {
                $this->logger->error('Activity state transition failed for room {id}: {message}', [
                    'id' => $id,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }

        $this->entityManager->flush();
    }

    private function stillCarriesProjectRooms(Room $room): bool
    {
        return $room->isCommunityRoom()
            && $this->roomManager->getLinkedProjectRooms($room)->getCount() > 0;
    }

    private function applyTransitions(Room $room): void
    {
        foreach ($this->roomActivityStateMachine->getEnabledTransitions($room) as $transition) {
            $transitionName = $transition->getName();

            if ($this->roomActivityStateMachine->can($room, $transitionName)) {
                $this->roomActivityStateMachine->apply($room, $transitionName);
                $this->entityManager->persist($room);
            }
        }
    }
}
