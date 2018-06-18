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
 */
class cs_matrix_item extends cs_label_item {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param string label_type type of the label
    */
   public function __construct ( $environment ) {
      cs_label_item::__construct($environment,CS_MATRIX_TYPE);
   }


   function saveRubricLinksByIDArray($array,$rubric) {
      $links_manager = $this->_environment->getLinkManager();
      $links_manager->saveLinksRubricToMyList($array,$this->getItemID(),$rubric);
   }

   function setIsColumn() {
     $this->_setExtra('MATRIX_TYPE','COLUMN');
   }
   function setIsRow() {
     $this->_setExtra('MATRIX_TYPE','ROW');
   }
   function setCount($int) {
     $this->_setExtra('COUNT',$int);
   }



   function saveLinksByIDArray($array) {
      if ( !empty($array) ) {
         $item_manager = $this->_environment->getItemManager();
         $item_list = $item_manager->getItemList($array);
         if ( isset($item_list)
              and $item_list->isNotEmpty()
            ) {
            $rubric_item_id_array = array();
            $item = $item_list->getFirst();
            while ( $item ) {
               $rubric_item_id_array[$item->getItemType()][] = $item->getItemID();
               unset($item);
               $item = $item_list->getNext();
            }
         }
         unset($item_list);
         unset($item_manager);

         // transfer "label" to real module
         if ( !empty($rubric_item_id_array['label']) ) {
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->setIdArrayLimit($rubric_item_id_array['label']);
            $label_manager->select();
            $label_list = $label_manager->get();
            if ( isset($label_list)
                 and $label_list->isNotEmpty()
               ) {
               $item = $label_list->getFirst();
               while ( $item ) {
                  $rubric_item_id_array[$item->getLabelType()][] = $item->getItemID();
                  unset($item);
                  $item = $label_list->getNext();
               }
            }
            unset($rubric_item_id_array['label']);
         }

         // now save links
         if ( !empty($rubric_item_id_array) ) {
            foreach ( $rubric_item_id_array as $rubric => $id_array ) {
               $this->saveRubricLinksByIDArray($id_array,$rubric);
            }
         }
      }
   }

   public function getAllLinkedItemIDArrayLabelVersion () {
      $retour = array();
      $manager = $this->_environment->getLinkManager();
      $links = $manager->getLinks('in_matrix',$this);
      if ( !empty($links) ) {
         foreach ( $links as $link ) {
            if ( !empty($link['from_item_id'])
                 and !empty($link['to_item_id'])
               ) {
               if ($link['from_item_id'] == $this->getItemID() ) {
                  $retour[] = $link['to_item_id'];
               } else {
                  $retour[] = $link['from_item_id'];
               }
            }
         }
      }
      return $retour;
   }
}
?>