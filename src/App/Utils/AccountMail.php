<?php

namespace App\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This is just a helper class to construct mails on any account action.
 * TODO: Refactor this with other mail tools to an abstract factory
 */
class AccountMail
{
    private $legacyEnvironment;
    private $router;

    public function __construct(LegacyEnvironment $legacyEnvironment, Router $router)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
    }

    public function generateSubject($action)
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->legacyEnvironment->getCurrentContextItem();

        switch ($action) {
            case 'user-delete':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE', $room->getTitle());

                break;

            case 'user-block':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK', $room->getTitle());

                break;

            case 'user-confirm':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE', $room->getTitle());

                break;

            case 'user-status-user':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $room->getTitle());

                break;

            case 'user-status-moderator':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR', $room->getTitle());

                break;

            case 'user-status-reading-user':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_STATUS_READ_ONLY_USER', $room->getTitle());

                break;

            case 'user-contact':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON', $room->getTitle());

                break;

            case 'user-contact-remove':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON', $room->getTitle());

                break;
        }

        return $subject;
    }

    public function generateBody($user, $action)
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->legacyEnvironment->getCurrentContextItem();

        $body = $legacyTranslator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
        $body .= "\n\n";

        $moderator = $this->legacyEnvironment->getCurrentUserItem();

        $absoluteRoomUrl = $this->router->generate('commsy_room_home', [
            'roomId' => $this->legacyEnvironment->getCurrentContextID(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        switch ($action) {
            case 'user-delete':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE', $user->getUserID(), $room->getTitle());

                break;

            case 'user-block':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK', $user->getUserID(), $room->getTitle());

                break;

            case 'user-confirm':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $user->getUserID(), $room->getTitle());

                break;

            case 'user-status-user':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $user->getUserID(), $room->getTitle());

                break;

            case 'user-status-moderator':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR', $user->getUserID(), $room->getTitle());

                break;

            case 'user-status-reading-user':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_READ_ONLY', $user->getUserID(), $room->getTitle());

                break;

            case 'user-contact':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON', $user->getUserID(), $room->getTitle());

                break;

            case 'user-contact-remove':
                $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', $user->getUserID(), $room->getTitle());

                break;
        }

        $body .= "\n\n";
        $body .= $absoluteRoomUrl;
        $body .= "\n\n";
        $body .= $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO', $moderator->getFullname(), $room->getTitle());

        return $body;
    }
}