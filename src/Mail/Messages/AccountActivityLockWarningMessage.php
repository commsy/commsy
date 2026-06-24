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
use App\Mail\MailTextResolver;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountActivityLockWarningMessage extends Message
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        private readonly Portal $portal,
        private readonly Account $account,
        private readonly MailTextResolver $mailTextResolver
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getSubject(): string
    {
        return '{portal_name}: Account will be locked in {num_days} days';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_activity_lock_warning.html.twig';
    }

    public function getParameters(): array
    {
        $overrides = $this->portal->getEmailTextArray();
        // account-level mail: the legacy translator resolved this in portal context (_PO)
        $roomType = 'other';
        $locale = $this->legacyEnvironment->getTranslationObject()->getSelectedLanguage();

        $contactModerators = $this->portal->getContactModeratorList($this->legacyEnvironment);
        /** @var cs_user_item|false $firstContactModerator */
        $firstContactModerator = $contactModerators->getFirst();

        return [
            'hello' => $this->mailTextResolver->resolve(
                'mail.salutation',
                'MAIL_BODY_HELLO',
                $roomType,
                $locale,
                ["{$this->account->getFirstname()} {$this->account->getLastname()}"],
                $overrides
            ),
            'content' => $this->mailTextResolver->resolve(
                'mail.inactivity_lock_next',
                'EMAIL_INACTIVITY_LOCK_NEXT_BODY',
                $roomType,
                $locale,
                [
                    $this->account->getDisplayName(),
                    $this->account->getAuthSource()->getTitle(),
                    $this->portal->getClearInactiveAccountsLockDays(),
                    $this->urlGenerator->generate('app_helper_portalenter', [
                        'context' => $this->portal->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    $this->portal->getTitle(),
                ],
                $overrides
            ),
            'ciao' => $this->mailTextResolver->resolve(
                'mail.goodbye',
                'MAIL_BODY_CIAO',
                $roomType,
                $locale,
                [$firstContactModerator ? $firstContactModerator->getFullName() : '', $this->portal->getTitle()],
                $overrides
            ),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            'portal_name' => $this->portal->getTitle(),
            'num_days' => $this->portal->getClearInactiveAccountsLockDays(),
        ];
    }
}
