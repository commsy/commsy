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

/** class for arraylists: needs for cs_form
 * this class implements a list of arrays. In this class an array is one element if the list.
 */
class cs_array_list {

   /**
    * array containing the elements
    */
   var $_elements;

   /**
    * integer containing the number of elements
    */
   var $_numberOfElements;

   /**
    * integer containing the poniter of the intenal iterator
    */
   var $_currentElement;

   /** constructor: cs_array_list
    * the only available constructor, initial values for internal variables
    *
    * @author CommSy Development Group
    */
   function __construct() {
      $this->reset();
   }

   /** reset internal variables
    *
    * this method resets the internal variables
    *
    * @author CommSy Development Group
    */
   function reset () {
      $this->_elements = array();
      $this->_numberOfElements = 0;
      $this->_currentElement = 0;
   }

   /** gets number of elements
    * this method returns the number of elements
    *
    * @return integer an integer with the number of elements in the form
    *
    * @author CommSy Development Group
    */
   function numberOfElements() {
      return $this->_numberOfElements;
   }

   /** is list empty ?
    * this method returns a boolean expressing if the list has no elements
    *
    * @return boolean   true if the form has no elements
    *
    * @author CommSy Development Group
    */
   function isEmpty() {
      return ($this->numberOfElements() == 0);
   }

   /** is current element valid ?
    * this method returns a boolean expressing if the element the internal interator is on exists
    *
    * @return boolean   true - element exists
    *                   false - element does not exist = end of list
    *
    * @author CommSy Development Group
    */
   function isCurrentValid() {
      return ( isset( $this->_elements[$this->_currentElement] ) );
   }

   /** reset cursor
    * this method sets the internal iterator to the first element
    *
    * @author CommSy Development Group
    */
   function resetCursor() {
      $this->_currentElement = 0;
   }

   /** get current element
    * this method returns the current element from the internal array
    *
    * @return array an array with the information about the current element
    *
    * @author CommSy Development Group
    */
   function getCurrent() {
      if (isset($this->_elements[$this->_currentElement])) {
         $current = $this->_elements[$this->_currentElement];
         $current['id'] = $this->_currentElement;
      } else {
         $current = NULL;
      }
      return $current;
   }

   /** move to next element
    * this method moves to the next element of the list
    *
    * @author CommSy Development Group
    */
   function moveNext() {
      $this->_currentElement++;
   }

   /** drop last element
    * this method drops the last element of the list (array_pop)
    *
    * @return array last element of this array list
    *
    * @author CommSy Development Group
    */
   function getLastAndDropIt () {
      $last_position = $this->numberOfElements()-1;
      $retour = $this->_elements[$last_position];
      unset($this->_elements[$last_position]);
      $this->_numberOfElements = $this->_numberOfElements -1;
      return $retour;
   }

   /** add an element
    * this method adds a new element to the array of table rows
    *
    * @param array array element of the array-list
    *
    * @author CommSy Development Group
    */
   function addElement( $array ) {
      $this->_elements[$this->numberOfElements()] = $array;
      $this->_numberOfElements = $this->_numberOfElements + 1;
   }

   /** replace an element
    * this method adds a new element to the array of table rows and replace an older element
    *
    * @param array array element of the array-list
    */
   function replaceElement( $array ) {
	  if ( isset($array['id']) ) {
         $this->_elements[$array['id']] = $array;
	  }
   }

   /** get an element
    * this function retrieves an already stored element the element must be named !!!
    *
    * @param string name name of an element of the array-list
    *
    * @return array element of the list with named "$name"
    *
    * @author CommSy Development Group
    */
   function getElement( $name ) {
      reset( $this->_elements );
      $id = 0;
      while ( isset( $this->_elements[$id] ) ) {
         $current = $this->_elements[$id];
         if ( $current['name'] == $name ) {
            $current['id'] = $id;
            return $current;
         }
         $id++;
      }

      return;
   }

   /** get an element array
    * this function retrieves already stored elements - the element must be named !!!
    *
    * @param string name name of elements of the array-list
    *
    * @return array elements of the list with named "$name"
    *
    * @author CommSy Development Group
    */
   function getElements( $name ) {
      $result = array();
      reset( $this->_elements );
      $id = 0;
      while ( isset( $this->_elements[$id] ) ) {
         $current = $this->_elements[$id];
         if ( $current['name'] == $name ) {
            $current['id'] = $id;
            $result[] = $current;
         }
         $id++;
      }

      return $result;
   }

   /** get first element
    * this method returns the first element from the internal array
    *
    * @return array an array with the information about the first element
    *
    * @author CommSy Development Group
    */
   function getFirst () {
      $this->resetCursor();
      return $this->getCurrent();
   }

   /** get next element
    * this method returns the next element from the internal array
    *
    * @return array an array with the information about the next element
    *
    * @author CommSy Development Group
    */
   function getNext () {
      $this->moveNext();
      return $this->getCurrent();
   }
}
?>