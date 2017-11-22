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

/** upper class of the label manager
 */
include_once('classes/cs_labels_manager.php');

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_group_manager extends cs_labels_manager {

   /** constructor
   * the only available constructor, initial values for internal variables
   *
   * @param object cs_environment the environment
   */
   public function __construct ($environment) {
      cs_labels_manager::__construct($environment);
   }

   /** resetLimits
   *  reset limits of this manager
   */
   public function resetLimits () {
      parent::resetLimits();
      $this->_type_limit = CS_GROUP_TYPE;
      $this->_context_limit = $this->_environment->getCurrentContextID();
   }

  /** get an empty group item
    *  get an empty label (group) item
    *
    *  @return cs_label_item a group label
    */
   public function getNewItem ($label_type = '') {
      include_once('classes/cs_group_item.php');
      $item = new cs_group_item($this->_environment);
      return $item;
   }

   public function cleanLinkToGroupAll ( $context_id ) {
      $retour = NULL;

      // rename group all
      $this->setContextLimit($context_id);
      $group_all_item = $this->getItemByName('ALL');
      if ( !isset($group_all_item) ) {
         $this->_renameGroupAll($context_id);
         sleep(2);
         $group_all_item = $this->getItemByName('ALL');
      }

      // re-insert links
      if ( isset($group_all_item)
           and !empty($group_all_item)
           and $group_all_item->getItemID() > 0
         ) {
         $retour = 0;

         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserLimit();
         $user_manager->select();
         $user_list = $user_manager->get();
         unset($user_manager);
         if ( isset($user_list) and $user_list->isNotEmpty() ) {
            $user_item = $user_list->getFirst();
            while ( $user_item ) {
               if ( !$user_item->isInGroup($group_all_item) ) {
                  $user_item->setGroup($group_all_item);
                  $user_item->setChangeModificationOnSave(false);
                  $user_item->save();
                  $retour++;
               }
               unset($user_item);
               $user_item = $user_list->getNext();
            }
            unset($user_item);
            unset($user_list);
         }
         unset($group_all_item);
      } else {
         unset($group_all_item);
         $retour = -1;
      }
      return $retour;
   }

   private function _renameGroupall ($context_id) {
      $query  = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET name="ALL" WHERE type="group" and (name="Alle Mitglieder" or name="All members") and context_id="'.$context_id.'";';
      $result = $this->_db_connector->performQuery($query);
      $this->resetCache();
   }
}
?>