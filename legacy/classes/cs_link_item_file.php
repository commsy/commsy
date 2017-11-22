<?PHP
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

/** upper class of the link item
 */
include_once('classes/cs_item.php');

/** class for a CommSy item: link
 * this class implements a link item
 */
class cs_link_item_file extends cs_item {

   /** constructor
    *
    *
    * @author CommSy Development Group
    */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'link_item_file';
   }

   /************** set methods*************************/

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
   }

   public function getLinkedItemID () {
      $retour = '';
      if ( !empty($this->_data['item_iid']) ) {
         $retour = $this->_data['item_iid'];
      }
      return $retour;
   }

   public function getLinkedItem () {
      $retour = NULL;
      $item_id = $this->getLinkedItemID();
      if ( !empty($item_id) ) {
         $item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($item_id);
         $manager = $this->_environment->getManager($item_type);
         if ( isset($manager) ) {
            $retour = $manager->getItem($item_id);
         }
         unset($manager);
         unset($item_manager);
      }
      return $retour;
   }
}
?>