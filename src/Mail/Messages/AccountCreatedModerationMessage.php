<?php

namespace App\Mail\Messages;

use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\Message;
use App\Services\PortalUrlResolver;

class AccountCreatedModerationMessage extends Message
{
    public function __construct(
        private readonly PortalUrlResolver $portalUrlResolver,
        private readonly Account $account,
        private readonly Portal $portal,
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
            'portalUrl' => $this->portalUrlResolver->resolve($this->portal),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            'username' => $this->account->getDisplayName(),
            'portal_name' => $this->portal->getTitle(),
        ];
    }
}
