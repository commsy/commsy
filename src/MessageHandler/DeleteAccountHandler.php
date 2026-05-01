<?php

namespace App\MessageHandler;

use App\Account\AccountDeleter;
use App\Message\DeleteAccountMessage;
use App\Repository\AccountsRepository;
use App\Services\LegacyEnvironment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DeleteAccountHandler
{
    public function __construct(
        private AccountsRepository $accountsRepository,
        private AccountDeleter $accountDeleter,
        private LegacyEnvironment $legacyEnvironment,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(DeleteAccountMessage $message): void
    {
        $account = $this->accountsRepository->find($message->getAccountId());

        if ($account === null) {
            $this->logger->warning(
                'Account {id} not found, may have been deleted already',
                ['id' => $message->getAccountId()]
            );
            return;
        }

        // Set portal context for legacy environment
        $portal = $account->getPortal();
        if ($portal !== null) {
            $this->legacyEnvironment->getEnvironment()->setCurrentPortalID($portal->getId());
        }

        $this->accountDeleter->delete($account);
    }
}
