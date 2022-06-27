<?php

namespace App\Cron\Tasks;

use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzRoomRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CronUpdateActivityState implements CronTaskInterface
{
    private const BATCH_SIZE = 5000;

    /**
     * @var AccountsRepository
     */
    private AccountsRepository $accountRepository;

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
    private WorkflowInterface $accountActivityStateMachine;

    /**
     * @var WorkflowInterface
     */
    private WorkflowInterface $roomActivityStateMachine;

    public function __construct(
        AccountsRepository $accountsRepository,
        RoomRepository $roomRepository,
        ZzzRoomRepository $zzzRoomRepository,
        EntityManagerInterface $entityManager,
        WorkflowInterface $accountActivityStateMachine,
        WorkflowInterface $roomActivityStateMachine
    ) {
        $this->accountRepository = $accountsRepository;
        $this->roomRepository = $roomRepository;
        $this->zzzRoomRepository = $zzzRoomRepository;
        $this->entityManager = $entityManager;
        $this->accountActivityStateMachine = $accountActivityStateMachine;
        $this->roomActivityStateMachine = $roomActivityStateMachine;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $accountActivityObjects = $this->accountRepository->findAllExceptRoot();

        $i = 0;
        foreach ($accountActivityObjects as $accountActivityObject) {
            $transitions = $this->accountActivityStateMachine->getEnabledTransitions($accountActivityObject);

            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->accountActivityStateMachine->can($accountActivityObject, $transitionName)) {
                    $this->accountActivityStateMachine->apply($accountActivityObject, $transitionName);
                    $this->entityManager->persist($accountActivityObject);
                }
            }

            $i++;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        $roomActivityObjects = array_merge(
            $this->roomRepository->findAll(),
            $this->zzzRoomRepository->findAll()
        );

        $i = 0;
        foreach ($roomActivityObjects as $roomActivityObject) {
            $transitions = $this->roomActivityStateMachine->getEnabledTransitions($roomActivityObject);

            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->roomActivityStateMachine->can($roomActivityObject, $transitionName)) {
                    $this->roomActivityStateMachine->apply($roomActivityObject, $transitionName);
                    $this->entityManager->persist($roomActivityObject);

                }
            }

            $i++;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
    }

    public function getSummary(): string
    {
        return 'Update activity states';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}