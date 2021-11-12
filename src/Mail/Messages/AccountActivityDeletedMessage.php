<?php

namespace App\Mail\Messages;

use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountActivityDeletedMessage extends Message
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Portal
     */
    private Portal $portal;

    /**
     * @var Account
     */
    private Account $account;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        Portal $portal,
        Account $account
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->portal = $portal;
        $this->account = $account;
    }

    public function getSubject(): string
    {
        return '%portal_name%: Your account has been deleted';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_activity_deleted.html.twig';
    }

    public function getParameters(): array
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $legacyTranslator->setEmailTextArray($this->portal->getEmailTextArray());

        $contactModerators = $this->portal->getContactModeratorList($this->legacyEnvironment);
        /** @var cs_user_item|false $firstContactModerator */
        $firstContactModerator = $contactModerators->getFirst();

        return [
            'hello' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_HELLO',
                $this->account->getFirstname() . $this->account->getLastname()
            ),
            'content' => $legacyTranslator->getEmailMessage(
                'EMAIL_INACTIVITY_DELETE_NOW_BODY',
                $this->account->getUsername(),
                $this->account->getAuthSource()->getTitle(),
                $this->urlGenerator->generate('app_helper_portalenter', [
                    'context' => $this->portal->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->portal->getTitle(),
            ),
            'ciao' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_CIAO',
                $firstContactModerator !== null ? $firstContactModerator->getFullName() : '',
                $this->portal->getTitle()
            ),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal_name%' => $this->portal->getTitle(),
        ];
    }
}