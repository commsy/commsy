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

/** upper class of the label item
 */
include_once('classes/cs_label_item.php');
include_once('functions/text_functions.php');

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 *
 * @author CommSy Development Group
 */
class cs_time_item extends cs_label_item {

   /**
    * string - containing the context of the time label (school or uni)
    */
   var $_context;

   /** constructor: cs_label_item
    * the only available constructor, initial values for internal variables
    *
    * @param string label_type type of the label
    *
    * @author CommSy Development Group
    */
   function __construct($environment) {
      cs_label_item::__construct($environment, 'time');
   }
   
   /** sets the data of the item.
    *
    * @param $data_array
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      // not yet implemented
      $this->_data = $data_array;
      if (isset($data_array['name'])) {
         $this->_data['sorting'] = $data_array['name'];
      }
      return $this->isValid();
   }

   /** get sorting field content
    * this method returns the data in the sorting field
    *
    * @return string content of the sorting field
    *
    * @author CommSy Development Group
    */
   function getSortingFieldContent () {
      return $this->_getValue('sorting');
   }
}
?>