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

   $configuration_rubric_extras_link_list = new cs_list();

   global $c_html_textarea;
   if ( $c_html_textarea ) {
      $link_item = new cs_link();
      $link_item->setTitle(getMessage('CONFIGURATION_TEXTAREA_TITLE'));
      $link_item->setDescription(getMessage('CONFIGURATION_TEXTAREA_TITLE_DESC'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/htmltextarea.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/htmltextarea.png');
      }
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('htmltextarea');
      $link_item->setParameter(array());
      $configuration_rubric_extras_link_list->add($link_item);
   }

   #########################################
   # Wiki - Raum-Wiki
   #########################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withWikiFunctions() and !$context_item->isServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle(getMessage('WIKI_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/pmwiki.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/pmwiki.png');
      }
      $link_item->setDescription(getMessage('WIKI_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('wiki');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }

   ############################################
   # Chat
   ############################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withChatLink() and !$context_item->isPortal() and !$context_item->isPrivateroom() and !$context_item->isGrouproom()) {
      $link_item = new cs_link();
      $link_item->setTitle(getMessage('CHAT_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/etchat.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/etchat.png');
      }
      $link_item->setDescription(getMessage('CHAT_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('chat');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }

   if ( !$environment->inServer()
        #and !$environment->inPrivateRoom()
        and !$environment->inPortal()
        and !$context_item->isGrouproom()
      ) {
      $link_item = new cs_link();
      if ( $environment->inPrivateRoom() ) {
         $link_item->setTitle(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'));
         $link_item->setShortTitle(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'));
         $link_item->setDescription(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE2'));
      } else {
         $link_item->setTitle(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE'));
         $link_item->setShortTitle(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE'));
         $link_item->setDescription(getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE'));
      }
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/template_options.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/template_options.png');
      }
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('template_options');
      $link_item->setParameter('');
      $configuration_rubric_extras_link_list->add($link_item);
   }

   $link_item = new cs_link();
   $link_item->setTitle(getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE'));
   $current_context = $environment->getCurrentContextItem();
   if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
      $link_item->setIconPath('images/commsyicons_msie6/48x48/config/rubric_extras.gif');
   } else {
      $link_item->setIconPath('images/commsyicons/48x48/config/rubric_extras.png');
   }
   $link_item->setDescription(getMessage('CONFIGURATION_RUBRIC_EXTRAS_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('rubric_extras');
   $link_item->setParameter(array());
   $configuration_rubric_extras_link_list->add($link_item);
?>