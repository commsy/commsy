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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkspaceActivityStateTransitionsHandler implements MessageHandlerInterface
{
    public function __construct(private RoomRepository $roomRepository, private EntityManagerInterface $entityManager, private WorkflowInterface $roomActivityStateMachine)
    {
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

                    if (Room::ACTIVITY_ABANDONED !== $roomActivityObject->getActivityState()) {
                        $this->entityManager->persist($roomActivityObject);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
