<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz
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

class cs_plugin {

   public $_environment    = NULL;
   public $_translator     = NULL;
   public $_identifier     = ''; // must be the same as in etc/commsy/plugin.php
   public $_title          = '';
   public $_text_converter = NULL;

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      $this->_environment = $environment;
      $this->_translator = $environment->getTranslationObject();
      $this->_text_converter = $environment->getTextConverter();
   }

   public function inStatistics () {
      return false;
   }

   public function isRubricPlugin () {
      return false;
   }

   public function isExtraPlugin () {
      return false;
   }

   public function inPrivateRoom () {
      return false;
   }

   public function inProjectRoom () {
      return false;
   }

   public function inCommunityRoom () {
      return false;
   }

   public function inGroupRoom () {
      return false;
   }

   public function getTitle () {
      if ( !empty($this->_title) ) {
         return $this->_title;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('title is empty, please define $this->_title = "TITLE" in your plugin class',E_USER_ERROR);
      }
   }

   public function getIdentifier () {
      if ( !empty($this->_identifier) ) {
         return $this->_identifier;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('title is empty, please define $this->_identifier = "IDENTIFIER" in your plugin class',E_USER_ERROR);
      }
   }

   public function isConfigurableInServer () {
      return false;
   }
   
   public function isConfigurableInPortal () {
      return false;
   }

   public function isConfigurableInRoom ( $room_type = '' ) {
      return false;
   }

   public function _getConfigValueFor ( $option ) {
      $retour = '';
      $identifier = $this->getIdentifier();
      if ( !empty($option)
           and !empty($identifier)
         ) {
         $current_context_item = $this->_environment->getCurrentContextItem();
         $config_array = $current_context_item->getPluginConfigForPlugin($identifier);
         if ( empty($config_array[$option])
              and !$current_context_item->isPortal()
            ) {
            $current_context_item = $this->_environment->getCurrentPortalItem();
            if ( !empty($current_context_item) ) {            
               $config_array = $current_context_item->getPluginConfigForPlugin($identifier);
            }
         }
         if ( !empty($config_array)
              and !empty($config_array[$option])
            ) {
            $retour = $config_array[$option];
         }
      }
      return $retour;
   }
}
?>