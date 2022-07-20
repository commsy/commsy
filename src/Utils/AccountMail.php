<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This is just a helper class to construct mails on any account action.
 * TODO: Refactor this with other mail tools to an abstract factory
 */
class AccountMail
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    public function __construct(LegacyEnvironment $legacyEnvironment, RouterInterface $router)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
    }

    /**
     * @param string $action
     * @return string
     */
    public function generateSubject(string $action): string
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

            case 'user-account-merge':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_MERGE', $room->getTitle());

                break;

            case 'user-account_password':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_PASSWORD', $room->getTitle());

                break;

            case 'user-account_send_mail':
                $subject = $legacyTranslator->getMessage('MAIL_SUBJECT', $room->getTitle());

                break;
        }

        return $subject;
    }

    /**
     * @param cs_user_item $user
     * @param string $action
     * @param bool $multipleRecipients
     * @return string
     */
    public function generateBody(cs_user_item $user, string $action, $multipleRecipients = false): string
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();
        $room = $this->legacyEnvironment->getCurrentContextItem();

        $body = $legacyTranslator->getEmailMessage('MAIL_BODY_HELLO', $multipleRecipients ? ' ' : $user->getFullname());
        $body .= "<br/><br/>";

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
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-confirm':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-status-user':

                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-status-moderator':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-status-reading-user':

                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_READ_ONLY', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-contact':

                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;

            case 'user-contact-remove':
                $message = $legacyTranslator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', $multipleRecipients ? ' ' : $user->getUserID(),
                    $room->getTitle());
                $message = str_replace("\n","<br/>", $message);
                $body .= $message;

                break;
        }

        if ($action !== 'user-delete') {
            $body .= "<br/><br/>";
            $body .= $absoluteRoomUrl;
        }

        $body .= "<br/><br/>";

        $message = $legacyTranslator->getEmailMessage('MAIL_BODY_CIAO', $moderator->getFullname(),
            $room->getTitle());
        $message = str_replace("\n","<br/>", $message);
        $body .= $message;

        return $body;
    }
}