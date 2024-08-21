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

use App\Entity\Account;
use App\Message\AccountActivityStateTransitions;
use App\Repository\AccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class AccountActivityStateTransitionsHandler
{
    public function __construct(private readonly AccountsRepository $accountRepository, private readonly EntityManagerInterface $entityManager, private readonly WorkflowInterface $accountActivityStateMachine)
    {
    }

    public function __invoke(AccountActivityStateTransitions $message)
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $accountActivityObject = $this->accountRepository->find($id);
            if (!$accountActivityObject) {
                continue;
            }

            $transitions = $this->accountActivityStateMachine->getEnabledTransitions($accountActivityObject);
            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->accountActivityStateMachine->can($accountActivityObject, $transitionName)) {
                    $this->accountActivityStateMachine->apply($accountActivityObject, $transitionName);
                    if (Account::ACTIVITY_ABANDONED !== $accountActivityObject->getActivityState()) {
                        $this->entityManager->persist($accountActivityObject);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
