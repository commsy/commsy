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

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 *
 * @author CommSy Development Group
 */
class cs_topic_item extends cs_label_item {

   /** constructor:
    * the only available constructor, initial values for internal variables
    *
    * @param object environment environment of CommSy
    */
   function __construct($environment) {
      cs_label_item::__construct($environment, 'topic');
   }

   function activatePath () {
      $this->_addExtra('PATH',1);
   }

   function deactivatePath () {
      $this->_addExtra('PATH',-1);
   }

   function _getPathActive () {
	   return $this->_getExtra('PATH');
   }

   function isPathActive () {
      $retour = false;
      $path = $this->_getExtra('PATH');
      if ($path == 1) {
	$retour = true;
      }
      return $retour;
   }

   function getPathItemList () {
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->setLinkedItemLimit($this);
      $link_manager->setSortingPlaceLimit();
      $link_manager->sortbySortingPlace();
      $link_manager->select();
      $link_item_list = $link_manager->get();

      include_once('classes/cs_list.php');
      $retour = new cs_list();

      if ( !$link_item_list->isEmpty() ) {
	$item = $link_item_list->getFirst();
	while ($item) {
	   $retour->add($item->getLinkedItem($this));
	   $item = $link_item_list->getNext();
	}
      }
      return $retour;
   }

   function save() {
      $topic_manager = $this->_environment->getTopicManager();
      $this->_save($topic_manager);
      $this->_saveFiles();     // this must be done before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id
   }


}
?>