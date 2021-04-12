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

/** class for database connection to the database table "noticed"
 * this class implements a database manager for the table "noticed". Read items
 */
class cs_noticed_manager {

  /**
   * object cs_user_item - containing the current user
   */
  var $_current_user = NULL;
  var $_current_user_id = NULL;
  var $_db_connector = NULL;
  var $_rubric_id_array = array();
  var $_noticed_id_array = array();
  var $_annotation_id_array = array();
  var $_noticed_annotation_id_array = array();
  var $_cache_on = true;

  public $_db_prefix = '';
  public $_with_db_prefix = true;
  
   /**
    * Environment - the environment of the CommSy
    */
   var $_environment = null;

   /** constructor: cs_reader_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function __construct($environment ) {
      $this->_environment = $environment;
      $this->_current_user    = $this->_environment->getCurrentUser();
      $this->_current_user_id = $this->_current_user->getItemID();
      $this->_db_connector    = $this->_environment->getDBConnector();
   }

   /** reset limits
    * reset limits of this class
    *
    * @version $Revision$
    */
   function resetLimits () {
   }

   function resetData () {
      $this->_noticed_id_array = array();
      $this->_rubric_id_array = array();
   }

   public function setCacheOff () {
      $this->_cache_on = false;
   }

   /** has the current user read a specific item
     * this method returns the latest version_id of an item, the user
     * has already read. Or false, if s/he never read this item.
     *
     * @param integer item_id    id of the item
     *
     * @return array contains the latest version_id and read_date
     */
   function getLatestnoticed ( $item_id ) {
      if (in_array($item_id,$this->_rubric_id_array)){
         if (array_key_exists($item_id, $this->_noticed_id_array)){
            return $this->_noticed_id_array[$item_id];
         }else{
            return false;
         }
      }else{
         return $this->getLatestnoticedForUserByID($item_id, $this->_current_user_id);
      }
   }

   /** has the current user read a specific item
     * this method returns the latest version_id of an item, the user
     * has already read. Or false, if s/he never read this item.
     *
     * @param integer item_id    id of the item
     *
     * @return array contains the latest version_id and read_date
     */
   function getLatestnoticedByUser ( $item_id, $user_id ) {
      if (in_array($item_id,$this->_rubric_id_array)){
         if (array_key_exists($item_id, $this->_noticed_id_array)){
            return $this->_noticed_id_array[$item_id];
         }else{
            return false;
         }
      }else{
         return $this->getLatestnoticedForUserByID($item_id, $user_id);
      }
   }

   function getLatestNoticedForUserByID ( $item_id, $user_id ) {
      $query  = 'SELECT version_id, read_date FROM '.$this->addDatabasePrefix('noticed').
                ' WHERE item_id="'.encode(AS_DB,$item_id).'"'.
                ' AND   user_id="'.encode(AS_DB,$user_id).'"'.
                ' ORDER BY read_date DESC';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
      } else {
         $noticed = array();
         if ( !empty($result[0]) ) {
            $noticed['version_id'] = $result[0]['version_id'];
            $noticed['read_date']  = $result[0]['read_date'];
         }
      }
      return $noticed;
   }

   function getLatestNoticedByIDArray ($id_array,$user_id = 0) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if (empty($user_id)){
      	 $user_id = $this->_current_user_id;
      }
      if ($this->_cache_on and count($id_array)>0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_rubric_id_array)){
               $this->_rubric_id_array[] = $id;
            }
         }
         $query  = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                ' WHERE item_id IN ('.implode(",",encode(AS_DB,$id_array)).')'.
                ' AND   user_id="'.encode(AS_DB,$user_id).'"'.
                ' GROUP BY item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
         } else {
            $noticed = array();
            foreach ($result as $rs) {
               $temp = array();
               $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
               $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
               if (!in_array($temp,$this->_noticed_id_array)){
                  $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                  $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
               }
            }
         }
      }
   }

   function getLatestNoticedByIDArrayAndUser ($id_array, $user_id){
      if ($this->_cache_on and count($id_array)>0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_rubric_id_array)){
               $this->_rubric_id_array[] = $id;
            }
         }
         $query  = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                ' WHERE item_id IN ('.implode(",",encode(AS_DB,$id_array)).')'.
                ' AND   user_id="'.encode(AS_DB,$user_id).'"'.
                ' GROUP BY item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
         } else {
            $noticed = array();
            foreach ($result as $rs) {
               $temp = array();
               $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
               $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
               if (!in_array($temp,$this->_noticed_id_array)){
                  $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                  $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
               }
            }
            return $this->_noticed_id_array;
         }
      }
   }

   function getLatestNoticedAnnotationsByIDArray ($id_array){
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if ($this->_cache_on and count($id_array)>0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_rubric_id_array)){
               $this->_rubric_id_array[] = $id;
            }
         }
         $annotation_manager = $this->_environment->getAnnotationManager();
         $annotation_list = new cs_list;
         $annotation_id_array = array();
         $annotation_list = $annotation_manager->getAllAnnotationItemListByIDArray($id_array);
         $annotation_item = $annotation_list->getFirst();
         if (!empty($annotation_item)){
            while($annotation_item){
               $annotation_id_array[] = $annotation_item->getItemID();
               if (!in_array($annotation_item->getItemID(),$this->_rubric_id_array)){
                  $this->_rubric_id_array[] = $annotation_item->getItemID();
               }
               $annotation_item = $annotation_list->getNext();
            }
            // in den annotation_manager und die IDS im assoziativen Array zwischenspeichern
            $query  = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                ' WHERE item_id IN ('.implode(",",encode(AS_DB,$annotation_id_array)).')'.
                ' AND   user_id="'.encode(AS_DB,$this->_current_user_id).'"'.
                ' GROUP BY item_id';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
              include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
            } else {
               foreach ($result as $rs) {
                  $temp = array();
                  $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
                  $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
                  if (!in_array($temp,$this->_noticed_id_array)){
                     $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                     $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
                  }
               }
            }
         }
      }
   }

   function getLatestNoticedAnnotationsByIDArrayAndUser ($id_array, $user_id){
      if ($this->_cache_on and count($id_array)>0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_rubric_id_array)){
               $this->_rubric_id_array[] = $id;
            }
         }
         $annotation_manager = $this->_environment->getAnnotationManager();
         $annotation_list = new cs_list;
         $annotation_id_array = array();
         $annotation_list = $annotation_manager->getAllAnnotationItemListByIDArray($id_array);
         $annotation_item = $annotation_list->getFirst();
         if (!empty($annotation_item)){
            while($annotation_item){
               $annotation_id_array[] = $annotation_item->getItemID();
               if (!in_array($annotation_item->getItemID(),$this->_rubric_id_array)){
                  $this->_rubric_id_array[] = $annotation_item->getItemID();
               }
               $annotation_item = $annotation_list->getNext();
            }
            // in den annotation_manager und die IDS im assoziativen Array zwischenspeichern
            $query  = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                ' WHERE item_id IN ('.implode(",",encode(AS_DB,$annotation_id_array)).')'.
                ' AND user_id="'.encode(AS_DB,$user_id).'"'.
                ' GROUP BY item_id';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
              include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
            } else {
               foreach ($result as $rs) {
                  $temp = array();
                  $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
                  $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
                  if (!in_array($temp,$this->_noticed_id_array)){
                     $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                     $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
                  }
               }
            }
         }
      }
   }

   /**
    * Marks the item with the given item ID & version ID as noticed by the current user.
    *
    * @param integer $itemId ID of the item to be marked as noticed
    * @param integer $versionId ID of the item version to be marked as noticed
    */
   public function markNoticed(int $itemId, int $versionId)
   {
       if (!empty($itemId)) {
           $this->markItemsAsNoticed([$itemId], $versionId); // defaults to current user
       }
   }

   /**
    * Marks an array of items (of the given version ID) as noticed by the given users
    * (or the current user in case no user IDs were given).
    *
    * @param integer[] $itemIds Array of item IDs for items to be marked as noticed
    * @param integer $versionId ID of the item version (applied to all given items) to be marked as noticed
    * @param integer[]|null $userIds Optional array of user IDs specifying the users for whom the given items shall
    * be marked as noticed; defaults to null in which case given items will be marked as noticed for the current user
    */
   public function markItemsAsNoticed(array $itemIds, int $versionId, array $userIds = null)
   {
       if (empty($itemIds)) {
           return;
       }

       if (empty($userIds)) {
           if (empty($this->_current_user_id)) {
               $this->_current_user_id = $this->_environment->getCurrentUserID();
               if (empty($this->_current_user_id)) {
                   return;
               }
           }
           $userIds = [$this->_current_user_id];
       }

       /*
        * There was a problem in reader- and noticed-manager when marking an entry as read, if
        * it was a non-active entry. In this case, the manager tried to execute the same insert
        * statement twice, which caused an error because of the tables primary key.
        * To fix this, the query was changed to "INSERT IGNORE INTO..."
        */
       $query = 'INSERT IGNORE INTO ' . $this->addDatabasePrefix('noticed')
           . ' (item_id, version_id, user_id, read_date) VALUES ';

       $valueRows = [];
       $currentDateTime = getCurrentDateTimeInMySQL();

       foreach ($itemIds as $itemId) {
           foreach ($userIds as $userId) {
               $valueRow = '("'
                   . encode(AS_DB, $itemId) . '", "'
                   . encode(AS_DB, $versionId) . '", "'
                   . encode(AS_DB, $userId) . '", "'
                   . $currentDateTime
                   . '")';
               $valueRows[] = $valueRow;
           }
       }
       $query .= implode(', ', $valueRows);

       $result = $this->_db_connector->performQuery($query);

       if (!isset($result)) {
           include_once('functions/error_functions.php');
           trigger_error('Problems marking item(s) as noticed from query: "' . $query . '"');
       }
   }

   function mergeAccounts($new_id,$old_id) {
      $select = "SELECT * FROM ".$this->addDatabasePrefix("noticed")." WHERE user_id = '".encode(AS_DB,$old_id)."'";

      $result = $this->_db_connector->performQuery($select);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating notice from query: "'.$select.'"',E_USER_WARNING);
      }

      foreach ( $result as $row ) {
           $select2 = "SELECT * FROM ".$this->addDatabasePrefix("noticed")." WHERE user_id = '".encode(AS_DB,$new_id)."' ";
           $select2.= " AND item_id = ".$row['item_id'];
           $select2.= " AND version_id = ".$row['version_id'];

         $result2 = $this->_db_connector->performQuery($select2);
         if ( !isset($result2) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems creating notice from query: "'.$select2.'"',E_USER_WARNING);
         } elseif ( empty($result2[0]) ) {
            $row2 = '';
         } else {
            $row2 = $result2[0];
         }


         if ( empty($row2) ) {
            $update = "UPDATE ".$this->addDatabasePrefix("noticed")." SET ";
            $update.= " user_id = ".encode(AS_DB,$new_id);
            $update.= " WHERE user_id = ".encode(AS_DB,$old_id);
            $update.= " AND item_id = ".$row['item_id'];
            $update.= " AND version_id = ".$row['version_id'];

            $result3 = $this->_db_connector->performQuery($update);
            if ( !isset($result3) or !$result3 ) {
               include_once('functions/error_functions.php');trigger_error('Problems creating notice from query: "'.$update.'"',E_USER_WARNING);
            }

         } else {
            $update = "DELETE FROM ".$this->addDatabasePrefix("noticed")." ";
            $update.= " WHERE user_id = ".encode(AS_DB,$old_id);
            $update.= " AND item_id = ".$row['item_id'];
            $update.= " AND version_id = ".$row['version_id'];

            $result3 = $this->_db_connector->performQuery($update);
            if ( !isset($result3) or !$result3 ) {
               include_once('functions/error_functions.php');trigger_error('Problems creating notice from query: "'.$update.'"',E_USER_WARNING);
            }
         }
      }
   }

   function addDatabasePrefix ($db_table) {
      $retour = $db_table;
      if ( $this->withDatabasePrefix() ) {
         $retour = $this->_db_prefix.$retour;
      }
      return $retour;
   }

   function setWithoutDatabasePrefix () {
      $this->_with_db_prefix = false;
   }

   function setWithDatabasePrefix () {
      $this->_with_db_prefix = true;
   }

   function withDatabasePrefix () {
      return $this->_with_db_prefix;
   }

    function moveFromDbToBackup($context_id)
    {
        $id_array_items = array();
        $item_manager = $this->_environment->getItemManager();
        $item_manager->setContextLimit($context_id);
        $item_manager->setNoIntervalLimit();
        $item_manager->select();
        $item_list = $item_manager->get();
        $temp_item = $item_list->getFirst();
        while ($temp_item) {
            $id_array_items[] = $temp_item->getItemID();
            $temp_item = $item_list->getNext();
        }

        $id_array_users = array();
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($context_id);
        $user_manager->select();
        $user_list = $user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array_users[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array_items) and !empty($id_array_users)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . 'noticed' . ' SELECT * FROM ' . 'noticed' . ' WHERE ' . 'noticed' . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . 'noticed' . '.user_id IN (' . implode(",", $id_array_users) . ')';
                $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id);
            }
        }
    }

    function moveFromBackupToDb($context_id)
    {
        $id_array_items = array();
        $zzz_item_manager = $this->_environment->getZzzItemManager();
        $zzz_item_manager->setContextLimit($context_id);
        $zzz_item_manager->setNoIntervalLimit();
        $zzz_item_manager->select();
        $item_list = $zzz_item_manager->get();
        $temp_item = $item_list->getFirst();
        while ($temp_item) {
            $id_array_items[] = $temp_item->getItemID();
            $temp_item = $item_list->getNext();
        }

        $id_array_users = array();
        $zzz_user_manager = $this->_environment->getZzzUserManager();
        $zzz_user_manager->setContextLimit($context_id);
        $zzz_user_manager->select();
        $user_list = $zzz_user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array_users[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array_items) and !empty($id_array_users)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . 'noticed' . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . 'noticed' . ' WHERE ' . $c_db_backup_prefix . '_' . 'noticed' . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . $c_db_backup_prefix . '_' . 'noticed' . '.user_id IN (' . implode(",", $id_array_users) . ')';
                $result = $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id, true);
            }
        }
    }

    function deleteFromDb($context_id, $from_backup = false)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        $db_prefix = '';
        $id_array_items = array();
        $id_array_users = array();
        if (!$from_backup) {
            $item_manager = $this->_environment->getItemManager();
            $item_manager->setContextLimit($context_id);
            $item_manager->setNoIntervalLimit();
            $item_manager->select();
            $item_list = $item_manager->get();
            $temp_item = $item_list->getFirst();
            while ($temp_item) {
                $id_array_items[] = $temp_item->getItemID();
                $temp_item = $item_list->getNext();
            }
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($context_id);
            $user_manager->select();
            $user_list = $user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array_users[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        } else {
            $db_prefix .= $c_db_backup_prefix . '_';
            $zzz_item_manager = $this->_environment->getZzzItemManager();
            $zzz_item_manager->setContextLimit($context_id);
            $zzz_item_manager->setNoIntervalLimit();
            $zzz_item_manager->select();
            $item_list = $zzz_item_manager->get();
            $temp_item = $item_list->getFirst();
            while ($temp_item) {
                $id_array_items[] = $temp_item->getItemID();
                $temp_item = $item_list->getNext();
            }
            $zzz_user_manager = $this->_environment->getZzzUserManager();
            $zzz_user_manager->setContextLimit($context_id);
            $zzz_user_manager->select();
            $user_list = $zzz_user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array_users[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        }

        if (!empty($id_array_items) and !empty($id_array_users)) {
            $query = 'DELETE FROM ' . $db_prefix . 'noticed' . ' WHERE ' . $db_prefix . 'noticed' . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . $db_prefix . 'noticed' . '.user_id IN (' . implode(",", $id_array_users) . ')';
            $this->_db_connector->performQuery($query);
        }
    }
}
?>