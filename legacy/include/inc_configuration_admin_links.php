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

$admin_link_list = new cs_list();

global $symfonyContainer;
$router = $symfonyContainer->get('router');

if ( !isset($environment) and isset($this->_environment) ) {
   $environment = $this->_environment;
}
if ( !isset($translator) and isset($this->_translator) ) {
   $translator = $this->_translator;
}

   if ( !$environment->inServer() and !$environment->inPrivateRoom()) {
      $link_item = new cs_link();
      $link_item->setDescription($translator->getMessage('ROOM_MEMBER_ADMIN_DESC'));
      $link_item->setIconPath('images/cs_config/ROOM_MEMBER_ADMIN.gif');
      $link_item->setTitle($translator->getMessage('ROOM_MEMBER_ADMIN'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('account');
      $link_item->setFunction('index');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }


   if ( !$environment->inServer() and !$environment->inPrivateRoom() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_MAIL_LINK'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_MAIL_DESC'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_MAIL.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('mail');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( !$environment->inServer() and !$environment->inPrivateRoom() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_AGB'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_AGB_DESC'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_AGB.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('agb');
      $link_item->setParameter('');
      $link_item->setLink($router->generate('commsy_portal_terms', ['roomId' => $environment->getCurrentContextID()]));
      $admin_link_list->add($link_item);
   }

   if ( !$environment->inServer()
        and !$environment->inPrivateRoom()
        and !$environment->inPortal()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('COMMON_INFORMATION_BOX'));
      $link_item->setShortTitle($translator->getMessage('COMMON_INFORMATION_BOX_SHORT'));
      $link_item->setDescription($translator->getMessage('COMMON_INFORMATION_BOX_DESC'));
      $link_item->setIconPath('images/cs_config/COMMON_INFORMATION_BOX.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('informationbox');
      $link_item->setParameter('');
      $admin_link_list->add($link_item);
   }

   if ( !$environment->inServer() and !$environment->inPrivateRoom() and !$environment->inPortal()) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('COMMON_CONFINGURATION_ARCHIVE'));
      $link_item->setShortTitle($translator->getMessage('COMMON_CONFINGURATION_ARCHIVE_SHORT'));
      $link_item->setDescription($translator->getMessage('COMMON_CONFIGURATION_ARCHIVE_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_ARCHIVE.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('archive');
      $link_item->setParameter('');
      $admin_link_list->add($link_item);
   }



   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->isCommunityRoom()
        and $context_item->isOpenForGuests()
        and $context_item->withRubric(CS_MATERIAL_TYPE)
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION'));
      $link_item->setDescription($translator->getMessage('MATERIAL_ADMIN_TINY_DESCRIPTION'));
      $link_item->setIconPath('images/cs_config/MATERIAL_ADMIN_TINY_DESCRIPTION.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('material_admin');
      $link_item->setFunction('index');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_USAGE_INFOS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_USAGE_INFOS_DESC'));
      $link_item->setIconPath('images/cs_config/PREFERENCES_USAGE_INFOS.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('usageinfo');
      $link_item->setParameter('');
      $admin_link_list->add($link_item);
   }


   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PORTAL_NEWS_LINK'));
      $link_item->setIconPath('images/cs_config/SERVER_NEWS_LINK.gif');
      $link_item->setDescription($translator->getMessage('PORTAL_NEWS_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('news');
      $link_item->setParameter(array());
      $link_item->setLink($router->generate('commsy_portal_announcements', ['roomId' => $environment->getCurrentContextID()]));
      $admin_link_list->add($link_item);
   }

   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PORTAL_HELP_LINK'));
      $link_item->setIconPath('images/cs_config/PORTAL_HELP_LINK.gif');
      $link_item->setDescription($translator->getMessage('PORTAL_HELP_LINK_DESC'));
      $link_item->setLink($router->generate('commsy_portal_help', ['roomId' => $environment->getCurrentContextID()]));
      $admin_link_list->add($link_item);
   }

   if ( $environment->inPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PORTAL_AUTOACCOUNTS_LINK'));
      $link_item->setIconPath('images/cs_config/SERVER_AUTOACCOUNTS_LINK.gif');
      $link_item->setDescription($translator->getMessage('PORTAL_AUTOACCOUNTS_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('autoaccounts');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('SERVER_NEWS_LINK'));
      $link_item->setIconPath('images/cs_config/SERVER_NEWS_LINK.gif');
      $link_item->setDescription($translator->getMessage('SERVER_NEWS_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('news');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_EXTRA_FORM_HEADLINE'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_EXTRA_FORM.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_EXTRA_FORM_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('extra');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('SERVER_STATISTIC_LINK'));
      $link_item->setIconPath('images/cs_config/SERVER_STATISTIC_LINK.gif');
      $link_item->setDescription($translator->getMessage('SERVER_STATISTIC_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('statistic');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_OUTOFSERVICE'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_OUTOFSERVICE_DESC'));
      $link_item->setIconPath('images/cs_config/SERVER_OUTOFSERVICE.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('outofservice');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   if ( $environment->inServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_UPDATE_LINK'));
      $link_item->setDescription($translator->getMessage('CONFIGURATION_UPDATE_DESC'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/update.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/update.png');
      }
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('update');
      $link_item->setParameter(array());
      $admin_link_list->add($link_item);
   }

   $admin_link_list->sortby('title');
?>