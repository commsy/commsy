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

use App\Account\AccountManager;
use App\Account\LastModeratorChecker;
use App\Entity\Account;
use App\Message\AccountActivityStateTransitions;
use App\Repository\AccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

#[AsMessageHandler]
class AccountActivityStateTransitionsHandler
{
    /**
     * States a started deprovisioning is rolled back from. `abandoned` is
     * terminal — its deletion is already on its way and must not be undone.
     */
    private const array ROLLED_BACK_STATES = [
        Account::ACTIVITY_ACTIVE_NOTIFIED,
        Account::ACTIVITY_IDLE,
        Account::ACTIVITY_IDLE_NOTIFIED,
    ];

    public function __construct(
        private readonly AccountsRepository $accountRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowInterface $accountActivityStateMachine,
        private readonly LastModeratorChecker $lastModeratorChecker,
        private readonly AccountManager $accountManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(AccountActivityStateTransitions $message): void
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $accountActivityObject = $this->accountRepository->find($id);
            if (!$accountActivityObject) {
                continue;
            }

            // The last moderator of a room is exempt from deprovisioning. The
            // workflow guard blocks the same case, but the write belongs here,
            // where the flush is: a guard is evaluated several times per
            // transition and also when nothing is applied.
            if ($this->lastModeratorChecker->isLastModerator($accountActivityObject)) {
                if (in_array($accountActivityObject->getActivityState(), self::ROLLED_BACK_STATES, true)) {
                    $this->accountManager->resetInactivity($accountActivityObject, false, true, false);
                }

                continue;
            }

            // One broken account must not stop the rest of the batch: a failure
            // here used to abort the whole message, so the mails already sent
            // in this run went out again on every retry while no state was
            // ever stored.
            try {
                $this->applyTransitions($accountActivityObject);
            } catch (Throwable $exception) {
                $this->logger->error('Activity state transition failed for account {id}: {message}', [
                    'id' => $id,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }

        $this->entityManager->flush();
    }

    private function applyTransitions(Account $account): void
    {
        foreach ($this->accountActivityStateMachine->getEnabledTransitions($account) as $transition) {
            $transitionName = $transition->getName();

            if ($this->accountActivityStateMachine->can($account, $transitionName)) {
                $this->accountActivityStateMachine->apply($account, $transitionName);
                $this->entityManager->persist($account);
            }
        }
    }
}
