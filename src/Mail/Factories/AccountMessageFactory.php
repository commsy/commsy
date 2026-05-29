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

namespace App\Mail\Factories;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\MessageInterface;
use App\Mail\Messages\AccountActivityDeletedMessage;
use App\Mail\Messages\AccountActivityDeleteWarningMessage;
use App\Mail\Messages\AccountActivityLockedMessage;
use App\Mail\Messages\AccountActivityLockWarningMessage;
use App\Mail\Messages\AccountMergeConfirmMessage;
use App\Services\LegacyEnvironment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountMessageFactory
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly AccountManager $accountManager
    ) {
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

    /**
     * Confirmation mail sent to the old account A legitimising its merge into N.
     *
     * @param Account $oldAccount account A (mailbox owner; will be merged in and deleted)
     * @param Account $newAccount surviving account N
     */
    public function createAccountMergeConfirmMessage(
        Account $oldAccount,
        Account $newAccount,
        Portal $portal,
        string $token
    ): MessageInterface {
        return new AccountMergeConfirmMessage($this->urlGenerator, $portal, $oldAccount, $newAccount, $token);
    }
}
