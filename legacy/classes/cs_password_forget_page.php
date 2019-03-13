<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('classes/cs_left_page.php');

class cs_password_forget_page extends cs_left_page
{

    public function __construct($environment)
    {
        cs_left_page::__construct($environment);
    }

    public function execute()
    {
        $class_params = array();
        $class_params['environment'] = $this->_environment;
        $form = $this->_class_factory->getClass(PASSWORD_FORGET_FORM, $class_params);
        unset($class_params);
        // Load form data from postvars
        if (!empty($this->_post_vars)) {
            $form->setFormPost($this->_post_vars);
        }
        $form->prepareForm();
        $form->loadValues();

        // cancel
        if (!empty($this->_command)
            and (isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
                or isOption($this->_command, $this->_translator->getMessage('COMMON_FORWARD_BUTTON')))
        ) {
            $this->_redirect_back();
        }

        // Save item
        if (!empty($this->_command)
            and isOption($this->_command, $this->_translator->getMessage('PASSWORD_GENERATE_BUTTON'))
        ) {
            $correct = $form->check();
            if ($correct) {

                // save special session
                $user_manager = $this->_environment->getUserManager();
                $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
                $user_manager->setUserIDLimit($this->_post_vars['user_id']);
                if (!empty($this->_post_vars['auth_source'])) {
                    $user_manager->setAuthSourceLimit($this->_post_vars['auth_source']);
                }
                $user_manager->select();
                $user_list = $user_manager->get();
                $user_item = $user_list->getFirst();
                $success = true;
                while ($user_item) {

                    // auth source
                    $auth_source_manager = $this->_environment->getAuthSourceManager();
                    $auth_source_item = $auth_source_manager->getItem($user_item->getAuthSource());

                    if ($auth_source_item->allowChangePassword()) {
                        include_once('classes/cs_session_item.php');
                        $new_special_session_item = new cs_session_item();
                        $new_special_session_item->createSessionID($this->_post_vars['user_id']);
                        $new_special_session_item->setValue('auth_source', $user_item->getAuthSource());
                        if ($this->_post_vars['user_id'] == 'root') {
                            $new_special_session_item->setValue('commsy_id', $this->_environment->getServerID());
                        } else {
                            $new_special_session_item->setValue('commsy_id', $this->_environment->getCurrentPortalID());
                        }
                        if (isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
                            $new_special_session_item->setValue('password_forget_ip', $_SERVER["SERVER_ADDR"]);
                        } else {
                            $new_special_session_item->setValue('password_forget_ip', $_SERVER["HTTP_HOST"]);
                        }
                        include_once('functions/date_functions.php');
                        $new_special_session_item->setValue('password_forget_time', getCurrentDateTimeInMySQL());
                        $new_special_session_item->setValue('javascript', -1);
                        $new_special_session_item->setValue('cookie', 0);
                        $session_manager = $this->_environment->getSessionManager();
                        $session_manager->save($new_special_session_item);
                    }

                    $user_fullname = $user_item->getFullName();
                    $user_email = $user_item->getEMail();
                    $user_id = $user_item->getUserID();

                    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?cid=' . $this->_environment->getCurrentPortalID();
                    if ($auth_source_item->allowChangePassword()) {
                        $url .= '&SID=' . $new_special_session_item->getSessionID();
                    }

                    // send email
                    $context_item = $this->_environment->getCurrentPortalItem();
                    $mod_text = '';
                    $mod_list = $context_item->getModeratorList();
                    if (!$mod_list->isEmpty()) {
                        $mod_item = $mod_list->getFirst();
                        $contact_moderator = $mod_item;
                        while ($mod_item) {
                            if (!empty($mod_text)) {
                                $mod_text .= ',' . LF;
                            }
                            $mod_text .= $mod_item->getFullname();
                            $mod_text .= ' (' . $mod_item->getEmail() . ')';
                            $mod_item = $mod_list->getNext();
                        }
                    }

                    $translator = $this->_environment->getTranslationObject();

                    global $symfonyContainer;
                    $emailFrom = $symfonyContainer->getParameter('commsy.email.from');

                    $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                    $body .= LF . LF;
                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $user_fullname);
                    $body .= LF . LF;
                    if ($auth_source_item->allowChangePassword()) {
                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY', $user_id, $context_item->getTitle(), $url, '15');
                    } else {
                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY', $user_id, $context_item->getTitle());
                        $body .= LF . LF;
                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2', $auth_source_item->getTitle());
                        $link = $auth_source_item->getPasswordChangeLink();
                        $contact_mail = $auth_source_item->getContactEMail();
                        if (!empty($link)) {
                            $body .= LF . LF;
                            $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2_LINK', $link);
                        }
                        if (!empty($contact_mail)) {
                            $body .= LF . LF;
                            $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2_MAIL', $auth_source_item->getTitle(), $contact_mail);
                        }
                        $body .= LF . LF;
                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY3');
                    }
                    $body .= LF . LF;
                    if (empty($contact_moderator)) {
                        $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO') . LF;
                        $body .= $mod_text;
                        $body .= LF . LF;
                    } else {
                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contact_moderator->getFullname(), $context_item->getTitle());
                        $body .= LF . LF;
                    }

                    $message = (new \Swift_Message())
                        ->setSubject($translator->getMessage('USER_PASSWORD_MAIL_SUBJECT', $context_item->getTitle()))
                        ->setBody($body, 'text/plain')
                        ->setFrom([$emailFrom => $this->_translator->getMessage('SYSTEM_MAIL_MESSAGE', $context_item->getTitle())])
                        ->setTo($user_email);

                    if (isset($contact_moderator)) {
                        $message->setReplyTo([$contact_moderator->getEmail() => $contact_moderator->getFullname()]);
                    }

                    $mailer = $symfonyContainer->get('mailer');
                    $success = $success && $mailer->send($message);

                    $user_item = $user_list->getNext();
                }
                if ($success) {
                    // show little status page that mail was sent successful
                    $form->showMailSent($user_email);
                } else {
                    // show little status page that mail was not sent successful
                    $form->showMailFailure();
                }
            }
        }
        return $this->_show_form($form);
    }
}

?>