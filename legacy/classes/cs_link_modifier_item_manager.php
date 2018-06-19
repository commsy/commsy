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

include_once('classes/cs_link_father_manager.php');

/** class for database connection to the database table "link_modifier_item"
 * this class implements a database manager for the table "link_modifier_item",
 * in which we store who had edited an item
 */
class cs_link_modifier_item_manager extends cs_link_father_manager {

   /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function __construct($environment ) {
		cs_link_father_manager::__construct($environment);
		$this->_db_table = 'link_modifier_item';
   }

   /** This method returns all user_id's from people, who have edited this item
     *
     * @param integer item_id    id of the item
     *
     * @return array containing modifier id's
     */
   function getModifiersOfItem ( $item_id ) {
      $query  = 'SELECT t2.item_id '.
                'FROM '.$this->addDatabasePrefix('link_modifier_item').' AS t1, '.$this->addDatabasePrefix('user').' AS t2 '.
                'WHERE t1.item_id = "'.encode(AS_DB,$item_id).'" AND t1.modifier_id = t2.item_id '.
                'ORDER BY lastname ASC';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting modifiers: "'.$this->_dberror.'" from query: "'.$query.'"');
      } else {
         $link_modifiers = array();
         foreach ($result as $rs) {
            $link_modifiers[] = $rs['item_id'];
         }
      }
      return $link_modifiers;
   }

   /** mark an item as edited by the current user
     *
     * @param integer item_id    id of the item
     * @param integer user_id    id of modifier, default set to id of current user
     */
    function markEdited($item_id, $user_id = '')
    {
        if ($user_id == '') {
            $user_id = $this->_current_user_id;
        }

        if (!empty($user_id)) {
            $query = '
                INSERT INTO '.$this->addDatabasePrefix('link_modifier_item').' SET '.
                ' item_id="'.encode(AS_DB,$item_id).'", '.
                ' modifier_id="'.encode(AS_DB,$user_id).'"'.
                ' ON DUPLICATE KEY UPDATE'.
                ' item_id="'.encode(AS_DB,$item_id).'", '.
                ' modifier_id="'.encode(AS_DB,$user_id).'"
            ';

            $this->_db_connector->setDisplayOff();$this->_db_connector->performQuery($query);
            $this->_db_connector->setDisplayOn();

            $errno = $this->_db_connector->getErrno();
            if (!empty($errno)) {
                include_once('functions/error_functions.php');
                trigger_error('Problems marking item as modified from query: "'.$query.'"');
            }
        }
    }

   function mergeAccounts ($account_new,$account_old) {
      $query_test = 'SELECT * FROM '.$this->addDatabasePrefix('link_modifier_item').' WHERE modifier_id = "'.encode(AS_DB,$account_old).'";';
      $result_test = $this->_db_connector->performQuery($query_test);
      if ( !empty($result_test) ) {
         foreach ( $result_test as $row_test ) {
            $query_test2 = 'SELECT * FROM '.$this->addDatabasePrefix('link_modifier_item').' WHERE modifier_id="'.encode(AS_DB,$account_new).'" and item_id="'.encode(AS_DB,$row_test['item_id']).'";';
            $result_test2 = $this->_db_connector->performQuery($query_test2);
            if ( empty($result_test2) ) {
               $query  = "UPDATE ".$this->addDatabasePrefix("link_modifier_item")." SET ";
               $query .= " modifier_id = ".encode(AS_DB,$account_new);
               $query .= " WHERE modifier_id = ".encode(AS_DB,$account_old);
               $query .= " AND item_id = ".encode(AS_DB,$row_test['item_id']);

               $result = $this->_db_connector->performQuery($query);
               if ( !isset($result) or !$result ) {
                  include_once('functions/error_functions.php');trigger_error('Problems creating link_modifier_item from query: "'.$query.'"',E_USER_WARNING);
               }
            } else {
               $query  = "DELETE FROM ".$this->addDatabasePrefix("link_modifier_item");
               $query .= " WHERE modifier_id = ".encode(AS_DB,$account_old);
               $query .= " AND item_id = ".encode(AS_DB,$row_test['item_id']);

               $result = $this->_db_connector->performQuery($query);
               if ( !isset($result) or !$result ) {
                  include_once('functions/error_functions.php');trigger_error('Problems creating link_modifier_item from query: "'.$query.'"',E_USER_WARNING);
               }
            }
         }
      }
   }

    function moveFromDbToBackup($context_id)
    {
        $id_array = array();
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($context_id);
        $user_manager->select();
        $user_list = $user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $this->_db_table . ' SELECT * FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.modifier_id IN (' . implode(",", $id_array) . ')';
                $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id);
            }
        }
    }

    function moveFromBackupToDb($context_id)
    {
        $id_array = array();
        $zzz_user_manager = $this->_environment->getZzzUserManager();
        $zzz_user_manager->setContextLimit($context_id);
        $zzz_user_manager->select();
        $user_list = $zzz_user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $this->_db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.modifier_id IN (' . implode(",", $id_array) . ')';
                $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id, true);
            }
        }
    }

    function deleteFromDb($context_id, $from_backup = false)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        $db_prefix = '';
        $id_array = array();
        if (!$from_backup) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($context_id);
            $user_manager->select();
            $user_list = $user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        } else {
            $db_prefix .= $c_db_backup_prefix . '_';
            $zzz_user_manager = $this->_environment->getZzzUserManager();
            $zzz_user_manager->setContextLimit($context_id);
            $zzz_user_manager->select();
            $user_list = $zzz_user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        }

        if (!empty($id_array)) {
            $query = 'DELETE FROM ' . $db_prefix . $this->_db_table . ' WHERE ' . $db_prefix . $this->_db_table . '.modifier_id IN (' . implode(",", $id_array) . ')';
            $this->_db_connector->performQuery($query);
        }
    }
}
?>