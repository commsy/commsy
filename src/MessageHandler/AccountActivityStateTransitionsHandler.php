<?php

namespace App\MessageHandler;

use App\Entity\Account;
use App\Message\AccountActivityStateTransitions;
use App\Repository\AccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class AccountActivityStateTransitionsHandler implements MessageHandlerInterface
{
    /**
     * @var AccountsRepository
     */
    private AccountsRepository $accountRepository;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var WorkflowInterface
     */
    private WorkflowInterface $accountActivityStateMachine;

    public function __construct(
        AccountsRepository $accountsRepository,
        EntityManagerInterface $entityManager,
        WorkflowInterface $accountActivityStateMachine
    ) {
        $this->accountRepository = $accountsRepository;

        $this->entityManager = $entityManager;
        $this->accountActivityStateMachine = $accountActivityStateMachine;
    }

    public function __invoke(AccountActivityStateTransitions $message)
    {
        $ids = $message->getIds();

        foreach ($ids as $id) {
            $accountActivityObject = $this->accountRepository->find($id);

            $transitions = $this->accountActivityStateMachine->getEnabledTransitions($accountActivityObject);
            foreach ($transitions as $transition) {
                $transitionName = $transition->getName();

                if ($this->accountActivityStateMachine->can($accountActivityObject, $transitionName)) {
                    $this->accountActivityStateMachine->apply($accountActivityObject, $transitionName);
                    if ($accountActivityObject->getActivityState() !== Account::ACTIVITY_ABANDONED) {
                        $this->entityManager->persist($accountActivityObject);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
