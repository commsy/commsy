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

namespace App\Cron\Tasks;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_portal_item;
use cs_user_item;
use DateTimeImmutable;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CronExpireTakeOver implements CronTaskInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RouterInterface $router,
        private Mailer $mailer
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $translator = $this->legacyEnvironment->getTranslationObject();
        $now = new DateTimeImmutable();

        $expiredUsers = $userManager->getUserTempLoginExpired();
        foreach ($expiredUsers as $expiredUser) {
            /** @var cs_user_item $expiredUser */
            if ($expiredUser->getImpersonateExpiryDate() <= $now) {
                // unset login as timestamp
                $expiredUser->setImpersonateExpiryDate(null);
                $expiredUser->save();

                /** @var cs_portal_item $portal */
                $portal = $expiredUser->getRelatedPortalUserItem()->getContextItem();

                $subject = $translator->getMessage('EMAIL_LOGIN_EXPIRATION_SUBJECT', $portal->getTitle());

                $contactModerators = $portal->getContactModeratorList();
                $ccMails = [];
                $ccMails[] = new Address($this->legacyEnvironment->getRootUserItem()->getEmail());
                foreach ($contactModerators as $contactModerator) {
                    /** @var cs_user_item $contactModerator */
                    $contactModeratorMail = $contactModerator->getEmail();
                    if (!empty($contactModeratorMail)) {
                        $ccMails[] = new Address($contactModeratorMail);
                    }
                }

                $linkToPortal = $this->router->generate('app_helper_portalenter', [
                    'context' => $portal->getItemID(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $translator->setEmailTextArray($portal->getEmailTextArray());
                $body = '';
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()),
                    $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $expiredUser->getFullName());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_LOGIN_EXPIRATION_BODY');
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contactModerators->getFirst()->getFullName(),
                    $portal->getTitle());
                $body .= "\n\n";
                $body .= $linkToPortal;

                $this->mailer->sendRaw(
                    $subject,
                    $body,
                    RecipientFactory::createRecipient($expiredUser),
                    $portal->getTitle(),
                    [],
                    $ccMails
                );
            }
        }
    }

    public function getSummary(): string
    {
        return 'Delete expired invitations';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}
