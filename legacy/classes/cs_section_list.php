<?php
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

class cs_section_list {
   
  
    /**
    * string - containing the type of the list resp. the type of the elements
    */
   var $_type;

   /**
    * array - containing the elements of the list
    */
   var $_data = array();

   /** constructor: cs_list
    * the only available constructor, initial values for internal variables
    *
    * @author CommSy Development Group
    */
   function __construct() {
      $this->_type = 'section_list';
   }

   /** is the type of the list = $type ?
    * this method returns a boolean expressing if type of the list is $type or not
    *
    * @param string type string to compare with type of list (_type)
    *
    * @return boolean   true - type of this list is $type
    *                   false - type of this list is not $type
    *
    * @author CommSy Development Group
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** reset internal variables
    *
    * this method resets the list
    *
    * @author CommSy Development Group
    */
   function reset () {
      reset($this->_data);
   }
   
   /** reset internal cursor
    *
    * this method resets the cursor
    *
    * @author CommSy Development Group
    */
   function resetCursor () {
      reset($this->_data);
   }

   /** get next element
    * this method returns the next element from the internal array
    *
    * @return object cs_item returns an object with the information about the next element
    *
    * @author CommSy Development Group
    */
   function getNext () {
      return next($this->_data);
   }

   /** get first element
    * this method returns the first element from the internal array
    *
    * @return object cs_item an commsy item with the information about the first element
    *
    * @author CommSy Development Group
    */
   function getFirst () {
      $this->reset();
      return current($this->_data);
   }

   /** get last element
    * this method returns the last element from the internal array
    *
    * @return object cs_item an commsy item with the information about the last element
    */
   function getLast () {
      return end($this->_data);
   }

   function append($section) {
      $count = count($this->_data);
#      include_once('functions/error_functions.php');trigger_error("count ergibt ".$count, E_USER_WARNING);
      $pos = $count+1; 
      if($section->getNumber() != $pos) {
         $section->setNumber($pos);
      }
      $this->_data[$pos] = $section;
      ksort($this->_data);
   }

   function set($section) {
      $counter = 1;
      $tmp_array = array();
      // if the section already exists in the array, resort array without section
      $section_id = $section->getItemID();
      if(!empty($section_id)) {
         foreach($this->_data as $section_item) {
            if($section_item->getItemID() != $section->getItemID()) {
               $section_item->setNumber($counter);
               $tmp_array[$section_item->getNumber()] = $section_item;
               $counter++;
            }
         }
      } else {
         $tmp_array = $this->_data;
      }
      // resort the sections ...
      foreach($tmp_array as $section_item) {
         if($section_item->getNumber() >= $section->getNumber()) {
            $section_item->setNumber($section_item->getNumber()+1);
         }
         $new_array[$section_item->getNumber()] = $section_item;
      }
      //...and put the new one in place
      $new_array[$section->getNumber()] = $section;
      $this->_data = $new_array;
      ksort($this->_data);
   }
   
   function remove($pos) {
      $counter = 1;
      $tmp_array = array();
      // resort array without section where number==$pos
      foreach($this->_data as $section_item) {
         if($section_item->getNumber() != $pos) {
            $section_item->setNumber($counter);
            $tmp_array[$section_item->getNumber()] = $section_item;
            $counter++;
         }
      }
      $this->_data = $tmp_array;
   }
   
   function get($pos) {
      return $this->_data[$pos];
   }
   
   function getCount() {
      return count($this->_data);
   }

   /** is list empty
    * this method returns a boolean: true if list is empty
    *
    * @return boolean list empty?
    */
   function isEmpty () {
      $retour = true;
      if ($this->getCount() > 0) {
         $retour = false;
      }
      return $retour;
   }

   /** is list not empty
    * this method returns a boolean: true if list is not empty
    *
    * @return boolean list not empty?
    */
   function isNotEmpty () {
      return !$this->isEmpty();
   }
   
   function to_array () {
      return $this->_data;
   }
}
?>