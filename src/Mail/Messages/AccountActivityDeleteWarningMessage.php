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
use App\Mail\Text\MailTextRenderer;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountActivityDeleteWarningMessage extends Message
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment,
        private readonly Portal $portal,
        private readonly Account $account,
        private readonly MailTextRenderer $mailTextRenderer
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getSubject(): string
    {
        return '{portal_name}: Account will be deleted in {num_days} days';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_activity_delete_warning.html.twig';
    }

    public function getParameters(): array
    {
        $overrides = $this->portal->getEmailTextArray();
        // account-level mail: the legacy translator resolved this in portal context (_PO)
        $roomType = 'other';

        $contactModerators = $this->portal->getContactModeratorList($this->legacyEnvironment);
        /** @var cs_user_item|false $firstContactModerator */
        $firstContactModerator = $contactModerators->getFirst();

        return [
            'hello' => $this->mailTextRenderer->render(
                'mail.salutation',
                'MAIL_BODY_HELLO',
                $roomType,
                null,
                ["{$this->account->getFirstname()} {$this->account->getLastname()}"],
                $overrides
            ),
            'content' => $this->mailTextRenderer->render(
                'mail.inactivity_delete_next',
                'EMAIL_INACTIVITY_DELETE_NEXT_BODY',
                $roomType,
                null,
                [
                    $this->account->getDisplayName(),
                    $this->account->getAuthSource()->getTitle(),
                    $this->portal->getClearInactiveAccountsDeleteDays(),
                    $this->urlGenerator->generate('app_helper_portalenter', [
                        'context' => $this->portal->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    $this->portal->getTitle(),
                ],
                $overrides
            ),
            'ciao' => $this->mailTextRenderer->render(
                'mail.goodbye',
                'MAIL_BODY_CIAO',
                $roomType,
                null,
                [$firstContactModerator ? $firstContactModerator->getFullName() : '', $this->portal->getTitle()],
                $overrides
            ),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            'portal_name' => $this->portal->getTitle(),
            'num_days' => $this->portal->getClearInactiveAccountsDeleteDays(),
        ];
    }
}
