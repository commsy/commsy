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

include_once('classes/cs_link.php');
include_once('classes/cs_list.php');

if ( !isset($environment) and isset($this->_environment) ) {
   $environment = $this->_environment;
}
   $translator = $environment->getTranslationObject();

   $room_link_list = new cs_list();

   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('COMMON_ROOM_META'));
   $current_context = $environment->getCurrentContextItem();
   $link_item->setIconPath('images/cs_config/COMMON_ROOM_META.gif');
   $link_item->setDescription($translator->getMessage('COMMON_ROOM_META_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('preferences');
   $link_item->setParameter(array());
   $room_link_list->add($link_item);


   if ($environment->inPortal()){
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_PORTAL_HOME'));
      $link_item->setDescription($translator->getMessage('CONFIGURATION_PORTAL_HOME_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_ROOM_HOME.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('portalhome');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
      unset($link_item);
   }


   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('RUBRIC_ADMIN'));
      $link_item->setIconPath('images/cs_config/RUBRIC_ADMIN.gif');
      $link_item->setDescription($translator->getMessage('RUBRIC_ADMIN_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('rubric');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);

      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_TAGS_CONFIGURATIONS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_TAGS_CONFIGURATIONS_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_TAGS.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('tags');
      $link_item->setParameter('');
      $room_link_list->add($link_item);

      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_LISTVIEWS_CONFIGURATION'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_LISTVIEWS_CONFIGURATION_DESC'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_LISTVIEWS_CONFIGURATION.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('listviews');
      $link_item->setParameter('');
      $room_link_list->add($link_item);
   }

   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_ROOM_HOME'));
      if ( $environment->inPrivateRoom() ) {
         $link_item->setDescription($translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_DESC'));
         $link_item->setIconPath('images/cs_config/CONFIGURATION_ROOM_HOME.gif');
         $link_item->setContextID($environment->getCurrentContextID());
         $link_item->setModule('configuration');
         $link_item->setFunction('home');
         $link_item->setParameter(array());
         $room_link_list->add($link_item);
      } else {
         $link_item->setDescription($translator->getMessage('CONFIGURATION_ROOM_HOME_DESC'));
         $link_item->setIconPath('images/cs_config/CONFIGURATION_ROOM_HOME.gif');
         $link_item->setContextID($environment->getCurrentContextID());
         $link_item->setModule('configuration');
         $link_item->setFunction('home');
         $link_item->setParameter(array());
         $room_link_list->add($link_item);
      }
   }

   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_DEFAULT_CONFIGURATIONS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_DEFAULT_CONFIGURATIONS_DESC'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_DEFAULT_CONFIGURATIONS.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('defaults');
      $link_item->setParameter('');
      $room_link_list->add($link_item);
   }

   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_TIME'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_TIME.gif');
      $link_item->setDescription($translator->getMessage('PREFERENCES_TIME_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('time');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }

   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_ROOM_OPENING_LINK'));
      $link_item->setIconPath('images/cs_config/PORTAL_ENTER_NEW.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_ROOM_OPENING_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('room_opening');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('SERVER_IMS_LINK'));
      $link_item->setIconPath('images/cs_config/IMS_CONFIGURATION_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('SERVER_IMS_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('ims');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }

   if ( $environment->inPrivateRoom() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_NEWSLETTER_LINK'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_NEWSLETTER.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_NEWSLETTER_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('privateroom_newsletter');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $room_link_list->add($link_item);
   }
   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_AUTHENTICATION_LINK'));
      $link_item->setIconPath('images/cs_config/IMS_CONFIGURATION_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_AUTHENTICATION_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('authentication');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }
   if ( $environment->inServer()
        or $environment->inPortal()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_LANGUAGE_LINK'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_LANGUAGE_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_LANGUAGE_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('language');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_BACKUP_LINK'));
      $link_item->setIconPath('images/cs_config/PORTAL_ENTER_NEW.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_BACKUP_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('backup');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
   }

// portal- and room-specific upload file size limits are currently not supported
/*   if ($environment->inPortal()){
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_PORTAL_UPLOAD'));
      $link_item->setDescription($translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_DESC'));
      $link_item->setIconPath('images/cs_config/COMMON_ROOM_META.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('portalupload');
      $link_item->setParameter(array());
      $room_link_list->add($link_item);
      unset($link_item);
   }*/

   $room_link_list->sortby('title');
?>