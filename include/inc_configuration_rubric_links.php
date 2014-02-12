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
   
  $current_context = $environment->getCurrentContextItem();

   $rubric_link_list = new cs_list();

   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_DATE_CONFIGURATIONS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_DATE_CONFIGURATIONS_DESC'));
      $link_item->setIconPath('images/cs_config/COMMON_DATE_PREFERENCES.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('dates');
      $link_item->setParameter('');
      $rubric_link_list->add($link_item);
   }

   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_DISCUSSION_CONFIGURATIONS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_DISCUSSION_CONFIGURATIONS_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_DISCUSSION_PREFERENCES.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('discussion');
      $link_item->setParameter('');
      $rubric_link_list->add($link_item);
   }

   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inGroupRoom()
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('PREFERENCES_PATH_CONFIGURATIONS'));
      $link_item->setDescription($translator->getMessage('PREFERENCES_PATH_CONFIGURATIONS_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_PATH.gif');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('path');
      $link_item->setParameter('');
      $rubric_link_list->add($link_item);
   }

   if ( $environment->inProjectroom()
        and $current_context->showGrouproomConfig()
        and $current_context->withRubric(CS_GROUP_TYPE)
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_GROUPROOM_LINK'));
      $link_item->setIconPath('images/cs_config/PORTAL_ENTER_NEW.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_GROUPROOM_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('grouproom');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $rubric_link_list->add($link_item);
   }
   $rubric_link_list->sortby('title');
?>