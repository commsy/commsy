<?php

namespace App\EventSubscriber;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class AccountActivityStateSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var AccountManager
     */
    private AccountManager $accountManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountManager $accountManager
    ) {
        $this->entityManager = $entityManager;
        $this->accountManager = $accountManager;
    }

    /**
     * Decides if an account can make the transition to the idle state
     *
     * @param GuardEvent $event
     */
    public function guardLock(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Deny transition if user is last moderator of any room
        if ($this->accountManager->isLastModerator($account)) {
            $event->setBlocked(true);
        }

        $event->setBlocked(true);
    }

    /**
     * Decides if an account can make the transition to the abandoned state
     *
     * @param GuardEvent $event
     */
    public function guardForsake(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $event->setBlocked(true);
    }

    /**
     * The account is about to enter the idle state. The marking is not yet updated.
     *
     * @param EnterEvent $event
     */
    public function enterIdle(EnterEvent $event)
    {

    }

    /**
     * The account is about to enter the abandoned state. The marking is not yet updated.
     *
     * @param EnterEvent $event
     */
    public function enterAbandoned(EnterEvent $event)
    {

    }

    protected function sendMailForUserInactivity($state, $user, Portal $portal, $days)
    {
//        // deleted deleteNext deleteNotify locked lockNext lockNotify
//        $translator = $this->legacyEnvironment->getTranslationObject();
//
//        $mail = new cs_mail();
//
//        $to = $user->getEmail();
//        $mod_contact_list = $portal->getContactModeratorList($this->legacyEnvironment);
//        $mod_user_first = $mod_contact_list->getFirst();
//
//        $urlToPortal = $this->router->generate('app_portal_goto', [
//            'portalId' => $portal->getId(),
//        ], UrlGeneratorInterface::ABSOLUTE_URL);
//
//        //content
//        $translator->setEmailTextArray($portal->getEmailTextArray());
//
//        $account = $this->accountManager->getAccount($user, $portal->getId());
//        if (!$account) {
//            return false;
//        }
//
//        $authSource = $account->getAuthSource();
//
//        if ($mod_user_first) {
//            $fullnameFirstModUser = $mod_user_first->getFullName();
//        } else {
//            $fullnameFirstModUser = '';
//        }
//
//        $emailFrom = $this->parameterBag->get('commsy.email.from');
//        $mail->set_from_email($emailFrom);
//
//        $mail->set_from_name($portal->getTitle());
//
//
//        // set message body for every inactivity state
//        switch ($state) {
//            case 'lockNotify':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NEXT_SUBJECT', $portal->getInactivitySendMailBeforeLockDays(), $portal->getTitle());
//                // lock in x days
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NEXT_BODY', $user->getUserID(), $authSource->getTitle(), $portal->getInactivitySendMailBeforeLockDays(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            case 'lockNext':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_SUBJECT', $portal->getTitle());
//                // lock tomorrow
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            case 'locked':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NOW_SUBJECT', $portal->getTitle());
//                // locked
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NOW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            case 'deleteNotify':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NEXT_SUBJECT', $portal->getInactivitySendMailBeforeDeleteDays(), $portal->getTitle());
//                // delete in x days
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NEXT_BODY', $user->getUserID(), $authSource->getTitle(), $portal->getInactivitySendMailBeforeDeleteDays(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            case 'deleteNext':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_SUBJECT', $portal->getTitle());
//                // delete tomorrow
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            case 'deleted':
//                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NOW_SUBJECT', '', $portal->getTitle());
//                // deleted
//                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NOW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('EMAIL_COMMSY_PORTAL_MODERATION');
//                $body .= "\n\n";
//                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
//                $body .= "\n\n";
//                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                break;
//            default:
//                // Should not be used
//                break;
//        }
//
//        $mail->set_subject($subject);
//        $mail->set_message($body);
//        $mail->set_to($to);
//
//        return $mail;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.account_activity.guard.lock' => ['guardLock'],
            'workflow.account_activity.guard.forsake' => ['guardForsake'],
            'workflow.account_activity.enter.idle' =>  ['enterIdle'],
            'workflow.account_activity.enter.abandoned' => ['enterAbandoned'],
        ];
    }
}