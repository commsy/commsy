<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2008 Iver Jackewitz
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

class cs_class_factory {

   private $_class_folder = NULL;
   private $_design_folder = NULL;
   private $_class_loaded_array = array();

   public function __construct () {
       $class_config = array();
       include('etc/config_classes.php');
       $this->_class_array = $class_config;
   }

   public function getClass ( $name, $params = array() ) {
      if ( !isset($this->_class_loaded_array[$name]) ) {
         $this->includeClass($name);
         $this->_class_loaded_array[$name] = true;
      }
      return new $this->_class_array[$name]['name']($params);
   }

   public function includeClass ( $name ) {
      if ( !empty($this->_class_array[$name]['switchable'])
           and $this->_class_array[$name]['switchable']
           and !empty($this->_design_folder)
           and !empty($this->_class_array[$name]['folder'])
           and !mb_stristr($this->_class_array[$name]['folder'],'/'.$this->_design_folder.'/')
         ) {
         $this->_class_array[$name]['folder'] .= $this->_design_folder.'/';
      }
      if ( empty($this->_class_array[$name]['folder']) ) {
         trigger_error('don\'t know where class '.$name.' is',E_USER_ERROR);
      } elseif ( empty($this->_class_array[$name]['filename']) ) {
         trigger_error('don\'t know the filename of '.$name,E_USER_ERROR);
      } elseif ( !file_exists(realpath(dirname(__FILE__)) . '/../' . $this->_class_array[$name]['folder'].$this->_class_array[$name]['filename']) ) {
         trigger_error('file '.$this->_class_array[$name]['folder'].$this->_class_array[$name]['filename'].' does not exist',E_USER_ERROR);
      } else {
         include_once(realpath(dirname(__FILE__)) . '/../' . $this->_class_array[$name]['folder'].$this->_class_array[$name]['filename']);
      }
   }

   public function setDesignTo6 () {
      $this->_design_folder = 6;
   }
}
?>