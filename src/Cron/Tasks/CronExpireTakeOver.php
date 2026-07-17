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
use App\Mail\Text\MailTextRenderer;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use DateTimeImmutable;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class CronExpireTakeOver implements CronTaskInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RouterInterface $router,
        private Mailer $mailer,
        private TranslatorInterface $translator,
        private MailTextRenderer $mailTextRenderer
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $now = new DateTimeImmutable();
        $locale = $this->legacyEnvironment->getSelectedLanguage();
        // legacy MAIL_AUTO footer date/time formatting (de d.m.Y / H:i, en m/d/Y / h:ia)
        $autoDate = 'de' === $locale ? $now->format('d.m.Y') : $now->format('m/d/Y');
        $autoTime = 'de' === $locale ? $now->format('H:i') : $now->format('h:ia');

        $expiredUsers = $userManager->getUserTempLoginExpired();
        foreach ($expiredUsers as $expiredUser) {
            /** @var cs_user_item $expiredUser */
            if ($expiredUser->getImpersonateExpiryDate() <= $now) {
                // unset login as timestamp
                $expiredUser->setImpersonateExpiryDate(null);
                $expiredUser->save();

                $portal = $expiredUser->getPortal();

                $subject = $this->translator->trans('mail.subject.login_expiration', ['p1' => $portal->getTitle()], 'mail', $locale);

                $contactModerators = $portal->getContactModeratorList();
                $ccMails = [];
                $ccMails[] = $this->legacyEnvironment->getRootUserItem()->getEmail();
                foreach ($contactModerators as $contactModerator) {
                    /** @var cs_user_item $contactModerator */
                    $contactModeratorMail = $contactModerator->getEmail();
                    if (!empty($contactModeratorMail)) {
                        $ccMails[] = $contactModeratorMail;
                    }
                }

                $linkToPortal = $this->router->generate('app_helper_portalenter', [
                    'context' => $portal->getItemID(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $overrides = $portal->getEmailTextArray();
                $body = $this->translator->trans('mail.auto_sent', ['p1' => $autoDate, 'p2' => $autoTime], 'mail', $locale);
                $body .= "\n\n";
                $body .= $this->mailTextRenderer->render('mail.salutation', 'MAIL_BODY_HELLO', 'other', $locale, [$expiredUser->getFullName()], $overrides);
                $body .= "\n\n";
                $body .= $this->mailTextRenderer->render('mail.body.login_expiration', 'EMAIL_LOGIN_EXPIRATION_BODY', 'other', $locale, [], $overrides);
                $body .= "\n\n";
                $body .= $this->mailTextRenderer->render('mail.goodbye', 'MAIL_BODY_CIAO', 'other', $locale, [$contactModerators->getFirst()->getFullName(), $portal->getTitle()], $overrides);
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
}
