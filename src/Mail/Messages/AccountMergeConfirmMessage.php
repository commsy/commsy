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

namespace App\Mail\Messages;

use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Mailed to the "old" account A whose mailbox legitimises merging A into the
 * current account N. Clicking the contained link confirms the (destructive)
 * merge — A's content is moved to N and A is deleted.
 */
class AccountMergeConfirmMessage extends Message
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Portal $portal,
        private readonly Account $oldAccount,
        private readonly Account $newAccount,
        private readonly string $token
    ) {
    }

    public function getSubject(): string
    {
        return 'mail.account_merge_subject';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_merge_confirm.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'portal' => $this->portal,
            'oldUsername' => $this->oldAccount->getUsername(),
            'newUsername' => $this->newAccount->getUsername(),
            'confirmUrl' => $this->urlGenerator->generate('app_account_mergeaccountsconfirm', [
                'portalId' => $this->portal->getId(),
                'token' => $this->token,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            'portal' => $this->portal->getTitle(),
        ];
    }
}
