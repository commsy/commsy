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

use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
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

    public function __construct(LegacyEnvironment $legacyEnvironment, private readonly RouterInterface $router, private readonly CurrentContextResolver $currentContextResolver, private readonly TranslatorInterface $translator)
    {
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
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->currentContextResolver->getContextItem();
        $portal = $this->currentContextResolver->getPortalItem();

        $oldContextType = $legacyTranslator->getContext();
        $legacyTranslator->setContext($room->getType());
        $legacyTranslator->setEmailTextArray($portal->getEmailTextArray());

        $body = $legacyTranslator->getEmailMessage('MAIL_BODY_HELLO', $multipleRecipients ? ' ' : $user->getFullname());
        $body .= '<br/><br/>';

        $moderator = $this->legacyEnvironment->getCurrentUserItem();

        $absoluteRoomUrl = $this->router->generate('app_room_home', [
            'roomId' => $this->currentContextResolver->getContextId() ?? 0,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $body .= match ($action) {
            'user-delete' => $legacyTranslator->getEmailMessageInLang(
                $this->legacyEnvironment->getUserLanguage(),
                'MAIL_BODY_USER_ACCOUNT_DELETE',
                $user->getUserID(),
                $room->getTitle()
            ),
            'user-block' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_ACCOUNT_LOCK',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
            'user-status-user',
            'user-confirm' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_STATUS_USER',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
            'user-status-moderator' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_STATUS_MODERATOR',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
            'user-status-reading-user' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_STATUS_USER_READ_ONLY',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
            'user-contact' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_MAKE_CONTACT_PERSON',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
            'user-contact-remove' => $legacyTranslator->getEmailMessage(
                'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',
                $multipleRecipients ? ' ' : $user->getUserID(),
                $room->getTitle()
            ),
        };

        if (!in_array($action, ['user-delete', 'user-block'])) {
            $body .= '<br/><br/>';
            $body .= "<a href=\"$absoluteRoomUrl\">$absoluteRoomUrl</a>";
        }

        $body .= '<br/><br/>';

        $message = $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO', $moderator->getFullname(),
            $room->getTitle());
        $body .= $message;

        $legacyTranslator->setContext($oldContextType);

        return $body;
    }
}
