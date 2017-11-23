<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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
$is_saved = false;

// get cid
if (!empty($_GET['cid'])) {
    $current_cid = $_GET['cid'];
} elseif (!empty($_POST['cid'])) {
    $current_cid = $_POST['cid'];
} else {
    include_once('functions/error_functions.php');
    trigger_error('context id lost', E_USER_ERROR);
}

// hier muss auf den aktuellen Kontext referenziert werden,
// da sonst später diese Einstellung wieder überschrieben wird
// in der commsy.php beim Speichern der Aktivität
$current_context_item = $environment->getCurrentContextItem();
$current_cid = $current_context_item->getItemId();
if ($current_cid == $current_context_item->getItemID()) {
    $item = $current_context_item;
} else {
    if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
        $room_manager = $environment->getRoomManager();
    } elseif ($environment->inPortal()) {
        $room_manager = $environment->getPortalManager();
    }
    $item = $room_manager->getItem($current_cid);
}

// Check access rights
if (isset($item) and !$item->mayEdit($current_user)) {
    $params = array();
    $params['environment'] = $environment;
    $params['with_modifying_actions'] = true;
    $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
    unset($params);
    $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
    $page->add($errorbox);
} // Access granted
else {

    // Find out what to do
    if (isset($_POST['option'])) {
        $command = $_POST['option'];
    } else {
        $command = '';
    }

    // Initialize the form
    $form = $class_factory->getClass(CONFIGURATION_SERVICE_FORM, array('environment' => $environment));
    // display form
    $params = array();
    $params['environment'] = $environment;
    $params['with_modifying_actions'] = true;
    $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW, $params);
    unset($params);

    // Load form data from postvars
    if (!empty($_POST)) {
        $form->setFormPost($_POST);
    } // Load form data from database
    elseif (isset($item)) {
        $form->setItem($item);
    }
    $form->prepareForm();
    $form->loadValues();

    // Save item
    if (!empty($command) and
        (isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')))) {

        if ($form->check()) {

            // Set modificator and modification date
            $current_user = $environment->getCurrentUserItem();
            $item->setModificatorItem($current_user);
            $item->setModificationDate(getCurrentDateTimeInMySQL());

            $item2 = $item;
            $temp_service_mail = '';
            $temp_service_link_external = '';
            while (isset($item2) and !$item2->isServer() and empty($temp_service_mail)) {
                $item2 = $item2->getContextItem();
                if (isset($item2)) {
                    $temp_service_mail = $item2->getServiceEmail();
                    $temp_service_link_external = $item2->getServiceLinkExternal();
                }
            }

            // mail to moderator link
            if (isset($_POST['moderatorlink']) and !empty($_POST['moderatorlink']) and $_POST['moderatorlink'] == 1) {
                $item->setModeratorLinkActive();
            } else {
                $item->setModeratorLinkInactive();
            }

            // service link
            if (isset($_POST['servicelink']) and !empty($_POST['servicelink']) and $_POST['servicelink'] == 1) {
                $item->setServiceLinkActive();
            } else {
                $item->setServiceLinkInactive();
                $item->setModeratorLinkActive();
            }
            if ($item->isServiceLinkActive()) {
                if (!empty($_POST['serviceemail'])
                    and (!isset($_POST['reset'])
                        or !$_POST['reset']
                    )
                    and (empty($temp_service_mail)
                        or $temp_service_mail != $_POST['serviceemail']
                    )
                ) {
                    $item->setServiceEmail($_POST['serviceemail']);
                } else {
                    $item->setServiceEmail('');
                }

                // external link to support formular
                if (!empty($_POST['service_link_external'])
                    and (!isset($_POST['reset'])
                        or !$_POST['reset']
                    )
                    and (empty($temp_service_link_external)
                        or $temp_service_link_external != $_POST['service_link_external']
                    )
                ) {
                    $item->setServiceLinkExternal($_POST['service_link_external']);
                } else {
                    $item->setServiceLinkExternal('');
                }
            }

            // Save item
            $item->save();
            $form_view->setItemIsSaved();
            $is_saved = true;
        }
    }

    if (isset($item) and !$item->mayEditRegular($current_user)) {
        $form_view->warnChanger();
        $params = array();
        $params['environment'] = $environment;
        $params['with_modifying_actions'] = true;
        $params['width'] = 500;
        $errorbox = $class_factory->getClass(ERRORBOX_VIEW, $params);
        unset($params);
        $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
        $page->add($errorbox);
    }

    include_once('functions/curl_functions.php');
    $form_view->setAction(curl($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), ''));
    $form_view->setForm($form);
    if ($environment->inPortal() or $environment->inServer()) {
        $page->addForm($form_view);
    } else {
        $page->add($form_view);
    }
}