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

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$current_context = $environment->getCurrentContextItem();

if (!$current_user->isModerator()
      or !$current_context->mayEdit($current_user)
      or !$current_context->isPortal()
      or $current_user->isGuest()
   ) {
    $params = array();
    $params['environment'] = $environment;
    $params['with_modifying_actions'] = true;
    $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
    unset($params);
    $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
    $page->addWarning($errorbox);
} else {
    //access granted

    // Find out what to do
    if (isset($_POST['option'])) {
        $command = $_POST['option'];
    } else {
        $command = '';
    }

    // Cancel editing
    if (isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
        redirect($environment->getCurrentContextID(), 'configuration', 'index', array());
    } else {
        // Show form and/or save item
   

        // Initialize the form
        $form = $class_factory->getClass(CONFIGURATION_INACTIVE_FORM, array('environment' => $environment));
        $params = array();
        $params['environment'] = $environment;
        $params['with_modifying_actions'] = true;
        $form_view = $class_factory->getClass(CONFIGURATION_DATASECURITY_FORM_VIEW, $params);
        unset($params);

        // Load form data from postvars
        if (!empty($_POST)) {
            $values = $_POST;
            $form->setFormPost($values);
        } else {
            $form->setItem($current_context);
        }

        $form->prepareForm();
        $form->loadValues();

        if (!empty($command)
           and ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) )
         ) {
            if ($form->check()) {
                if (isset($_POST['overwrite_content'])) {
                    $current_context->setInactivityOverwriteContent($_POST['overwrite_content']);
                }

                if (!empty($_POST['lock_user'])) {
                    $current_context->setInactivityLockDays($_POST['lock_user']);
                } else {
                    $current_context->setInactivityLockDays('');
                }

                if (!empty($_POST['email_before_lock'])) {
                    $current_context->setInactivitySendMailBeforeLockDays($_POST['email_before_lock']);
                } else {
                    $current_context->setInactivitySendMailBeforeLockDays('');
                }

                if (!empty($_POST['delete_user'])) {
                    $current_context->setInactivityDeleteDays($_POST['delete_user']);
                } else {
                    $current_context->setInactivityDeleteDays('');
                }

                if (!empty($_POST['email_before_delete'])) {
                    $current_context->setInactivitySendMailBeforeDeleteDays($_POST['email_before_delete']);
                } else {
                    $current_context->setInactivitySendMailBeforeDeleteDays('');
                }
                // save configuration
                $current_context->save();

                if (empty($_POST['delete_user']) and empty($_POST['lock_user'])) {
                    $params = array();
                    $params['environment'] = $environment;
                    $params['with_modifying_actions'] = true;
                    $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
                    $errorbox->setText($translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_CONFIG'));
                    $page->add($errorbox);
                } else {
                    // set config date
                    $current_context->setInactivityConfigDate();

                    // save room_item
                    $current_context->save();
                    $form_view->setItemIsSaved();

                    // warning of locked and deleted user
                    $lock_days          = $_POST['lock_user'];
                    $mail_before_lock   = $_POST['email_before_lock'];
                    $delete_days        = $_POST['delete_user'];
                    $mail_before_delete = $_POST['email_before_delete'];

                    $user_manager = $environment->getUserManager();
                    if (isset($lock_days) and !empty($lock_days)) {
                        if (isset($mail_before_lock) and !empty($mail_before_lock)) {
                            $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL(($lock_days + $mail_before_lock));
                        } else {
                            $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($lock_days);
                        }

                    }
                    if (isset($delete_days) and !empty($delete_days)) {
                        if (isset($mail_before_delete) and !empty($mail_before_delete)) {
                            $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days + $mail_before_delete);
                        } else {
                            $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days);
                        }
                    }
                    if (isset($date_lastlogin_do)) {
                        $user_array = $user_manager->getUserLastLoginLaterAs($date_lastlogin_do, $current_context->getItemID());
                    }
          
                    if (!empty($user_array)) {
                        $count_delete = 0;
                        $count_lock = 0;
                        foreach ($user_array as $user) {
                            $start_date = new DateTime(getCurrentDateTimeInMySQL());
                            $since_start = $start_date->diff(new DateTime($user->getLastLogin()));
                            $days = $since_start->days;
                            if ($days == 0) {
                                $days = 1;
                            }
                            if(!empty($delete_days) AND empty($lock_days)) {
                                if ($days >= $delete_days-1 and !empty($delete_days)) {
                                    $count_delete++;
                                    continue;
                                }
                            }
                            if ($days >= $lock_days-1 and !empty($lock_days)) {
                                $count_lock++;
                                continue;
                            }
                        }
                    }
                    if (isset($count_delete) or isset($count_lock)) {
                        if ($count_delete != 0 or $count_lock != 0) {
                            $html = '';
                            if ($count_delete > 0) {
                                $html .= $count_delete.' '.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_DELETE', $delete_days);
                            }
                            if ($count_lock > 0) {
                                $html .= $count_lock.' '.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_LOCK', $lock_days);
                            }
                            #$html .= $translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_INFO');
                      
                            $params = array();
                            $params['environment'] = $environment;
                            $params['with_modifying_actions'] = true;
                            $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
                            $errorbox->setText($html);
                            $page->add($errorbox);
                        }
                    }
                }
            }
        }

        // display form
        if (isset($current_context) and !$current_context->mayEditRegular($current_user)) {
            $form_view->warnChanger();
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $params['width'] = 500;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
            unset($params);
            $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
            $page->addWarning($errorbox);
        }

        include_once('functions/curl_functions.php');
        $form_view->setAction(curl($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), ''));
        $form_view->setForm($form);
        $page->addForm($form_view);
    }
}
?>