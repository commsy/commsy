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

namespace App\Cron\Tasks;

use App\Entity\Account;
use App\Entity\Room;
use App\Message\AccountActivityStateTransitions;
use App\Message\WorkspaceActivityStateTransitions;
use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;

class CronUpdateActivityState implements CronTaskInterface
{
    private const BATCH_SIZE = 100;

    public function __construct(private readonly AccountsRepository $accountRepository, private readonly RoomRepository $roomRepository, private readonly MessageBusInterface $messageBus)
    {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Accounts
        $accountActivityObjects = $this->accountRepository->findAllExceptRoot();
        $ids = [];
        foreach ($accountActivityObjects as $accountActivityObject) {
            /* @var Account $accountActivityObject */
            $ids[] = $accountActivityObject->getId();

            if ((count($ids) % self::BATCH_SIZE) === 0) {
                $this->messageBus->dispatch(new AccountActivityStateTransitions($ids));
                $ids = [];
            }
        }
        $this->messageBus->dispatch(new AccountActivityStateTransitions($ids));

        // Workspaces
        // TODO: exclude grouprooms and userrooms
        $roomActivityObjects = $this->roomRepository->findAll();
        $ids = [];
        foreach ($roomActivityObjects as $roomActivityObject) {
            /* @var Room $roomActivityObject */
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
}
