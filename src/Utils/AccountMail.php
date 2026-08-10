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

namespace App\Utils;

use App\Mail\Text\MailTextRenderer;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\Services\PortalUrlResolver;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This is just a helper class to construct mails on any account action.
 * TODO: Refactor this with other mail tools to an abstract factory.
 */
class AccountMail
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RouterInterface $router,
        private readonly CurrentContextResolver $currentContextResolver,
        private readonly PortalUrlResolver $portalUrlResolver,
        private readonly TranslatorInterface $translator,
        private readonly MailTextRenderer $mailTextRenderer
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function generateSubject(string $action): string
    {
        $room = $this->currentContextResolver->getContextItem();
        $locale = $this->legacyEnvironment->getSelectedLanguage();
        $title = $room->getTitle();

        return match ($action) {
            'user-delete' => $this->translator->trans('mail.subject.account_delete', ['p1' => $title], 'mail', $locale),
            'user-block' => $this->translator->trans('mail.subject.account_lock', ['p1' => $title], 'mail', $locale),
            'user-confirm' => $this->translator->trans('mail.subject.account_free', ['p1' => $title], 'mail', $locale),
            'user-status-user' => $this->translator->trans('mail.subject.status_user', ['p1' => $title], 'mail', $locale),
            'user-status-moderator' => $this->translator->trans('mail.subject.status_moderator', ['p1' => $title], 'mail', $locale),
            'user-status-reading-user' => $this->translator->trans('mail.subject.status_read_only_user', ['p1' => $title], 'mail', $locale),
            'user-contact' => $this->translator->trans('mail.subject.make_contact_person', ['p1' => $title], 'mail', $locale),
            'user-contact-remove' => $this->translator->trans('mail.subject.unmake_contact_person', ['p1' => $title], 'mail', $locale),
            'user-account-merge' => $this->translator->trans('mail.subject.account_merge', ['p1' => $title], 'mail', $locale),
            'user-account_password' => $this->translator->trans('mail.subject.account_password', ['p1' => $title], 'mail', $locale),
            'user-account_send_mail' => $this->translator->trans('mail.subject.generic', [], 'mail', $locale),
            default => '',
        };
    }

    /**
     * @param bool $multipleRecipients
     */
    public function generateBody(cs_user_item $user, string $action, $multipleRecipients = false): string
    {
        $room = $this->currentContextResolver->getContextItem();
        $portal = $this->currentContextResolver->getPortalItem();
        $roomType = $room->getType(); // project|community|grouproom -> ICU select; anything else -> "other"
        $overrides = $portal->getEmailTextArray();
        $locale = $this->legacyEnvironment->getSelectedLanguage();
        $moderator = $this->legacyEnvironment->getCurrentUserItem();

        // user-delete renders the body in the recipient's own language (legacy getUserLanguage)
        $bodyDefinition = match ($action) {
            'user-delete' => ['mail.body.account_delete', 'MAIL_BODY_USER_ACCOUNT_DELETE', $this->legacyEnvironment->getUserLanguage()],
            'user-block' => ['mail.body.account_lock', 'MAIL_BODY_USER_ACCOUNT_LOCK', $locale],
            'user-status-user', 'user-confirm' => ['mail.body.status_user', 'MAIL_BODY_USER_STATUS_USER', $locale],
            'user-status-moderator' => ['mail.body.status_moderator', 'MAIL_BODY_USER_STATUS_MODERATOR', $locale],
            'user-status-reading-user' => ['mail.body.status_read_only', 'MAIL_BODY_USER_STATUS_USER_READ_ONLY', $locale],
            'user-contact' => ['mail.body.make_contact_person', 'MAIL_BODY_USER_MAKE_CONTACT_PERSON', $locale],
            'user-contact-remove' => ['mail.body.unmake_contact_person', 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', $locale],
            // A plain mail, a merge or a password change carries no status text of its
            // own — the moderator composes those freely, so there is nothing to offer.
            // Anything unknown is treated the same rather than failing: an empty
            // suggestion beats a 500 on a moderator action, which is what the missing
            // default arm used to produce.
            default => null,
        };

        if (null === $bodyDefinition) {
            return '';
        }

        // Account actions are performed from a room as well as from the portal's account
        // index. Portal ids and room ids come from the same sequence, so linking to
        // app_room_home with the portal's context id would point at whatever room happens
        // to carry that number.
        $absoluteContextUrl = $room->isPortal()
            ? $this->portalUrlResolver->resolve($portal)
            : $this->router->generate('app_room_home', [
                'roomId' => $this->currentContextResolver->getContextId() ?? 0,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = $this->mailTextRenderer->render(
            'mail.salutation',
            'MAIL_BODY_HELLO',
            $roomType,
            $locale,
            [$multipleRecipients ? ' ' : $user->getFullname()],
            $overrides
        );
        $body .= '<br/><br/>';

        [$bodyKey, $bodyLegacyId, $bodyLocale] = $bodyDefinition;
        $bodyUserId = ('user-delete' === $action || !$multipleRecipients) ? $user->getUserID() : ' ';
        $body .= $this->mailTextRenderer->render(
            $bodyKey,
            $bodyLegacyId,
            $roomType,
            $bodyLocale,
            [$bodyUserId, $room->getTitle()],
            $overrides
        );

        if (!in_array($action, ['user-delete', 'user-block'])) {
            $body .= '<br/><br/>';
            $body .= "<a href=\"$absoluteContextUrl\">$absoluteContextUrl</a>";
        }

        $body .= '<br/><br/>';
        $body .= $this->mailTextRenderer->render(
            'mail.goodbye',
            'MAIL_BODY_CIAO',
            $roomType,
            $locale,
            [$moderator->getFullname(), $room->getTitle()],
            $overrides
        );

        return $body;
    }
}
