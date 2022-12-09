<?php

namespace App\MessageHandler;

use App\Entity\Room;
use App\Message\WorkspaceActivityStateTransitions;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkspaceActivityStateTransitionsHandler implements MessageHandlerInterface
{
    /**
     * @var RoomRepository
     */
    private RoomRepository $roomRepository;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var WorkflowInterface
     */
    private WorkflowInterface $roomActivityStateMachine;

    public function __construct(
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        WorkflowInterface $roomActivityStateMachine
    ) {
        $this->roomRepository = $roomRepository;

        $this->entityManager = $entityManager;
        $this->roomActivityStateMachine = $roomActivityStateMachine;
    }

    public function __invoke(WorkspaceActivityStateTransitions $message)
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $roomActivityObject = $this->roomRepository->find($id);

            $transitions = $this->roomActivityStateMachine->getEnabledTransitions($roomActivityObject);
            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->roomActivityStateMachine->can($roomActivityObject, $transitionName)) {
                    $this->roomActivityStateMachine->apply($roomActivityObject, $transitionName);

                    if ($roomActivityObject->getActivityState() !== Room::ACTIVITY_ABANDONED) {
                        $this->entityManager->persist($roomActivityObject);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
