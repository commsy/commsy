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
class cs_buzzword_item extends cs_label_item {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param string label_type type of the label
    */
   public function __construct ( $environment ) {
      cs_label_item::__construct($environment,CS_BUZZWORD_TYPE);
   }

   /** save news item
    * this methode save the news item into the database
    */
   function saveMaterialLinksByIDArray($array) {
      $links_manager = $this->_environment->getLinkManager();
      $links_manager->saveLinksMaterialToBuzzword($array,$this->getItemID());
   }

   function saveRubricLinksByIDArray($array,$rubric) {
      $links_manager = $this->_environment->getLinkManager();
      $links_manager->saveLinksRubricToBuzzword($array,$this->getItemID(),$rubric);
   }

    /**
     * Sets linked items for this buzzword
     * 
     * @param  array $rubricIdArray Array of item id's to set
     */
    function saveLinksByIDArray($rubricIdArray) {
        $itemManager = $this->_environment->getItemManager();

        /**
         * We need to store all links per rubric, so we iterate $rubricIdArray and build up
         * a per rubric array
         */
        $rubricUpdateArray = array();

        if (!empty($rubricIdArray)) {
            $newLinkItemList = $itemManager->getItemList($rubricIdArray);

            if ($newLinkItemList && $newLinkItemList->isNotEmpty()) {
                $newItem = $newLinkItemList->getFirst();
                while ($newItem) {
                    $rubricUpdateArray[$newItem->getItemType()][] = $newItem->getItemID();

                    $newItem = $newLinkItemList->getNext();
                }
            }
        }

        // check update types and convert "label" types to real modules
        if (!empty($rubricUpdateArray['label'])) {
            $labelManager = $this->_environment->getLabelManager();
            $labelManager->setIdArrayLimit($rubricUpdateArray['label']);
            $labelManager->select();

            $labelList = $labelManager->get();
            if ($labelList && $labelList->isNotEmpty()) {
                $labelItem = $labelList->getFirst();
                while ($labelItem) {
                    $rubricUpdateArray[$labelItem->getLabelType()][] = $labelItem->getItemID();

                    $labelItem = $labelList->getNext();
                }
            }
        }

        /**
         * Compare the update array with the currently linked items to add rubrics that
         * may be deleted completly
         */
        $currentLinkIdArray = $this->getAllLinkedItemIDArrayLabelVersion();
        $currentLinkItemList = $itemManager->getItemList($currentLinkIdArray);

        if ($currentLinkItemList && $currentLinkItemList->isNotEmpty()) {
            $currentItem = $currentLinkItemList->getFirst();
            while ($currentItem) {
                if (!isset($rubricUpdateArray[$currentItem->getItemType()])) {
                    $rubricUpdateArray[$currentItem->getItemType()] = array();
                }

                $currentItem = $currentLinkItemList->getNext();
            }
        }

        // save per rubric
        if (!empty($rubricUpdateArray)) {
            foreach ($rubricUpdateArray as $rubric => $idArray) {
                $this->saveRubricLinksByIDArray($idArray, $rubric);
            }
        }
   }

   public function getAllLinkedItemIDArrayLabelVersion () {
      $retour = array();
      $manager = $this->_environment->getLinkManager();
      $links = $manager->getLinks('buzzword_for',$this);
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