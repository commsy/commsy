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

   $configuration_important_link_list = new cs_list();

   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('CONFIGURATION_ROOM_OPTIONS_TITLE'));
   $current_context = $environment->getCurrentContextItem();
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/room_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/room_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/room_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/room_options.png');
   }
   $link_item->setDescription($translator->getMessage('CONFIGURATION_ROOM_OPTIONS_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('room_options');
   $link_item->setParameter(array());
   $configuration_important_link_list->add($link_item);

   if ( $environment->inPrivateRoom() ) {
      $current_context_id = $environment->getCurrentContextID();
      $current_portal_id = $environment->getCurrentPortalID();
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_OPTIONS_TITLE'));
      $current_context = $environment->getCurrentContextItem();
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/privateroom_home_options.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/privateroom_home_options.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/privateroom_home_options.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/privateroom_home_options.png');
      }
      $link_item->setDescription($translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_OPTIONS_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('privateroom_home_options');
      $link_item->setParameter(array());
      $configuration_important_link_list->add($link_item);
   }


   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('CONFIGURATION_RUBRIC_OPTIONS_TITLE'));
   $current_context = $environment->getCurrentContextItem();
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/rubric_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/rubric_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/rubric_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/rubric_options.png');
   }
   $link_item->setDescription($translator->getMessage('CONFIGURATION_RUBRIC_OPTIONS_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('rubric_options');
   $link_item->setParameter(array());
   $configuration_important_link_list->add($link_item);

   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE'));
   $current_context = $environment->getCurrentContextItem();
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/structure_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/structure_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/structure_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/structure_options.png');
   }
   $link_item->setDescription($translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('structure_options');
   $link_item->setParameter(array());
   $configuration_important_link_list->add($link_item);

   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE'));
   $link_item->setDescription($translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_DESC'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/account_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/account_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/account_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/account_options.png');
   }
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('account_options');
   $link_item->setParameter('');
   $configuration_important_link_list->add($link_item);

#   $configuration_important_link_list->sortby('title');
?>