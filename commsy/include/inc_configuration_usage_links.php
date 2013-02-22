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

$usage_link_list = new cs_list();

if ( !isset($environment) and isset($this->_environment) ) {
   $environment = $this->_environment;
}

$translator = $environment->getTranslationObject();

if ( !$environment->inServer() and !$environment->inPrivateRoom()) {
   $link_item = new cs_link();
   $link_item->setDescription($translator->getMessage('ROOM_MEMBER_ADMIN_DESC'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/account.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/account.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/account.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/account.png');
   }
   $link_item->setTitle($translator->getMessage('ROOM_MEMBER_ADMIN'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('account');
   $link_item->setFunction('index');
   $link_item->setParameter(array());
   $usage_link_list->add($link_item);
}

if ( !$environment->inServer()
     and !$environment->inPortal()
   ) {
   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('COMMON_INFORMATION_BOX'));
   $link_item->setShortTitle($translator->getMessage('COMMON_INFORMATION_BOX_SHORT'));
   $link_item->setDescription($translator->getMessage('COMMON_INFORMATION_BOX_DESC'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/informationbox.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/informationbox.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/informationbox.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/informationbox.png');
   }
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('informationbox');
   $link_item->setParameter('');
   $usage_link_list->add($link_item);
}

$context_item = $environment->getCurrentContextItem();
if ( $context_item->isCommunityRoom()
     and $context_item->isOpenForGuests()
     and $context_item->withRubric(CS_MATERIAL_TYPE)
   ) {
   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION'));
   $link_item->setDescription($translator->getMessage('MATERIAL_ADMIN_TINY_DESCRIPTION'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/material_admin.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/material_admin.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/material_admin.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/material_admin.png');
   }
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('material_admin');
   $link_item->setFunction('index');
   $link_item->setParameter(array());
   $usage_link_list->add($link_item);
}

if ( $environment->inProjectRoom()
     or $environment->inCommunityRoom()
     or $environment->inPrivateRoom()
     or $environment->inGroupRoom()
   ) {
   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('PREFERENCES_USAGE_INFOS'));
   $link_item->setDescription($translator->getMessage('PREFERENCES_USAGE_INFOS_DESC'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/usage_info_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/usage_info_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/usage_info_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/usage_info_options.png');
   }
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('usageinfo');
   $link_item->setParameter('');
   $usage_link_list->add($link_item);
}

if ( !$environment->inServer() and !$environment->inPrivateRoom() ) {
   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('PREFERENCES_MAIL_LINK'));
   $link_item->setDescription($translator->getMessage('PREFERENCES_MAIL_DESC'));
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/mail_options.gif');
      $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/mail_options.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/mail_options.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/mail_options.png');
   }
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('mail');
   $link_item->setParameter(array());
   $usage_link_list->add($link_item);
}
?>