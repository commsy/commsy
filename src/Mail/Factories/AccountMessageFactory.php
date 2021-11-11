<?php

namespace App\Mail\Factories;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Mail\MessageInterface;
use App\Mail\Messages\AccountActivityDeletedMessage;
use App\Mail\Messages\AccountActivityDeleteWarningMessage;
use App\Mail\Messages\AccountActivityLockedMessage;
use App\Mail\Messages\AccountActivityLockWarningMessage;
use App\Services\LegacyEnvironment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountMessageFactory
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var LegacyEnvironment
     */
    private LegacyEnvironment $legacyEnvironment;

    /**
     * @var AccountManager
     */
    private AccountManager $accountManager;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        AccountManager $accountManager
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->accountManager = $accountManager;
    }

    public function createAccountActivityLockWarningMessage(Account $account): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($account);
        if ($portal) {
            return new AccountActivityLockWarningMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $account);
        }

        return null;
    }

    public function createAccountActivityLockedMessage(Account $account): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($account);
        if ($portal) {
            return new AccountActivityLockedMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $account);
        }

        return null;
    }

    public function createAccountActivityDeleteWarningMessage(Account $account): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($account);
        if ($portal) {
            return new AccountActivityDeleteWarningMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $account);
        }

        return null;
    }

    public function createAccountActivityDeletedMessage(Account $account): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($account);
        if ($portal) {
            return new AccountActivityDeletedMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $account);
        }

        return null;
    }
}