<?php

namespace App\Mail\Messages;

use App\Entity\Account;
use App\Mail\Message;

class AccountCreatedModerationMessage extends Message
{
    public function __construct(
        private readonly Account $account
    ) {}

    public function getSubject(): string
    {
        return 'mail.account_registration.subject';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_created_moderation.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'account' => $this->account,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%username%' => $this->account->getUsername(),
        ];
    }
}
