<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez, Johannes Schultze
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
if ( !isset($translator) and isset($this->_translator) ) {
   $translator = $this->_translator;
}
   $addon_link_list = new cs_list();
   $context_item = $environment->getCurrentContextItem();
   $link_item = new cs_link();
   $link_item->setTitle($translator->getMessage('CONFIGURATION_SERVICE_LINK'));
   $link_item->setIconPath('images/cs_config/CONFIGURATION_SERVICE.gif');
   $link_item->setDescription($translator->getMessage('CONFIGURATION_SERVICE_DESC'));
   $link_item->setContextID($environment->getCurrentContextID());
   $link_item->setModule('configuration');
   $link_item->setFunction('service');
   #$link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
   $addon_link_list->add($link_item);

   if ( $environment->inServer() && $environment->isCurlForPHPAvailable()) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_SCRIBD_LINK'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_SCRIBD_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_SCRIBD_LINK_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('scribd');
      $link_item->setParameter(array());
      $addon_link_list->add($link_item);
   }
   
   $contextItem = $environment->getCurrentContextItem();
   if ( $context_item->withLimesurveyFunctions() && $contextItem->isPortal() )
   {
   		$link_item = new cs_link();
   		$link_item->setTitle($translator->getMessage('LIMESURVEY_CONFIGURATION_LINK'));
   		$link_item->setIconPath('images/cs_config/LIMESURVEY_CONFIGURATION_IMAGE.gif');
   		$link_item->setDescription($translator->getMessage('LIMESURVEY_CONFIGURATION_DESC'));
   		$link_item->setContextID($environment->getCurrentContextID());
   		$link_item->setModule('configuration');
   		$link_item->setFunction('limesurvey');
   		$addon_link_list->add($link_item);
   }

   #########################################
   # Wiki - Raum-Wiki
   #########################################

   /* $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withWikiFunctions() and !$context_item->isServer() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('WIKI_CONFIGURATION_LINK'));
      $link_item->setIconPath('images/cs_config/WIKI_CONFIGURATION_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('WIKI_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('wiki');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $addon_link_list->add($link_item);
   } */

   ############################################
   # Chat
   ############################################

   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->withChatLink() and !$context_item->isPortal() ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CHAT_CONFIGURATION_LINK'));
      $link_item->setIconPath('images/cs_config/CHAT_CONFIGURATION_IMAGE.gif');
      $link_item->setDescription($translator->getMessage('CHAT_CONFIGURATION_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('chat');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $addon_link_list->add($link_item);
   }

   #############################################
   # plugins - active and deactivate plugins
   #############################################
   $context_item = $environment->getCurrentContextItem();
   global $c_plugin_array;
   if ( !empty($c_plugin_array)
        and (
        		  $context_item->isPortal()
        		  or $context_item->isServer()
        		)
      ) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_PLUGIN_LINK'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_EXTRA_FORM.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_PLUGIN_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('plugins');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $addon_link_list->add($link_item);
   }
   
   ################################################
   # Datenschutz data security Server
   ###############################################
   
   if($context_item->isPortal()){
	   $link_item = new cs_link();
	   $link_item->setTitle($translator->getMessage('CONFIGURATION_DATA_SECURITY'));
	   $link_item->setIconPath('images/cs_config/CONFIGURATION_DATASECURITY_PREFERENCES.gif');
	   $link_item->setDescription($translator->getMessage('CONFIGURATION_DATA_SECURITY_DESC_2'));
	   $link_item->setContextID($environment->getCurrentContextID());
	   $link_item->setModule('configuration');
	   $link_item->setFunction('datasecurity');
	   #$link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
	   $addon_link_list->add($link_item);
   }
   
   ###############################################
   # Datenschutz data security Server
   ###############################################
   if($context_item->isServer()){
	   $link_item = new cs_link();
	   $link_item->setTitle($translator->getMessage('CONFIGURATION_LOG_DATA'));
	   $link_item->setIconPath('images/cs_config/PREFERENCES_LISTVIEWS_CONFIGURATION.gif');
	   $link_item->setDescription($translator->getMessage('CONFIGURATION_DATA_SECURITY_DESC'));
	   $link_item->setContextID($environment->getCurrentContextID());
	   $link_item->setModule('configuration');
	   $link_item->setFunction('datasecurity');
	   #$link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
	   $addon_link_list->add($link_item);
   }
   ###############################################
   # delete inactive user
   ###############################################
   if($context_item->isPortal()){
   	$link_item = new cs_link();
   	$link_item->setTitle($translator->getMessage('CONFIGURATION_DELETE_INACTIVE_USER'));
   	$link_item->setIconPath('images/cs_config/SERVER_AUTOACCOUNTS_LINK.gif');
   	$link_item->setDescription($translator->getMessage('CONFIGURATION_DELETE_INACTIVE_USER_DESC'));
   	$link_item->setContextID($environment->getCurrentContextID());
   	$link_item->setModule('configuration');
   	$link_item->setFunction('inactive');
   	#$link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
   	$addon_link_list->add($link_item);
   }
   ################################################
   # plugins - special configuration of one plugin
   ################################################
   global $c_plugin_array;
   if ( isset($c_plugin_array) and !empty($c_plugin_array) ) {
      foreach ($c_plugin_array as $plugin) {
         $plugin_class = $environment->getPluginClass($plugin);
         if ( method_exists($plugin_class,'getLinkItemForConfigurationIndex') ) {
            $link_item = $plugin_class->getLinkItemForConfigurationIndex();
            if ( isset($link_item) ) {
               $addon_link_list->add($link_item);
            }
         }
      }
   }
   
   ###############################################
   # connection to other commsys
   ###############################################
   if ( $context_item->isServer() ) {
   	$link_item = new cs_link();
   	$link_item->setTitle($translator->getMessage('CONFIGURATION_CONNECTION'));
   	$link_item->setIconPath('images/cs_config/PORTAL_ENTER_NEW.gif');
   	$link_item->setDescription($translator->getMessage('CONFIGURATION_CONNECTION_DESC'));
   	$link_item->setContextID($environment->getCurrentContextID());
   	$link_item->setModule('configuration');
   	$link_item->setFunction('connection');
   	$addon_link_list->add($link_item);
   }

   #############################################
   # export import
   #############################################
   $context_item = $environment->getCurrentContextItem();
   if ( $context_item->isPortal()) {
      $link_item = new cs_link();
      $link_item->setTitle($translator->getMessage('CONFIGURATION_EXPORT_IMPORT_LINK'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_EXTRA_FORM.gif');
      $link_item->setDescription($translator->getMessage('CONFIGURATION_EXPORT_IMPORT_DESC'));
      $link_item->setContextID($environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('export_import');
      $link_item->setParameter(array('iid' => $environment->getCurrentContextID()));
      $addon_link_list->add($link_item);
   }

   if ( $addon_link_list->getFirst() ){
      $addon_link_list->sortby('title');
   }
?>
