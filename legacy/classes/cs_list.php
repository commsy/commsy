<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** class for lists of commsy items (objects)
 * this class implements a list of ojects. An object is a commsy item
 */
class cs_list implements IteratorAggregate {

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
    */
   function __construct() {
      $this->_type = 'list';
   }

   /** is the type of the list = $type ?
    * this method returns a boolean expressing if type of the list is $type or not
    *
    * @param string type string to compare with type of list (_type)
    *
    * @return boolean   true - type of this list is $type
    *                   false - type of this list is not $type
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** reset internal variables
    *
    * this method resets the list
    */
   function reset () {
      reset($this->_data);
   }

   /** reset internal cursor
    *
    * this method resets the cursor
    */
   function resetCursor () {
      reset($this->_data);
   }

   /** get next element
    * this method returns the next element from the internal array
    *
    * @return object cs_item returns an object with the information about the next element
    */
   function getNext () {
      return next($this->_data);
   }

   /** add an element
    * this method adds a new element to the list
    *
    * @param object cs_item a commsy item (object)
    */
   function add ($item) {
      $this->_data[] = $item;
   }

   /** get first element
    * this method returns the first element from the internal array
    *
    * @return object cs_item an commsy item with the information about the first element
    */
   function getFirst () {
      $this->reset();
      return current($this->_data);
   }

   function getSubList($position, $length){
      $sub_list = new cs_list();
      $sub_data_array = array_slice($this->_data,$position,$length);
      foreach($sub_data_array as $sub_data_item){
         $sub_list->add($sub_data_item);
      }
      return $sub_list;
   }

   /** get last element
    * this method returns the last element from the internal array
    *
    * @return object cs_item an commsy item with the information about the last element
    */
   function getLast () {
      return end($this->_data);
   }


   /** add a list of commsy items to this list
    * this method adds a list of commsy items to this list, like array_merge
    *
    * @param object cs_list a list of commsy items (object)
    */
   function addList ($list) {
      // performance ??? (TBD)
      $item = $list->getFirst();
      while($item) {
         $this->add($item);
         $item = $list->getNext();
      }
   }

   /** count list
    * this method returns the number of elements
    *
    * @return integer number of elements within the list
    */
   function getCount () {
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

   private function _translateUmlaute ( $value ) {
      $value = str_replace('Ä','Azzz',$value);
      $value = str_replace('Ö','Ozzz',$value);
      $value = str_replace('Ü','Uzzz',$value);
      $value = str_replace('ä','azzz',$value);
      $value = str_replace('ö','ozzz',$value);
      $value = str_replace('ü','uzzz',$value);
      return $value;
   }

   /** sort list
    * this method sort list by $sort_by
    *
    * @param string sort_by keyword for sorting list
    */
   function sortby ($sort_by) {
      // prepare temp array to sort
      if (count($this->_data) > 1) {
         $old_list = $this->_data;
         $temp_array = array();
         for ($i=0; $i<count($old_list); $i++) {
            $temp_array2['position'] = $i;
            if ($sort_by == 'name') {
               $temp_array2[$sort_by] = $this->_translateUmlaute($old_list[$i]->getName());
            } elseif ($sort_by == 'lastname') {
               $temp_array2[$sort_by] = $this->_translateUmlaute($old_list[$i]->getLastname());
            } elseif ($sort_by == 'modification_date') {
               $temp_array2[$sort_by] = $old_list[$i]->getModificationDate();
            } elseif ($sort_by == 'title') {
               $temp_array2[$sort_by] = $this->_translateUmlaute($old_list[$i]->getTitle());
            } elseif ($sort_by == 'sorting') {
               $temp_array2[$sort_by] = $old_list[$i]->getSortingFieldContent();
            } elseif ($sort_by == 'filename') {
               $temp_array2[$sort_by] = $old_list[$i]->getDisplayName();
            } elseif ($sort_by == 'date') {
               $temp_array2[$sort_by] = $old_list[$i]->getDateTime_start().$old_list[$i]->getDateTime_end();
            } elseif ($sort_by == 'treePosition') {
                $temp_array2[$sort_by] = $old_list[$i]->getPosition();
            } else {
               include_once('functions/error_functions.php');
               trigger_error('Problems sorting list because '.$sort_by.' is not implemented yet.',E_USER_ERROR);
            }
            $temp_array[] = $temp_array2;
         }

         // sort temp aray
         usort($temp_array,create_function('$a,$b','return strnatcasecmp($a[\''.$sort_by.'\'],$b[\''.$sort_by.'\']);'));

         // create sorted list array
         unset($this->_data);
         $this->_data = array();
         for ($i=0; $i<count($temp_array); $i++) {
            $this->_data[$i] = $old_list[$temp_array[$i]['position']];
         }
      }
   }

   /** sort room list by page impressions for $days
    * this method sort list by page impressions $days
    *
    * @param int $days
    */
   function sortbyPageImpressions ($days) {
      // prepare temp array to sort
      if (count($this->_data) > 1) {
         $old_list = $this->_data;
         $temp_array = array();
         for ($i=0; $i<count($old_list); $i++) {
            $temp_array2['position'] = $i;
            $temp_array2[$days] = $old_list[$i]->getPageImpressions($days);
            $temp_array[] = $temp_array2;
         }

         // sort temp aray
         usort($temp_array,create_function('$a,$b','return strnatcasecmp($a[\''.$days.'\'],$b[\''.$days.'\']);'));
         $temp_array = array_reverse($temp_array);

         // create sorted list array
         unset($this->_data);
         $this->_data = array();
         for ($i=0; $i<count($temp_array); $i++) {
            $this->_data[$i] = $old_list[$temp_array[$i]['position']];
         }
      }
   }

   /** reverse list elements
    * this method reverse the list
    */
   function reverse () {
      $this->_data = array_reverse($this->_data);
   }

   /** list unique
    * this method is like array_unique
    */
   function unique () {
      if (count($this->_data) > 1) {
         $a = $this->_data;
         $r = array();
         for ( $i=0; $i<count($a); $i++) {
            if ( !in_array($a[$i], $r) ) {
               $r[] = $a[$i];
            }
         }
         $this->_data = $r;
      }
   }

   function removeElement ($item) {
      foreach($this->_data as $pos => $list_item){
         if ($list_item->getItemID()==$item->getItemID() AND $list_item->getVersionID()==$item->getVersionID()) {
            array_splice($this->_data,$pos,1);
         }
      }
   }

   function getElement ($item) {
      $return_item = new cs_list();
      foreach($this->_data as $pos => $list_item){
         if ($list_item->getItemID()==$item->getItemID() AND $list_item->getVersionID()==$item->getVersionID()) {
            $return_item = $list_item;
         }
      }
      return $return_item;
   }

   function getElementByID ($id) {
      $return_item = new cs_list();
      foreach($this->_data as $pos => $list_item){
         if ($list_item->getItemID() == $id) {
            $return_item = $list_item;
         }
      }
      return $return_item;
   }

   function get($pos) {
      return $this->_data[$pos];
   }


   function inList ($item) {
      $boolean = false;
      if ( isset($item)
           and $item->getItemID() > 0
         ) {
         foreach($this->_data as $pos => $list_item){
            if ($list_item->getItemID()==$item->getItemID() AND $list_item->getVersionID()==$item->getVersionID()) {
               $boolean = true;
               // optimized:
               return true;
            }
         }
      }
      return $boolean;
   }

   function to_array () {
      return $this->_data;
   }

   public function getIDArray () {
      $retour = array();
      $item = $this->getFirst();
      while ( $item ) {
         if ( method_exists($item,'getItemID') ) {
            $retour[] = $item->getItemID();
         }
         $item = $this->getNext();
      }

      return $retour;
   }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->to_array());
    }
}
