<?php

namespace App\Cron\Tasks;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\ZzzRoom;
use App\Message\AccountActivityStateTransitions;
use App\Message\WorkspaceActivityStateTransitions;
use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzRoomRepository;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;

class CronUpdateActivityState implements CronTaskInterface
{
    private const BATCH_SIZE = 100;

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
     * @var MessageBusInterface
     */
    private MessageBusInterface $messageBus;

    public function __construct(
        AccountsRepository $accountsRepository,
        RoomRepository $roomRepository,
        ZzzRoomRepository $zzzRoomRepository,
        MessageBusInterface $messageBus
    ) {
        $this->accountRepository = $accountsRepository;
        $this->roomRepository = $roomRepository;
        $this->zzzRoomRepository = $zzzRoomRepository;

        $this->messageBus = $messageBus;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Accounts
        $accountActivityObjects = $this->accountRepository->findAllExceptRoot();
        $ids = [];
        foreach ($accountActivityObjects as $accountActivityObject) {
            /** @var Account $accountActivityObject */
            $ids[] = $accountActivityObject->getId();

            if ((count($ids) % self::BATCH_SIZE) === 0) {
                $this->messageBus->dispatch(new AccountActivityStateTransitions($ids));
                $ids = [];
            }
        }
        $this->messageBus->dispatch(new AccountActivityStateTransitions($ids));

        // Workspaces
        $roomActivityObjects = array_merge($this->roomRepository->findAll(), $this->zzzRoomRepository->findAll());
        $ids = [];
        foreach ($roomActivityObjects as $roomActivityObject) {
            /** @var Room|ZzzRoom $roomActivityObject */
            $ids[] = $roomActivityObject->getItemId();

            if ((count($ids) % self::BATCH_SIZE) === 0) {
                $this->messageBus->dispatch(new WorkspaceActivityStateTransitions($ids));
                $ids = [];
            }
        }

        $this->messageBus->dispatch(new WorkspaceActivityStateTransitions($ids));
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
