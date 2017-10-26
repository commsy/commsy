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

if ( isset($_POST['message_language_select']) ) {
    if ( empty($_POST['message_language_select'])
        or $_POST['message_language_select'] == 'reset'
    ) {
        $session->unsetValue('message_language_select_dev');
    } else {
        $session->setValue('message_language_select_dev',$_POST['message_language_select']);
    }
} else {
    $current_user = $environment->getCurrentUserItem();
    if ( $current_user->isUser() ) {
        if ( !empty($_GET['language']) ) {
            $current_user->setLanguage($_GET['language']);
            $current_user->setChangeModificationOnSave(false);
            $current_user->save();

            $session_item = $environment->getSessionItem();
            if ( $session_item->issetValue('message_language_select') ) {
                $session_item->unsetValue('message_language_select');
            }
            if ( $session_item->issetValue('message_language_select_dev') ) {
                $session_item->unsetValue('message_language_select_dev');
            }
        }
    } elseif ( !empty($_GET['language']) ) {
        $session_item = $environment->getSessionItem();
        $session_item->setValue('message_language_select',$_GET['language']);
        if ( $session_item->issetValue('message_language_select_dev') ) {
            $session_item->unsetValue('message_language_select_dev');
        }
    }
}

// back
$history = $session->getValue('history');
$params = $history[0]['parameter'];
redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
?>