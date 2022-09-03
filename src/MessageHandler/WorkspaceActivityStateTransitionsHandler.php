<?php

namespace App\MessageHandler;

use App\Message\WorkspaceActivityStateTransitions;
use App\Repository\RoomRepository;
use App\Repository\ZzzRoomRepository;
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
     * @var ZzzRoomRepository
     */
    private ZzzRoomRepository $zzzRoomRepository;

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
        ZzzRoomRepository $zzzRoomRepository,
        EntityManagerInterface $entityManager,
        WorkflowInterface $roomActivityStateMachine
    ) {
        $this->roomRepository = $roomRepository;
        $this->zzzRoomRepository = $zzzRoomRepository;

        $this->entityManager = $entityManager;
        $this->roomActivityStateMachine = $roomActivityStateMachine;
    }

    public function __invoke(WorkspaceActivityStateTransitions $message)
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $roomActivityObject = $this->roomRepository->find($id);
            if (!$roomActivityObject) {
                $roomActivityObject = $this->zzzRoomRepository->find($id);
            }

            $transitions = $this->roomActivityStateMachine->getEnabledTransitions($roomActivityObject);
            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->roomActivityStateMachine->can($roomActivityObject, $transitionName)) {
                    $this->roomActivityStateMachine->apply($roomActivityObject, $transitionName);
                    $this->entityManager->persist($roomActivityObject);
                }
            }
        }

        $this->entityManager->flush();
    }
}
