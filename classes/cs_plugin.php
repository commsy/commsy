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

   protected $_environment = NULL;
   protected $_translator  = NULL;
   protected $_identifier  = ''; // must be the same as in etc/commsy/plugin.php
   protected $_title       = '';

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      $this->_environment = $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   public function isRubricPlugin () {
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

   public function isConfigurableInPortal () {
      return false;
   }
}
?>