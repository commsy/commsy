<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

mb_internal_encoding('UTF-8');

if ( isset($_GET['cid']) ) {
	if ( isset($_GET['sid']) ) {
		chdir('..');
      include_once('functions/misc_functions.php');
	   include_once('etc/cs_constants.php');
	   include_once('etc/cs_config.php');

	   include_once('classes/cs_environment.php');
	   $environment = new cs_environment();
	   $environment->setCurrentContextID($_GET['cid']);
	   $context_item = $environment->getCurrentContextItem();
	   $session_manager = $environment->getSessionManager();
	   $translator = $environment->getTranslationObject();

	   $validated = false;
	   if ( $context_item->isOpenForGuests() ) {
	      $validated = true;
	   }

	   if ( !$context_item->isPortal()
	   and !$context_item->isServer()
	   and isset($_GET['sid'])
	   and !empty($_GET['sid'])
	   and !$validated
	   ) {
	      if ( !$context_item->isLocked()) {
	        $session_item = $session_manager->get($_GET['sid']);
            if ( isset($session_item) and $session_item->issetValue('user_id') ) {
               $validated = true;
            }
	      }
	   }
	   if($validated) {
	   	if(isset($_GET['fct'])){
	   		if(file_exists('pages/ajax/'.$_GET['fct'].'.php')){
	   			include_once('pages/ajax/'.$_GET['fct'].'.php');
	   		}
	   	}
	   }
	} else {
		chdir('..');
	   include_once('etc/cs_constants.php');
	   include_once('etc/cs_config.php');
	   include_once('classes/cs_environment.php');
	   $environment = new cs_environment();
	   $translator = $environment->getTranslationObject();
	   die($translator->getMessage('AJAX_NO_SID_GIVEN'));
	}
} else {
	chdir('..');
	include_once('etc/cs_constants.php');
	include_once('etc/cs_config.php');
	include_once('classes/cs_environment.php');
	$environment = new cs_environment();
	$translator = $environment->getTranslationObject();
	die($translator->getMessage('AJAX_NO_CID_GIVEN'));
}
?>