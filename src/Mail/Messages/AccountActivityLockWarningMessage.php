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
use App\Services\LegacyEnvironment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountActivityLockWarningMessage extends Message
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        private Portal $portal,
        private Account $account
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getSubject(): string
    {
        return '%portal_name%: Account will be locked in %num_days% days';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_activity_lock_warning.html.twig';
    }

    public function getParameters(): array
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $legacyTranslator->setEmailTextArray($this->portal->getEmailTextArray());

        $contactModerators = $this->portal->getContactModeratorList($this->legacyEnvironment);
        /** @var \cs_user_item|false $firstContactModerator */
        $firstContactModerator = $contactModerators->getFirst();

        return [
            'hello' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_HELLO',
                $this->account->getFirstname().$this->account->getLastname()
            ),
            'content' => $legacyTranslator->getEmailMessage(
                'EMAIL_INACTIVITY_LOCK_NEXT_BODY',
                $this->account->getUsername(),
                $this->account->getAuthSource()->getTitle(),
                $this->portal->getClearInactiveAccountsLockDays(),
                $this->urlGenerator->generate('app_helper_portalenter', [
                    'context' => $this->portal->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->portal->getTitle(),
            ),
            'ciao' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_CIAO',
                $firstContactModerator ? $firstContactModerator->getFullName() : '',
                $this->portal->getTitle()
            ),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal_name%' => $this->portal->getTitle(),
            '%num_days%' => $this->portal->getClearInactiveAccountsLockDays(),
        ];
    }
}
