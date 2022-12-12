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

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This is just a helper class to construct mails on any account action.
 * TODO: Refactor this with other mail tools to an abstract factory.
 */
class AccountMail
{
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, private RouterInterface $router)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function generateSubject(string $action): string
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->legacyEnvironment->getCurrentContextItem();

        $subject = match ($action) {
            'user-delete' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE', $room->getTitle()),
            'user-block' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK', $room->getTitle()),
            'user-confirm' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE', $room->getTitle()),
            'user-status-user' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $room->getTitle()),
            'user-status-moderator' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR', $room->getTitle()),
            'user-status-reading-user' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_READ_ONLY_USER', $room->getTitle()),
            'user-contact' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON', $room->getTitle()),
            'user-contact-remove' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON', $room->getTitle()),
            'user-account-merge' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_MERGE', $room->getTitle()),
            'user-account_password' => $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_PASSWORD', $room->getTitle()),
            'user-account_send_mail' => $legacyTranslator->getMessage('MAIL_SUBJECT', $room->getTitle()),
            default => $subject,
        };

        return $subject;
    }

    /**
     * @param bool $multipleRecipients
     */
    public function generateBody(cs_user_item $user, string $action, $multipleRecipients = false): string
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->legacyEnvironment->getCurrentContextItem();

        $body = $legacyTranslator->getEmailMessage('MAIL_BODY_HELLO', $multipleRecipients ? ' ' : $user->getFullname());
        $body .= '<br/><br/>';

        $moderator = $this->legacyEnvironment->getCurrentUserItem();

        $absoluteRoomUrl = $this->router->generate('app_room_home', [
            'roomId' => $this->legacyEnvironment->getCurrentContextID(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        switch ($action) {
            case 'user-delete':
                $mailText = $legacyTranslator->getEmailMessageInLang($this->legacyEnvironment->getUserLanguage(), 'MAIL_BODY_USER_ACCOUNT_DELETE', $user->getUserID(), $room->getTitle());
                $body .= $mailText;
                break;

            case 'user-block':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-confirm':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-status-user':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-status-moderator':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-status-reading-user':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_READ_ONLY', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-contact':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;

            case 'user-contact-remove':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n", '<br/>', $message);
                $body .= $message;

                break;
        }

        if ('user-delete' !== $action) {
            $body .= '<br/><br/>';
            $body .= $absoluteRoomUrl;
        }

        $body .= '<br/><br/>';

        $message = $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO', $moderator->getFullname(),
            $room->getTitle());
        $message = str_replace("\n", '<br/>', $message);
        $body .= $message;

        return $body;
    }
}
