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

   $configuration_rubric_extras_link_list = new cs_list();

   #########################################
   # Wiki - Raum-Wiki
   #########################################
   $context_item = $environment->getCurrentContextItem();
   /* if ( $context_item->withWikiFunctions() and !$context_item->isServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('WIKI_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/pmwiki.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/pmwiki.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/pmwiki.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/pmwiki.png');
      }
      $link_item->setDescription($translator->getMessage('WIKI_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('wiki');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   } */

   #########################################
   # Wordpress - Raum-Wordpress
   #########################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withWordpressFunctions() and !$context_item->isServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('WORDPRESS_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/wordpress.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/wordpress.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/wordpress.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/wordpress.png');
      }
      $link_item->setDescription($translator->getMessage('WORDPRESS_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('wordpress');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }
   
   
   ############################################
   # Chat
   ############################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withChatLink() and !$context_item->isPortal() and !$context_item->isPrivateroom() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CHAT_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/etchat.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/etchat.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/etchat.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/etchat.png');
      }
      $link_item->setDescription($translator->getMessage('CHAT_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('chat');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }

   #########################################
   # Workflow
   #########################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withWorkflowFunctions() and !$context_item->isServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('WORKFLOW_CONFIGURATION_LINK'));
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/workflow.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/workflow.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/workflow.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/workflow.png');
      }
      $link_item->setDescription($translator->getMessage('WORKFLOW_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('workflow');
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
         $link_item->setTitle($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'));
         $link_item->setShortTitle($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'));
         $link_item->setDescription($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE2'));
      } else {
         $link_item->setTitle($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE'));
         $link_item->setShortTitle($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE'));
         $link_item->setDescription($translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE'));
      }
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/template_options.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/template_options.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/template_options.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/template_options.png');
      }
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('template_options');
      $link_item->setParameter('');
      $configuration_rubric_extras_link_list->add($link_item);
   }

   $show_option = true;
   if ( $environment->inPrivateRoom() ) {
      $show_option = false;
   }
   if ($show_option) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE'));
      $current_context = $environment->getCurrentContextItem();
      if(($environment->getCurrentBrowser() == 'MSIE') && (mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6')){
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/rubric_extras.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/rubric_extras.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/rubric_extras.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/rubric_extras.png');
      }
      $link_item->setDescription($translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('rubric_extras');
      $link_item->setParameter(array());
      $configuration_rubric_extras_link_list->add($link_item);
   }
   unset($show_option);

   $show_option = true;
   if ( $environment->inPrivateRoom() ) {
      $show_option = false;
   }
   if ($show_option) {
      $context_item = $environment->getCurrentContextItem();
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_SERVICE_LINK'));
      if ( ( $environment->getCurrentBrowser() == 'MSIE' )
           and ( mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6' )
         ) {
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/service.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/service.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/service.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/service.png');
      }
      $link_item->setDescription($translator->getMessage('CONFIGURATION_SERVICE_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('service');
      #$link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }

#############################################
# plugins - active and deactivate plugins
#############################################
global $c_plugin_array;
if ( !empty($c_plugin_array) ) {
   $show_link = false;

   $current_portal = $environment->getCurrentPortalItem();
   if ( isset($current_portal) ) {
      foreach ( $c_plugin_array as $plugin) {
         if ( $current_portal->isPluginOn($plugin) ) {
            $plugin_class = $environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,'isConfigurableInRoom') ) {
               $current_context_item = $environment->getCurrentContextItem();
               if ($plugin_class->isConfigurableInRoom($current_context_item->getItemType())) {
                  $show_link = true;
                  break;
               }
            }
         }
      }
   }
   if ($show_link) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_PLUGIN_LINK'));
      if ( ( $environment->getCurrentBrowser() == 'MSIE' )
           and ( mb_substr($environment->getCurrentBrowserVersion(),0,1) == '6' )
         ) {
         $link_item->setIconPath('images/commsyicons_msie6/48x48/config/plugin.gif');
         $link_item->setIconPathForNavigation('images/commsyicons_msie6/22x22/config/plugin.gif');
      } else {
         $link_item->setIconPath('images/commsyicons/48x48/config/plugin.png');
         $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/plugin.png');
      }
      $link_item->setDescription($translator->getMessage('CONFIGURATION_PLUGIN_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('plugins');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $configuration_rubric_extras_link_list->add($link_item);
   }
}

   ################################################
   # media integration
   ################################################
   global $c_media_integration;
   // !!!!
   // NOTE: when opening media integration for all room contexts, make sure that mediadistribution online is only accessable for community rooms(see cs_configuration_mediaintegration_form)
   // this restriction should always be implemented
   // test it
   // remove page protection in configuration_mediaintegration.php
   // !!!!
   if(  $environment->inCommunityRoom() &&
        isset($c_media_integration) &&
        (
          $c_media_integration === true ||                                                                            // activ in all community rooms
          (
            is_array($c_media_integration) && in_array($environment->getCurrentContextID(), $c_media_integration)     // restricted community rooms
          )
        )
     ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_MEDIA_INTEGRATION'));
      $link_item->setDescription($translator->getMessage('CONFIGURATION_MEDIA_INTEGRATION_DESC'));
      $link_item->setIconPath('images/commsyicons/48x48/config/video.png');
      $link_item->setIconPathForNavigation('images/commsyicons/22x22/config/video.png');
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('mediaintegration');
      $link_item->setParameter(array());
      $configuration_rubric_extras_link_list->add($link_item);
      unset($link_item);
   }
  
?>