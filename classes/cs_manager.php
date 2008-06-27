<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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


/** upper class for manager of commsy
 * this class implements an upper class of database manager in commsy
 *
 * @author CommSy Development Group
 */
class cs_manager {

  /**
   * integer - containing the item id, if an item was created
   */
  var $_create_id;

  /**
   * integer - containing the version id of the item
   */
  var $_version_id;

  /**
   * object cs_user_item - containing the current user
   */
  var $_current_user = NULL;

  /**
   * integer - containing the room id as a limit for select statements
   */
  var $_room_limit = NULL;
  var $_room_array_limit = NULL;

  /**
   * String - containing the attribute as a limit for select statements
   */
  var $_attribute_limit = NULL;

  /**
   * boolean - true: then all deleted items where not observed in select statements, false: then all deleted items where observed in select statements
   */
  var $_delete_limit = true;

  /**
   * object cs_list - contains stored commsy items
   */
  var $_data = NULL;

   /**
    * Environment - the environment of the CommSy
    */
   var $_environment = null;

   /**
    * id_array for item_ids
    */
   var $_id_array = NULL;

   /**
    * integer - containing the item id of the ref item as a limit
    */
   var $_ref_id_limit = NULL;

   /**
    * integer - containing the item id of the user as a limit
    */
   var $_ref_user_limit = NULL;

   /**
    * integer - max number of days since creation of item
    */
   var $_existence_limit = NULL;
   var $_age_limit = NULL;

   var $_update_with_changing_modification_information = true;

   var $_db_table = NULL;

   /**
   * Array containing search limits
   */
   var $_search_array = array();

   var $_tag_limit = NULL;
   var $_buzzword_limit = NULL;

   /**
   * Array containing negative search limits (words that mustn't occure)
   */
   var $_search_negative_array = array();

   /**
    * Stores last query if method assigns string
    */
   var $_last_query = '';

   var $_output_limit = '';
   var $_key_array = NULL;
   var $_only_files_limit = NULL;
   var $_db_connector = NULL;

   var $_cached_items = array();
   var $_cache_on = true;

  /** constructor: cs_manager
    * the only available constructor, initial values for internal variables. sets room limit to room
    *
    * @param object cs_environment the environment
    */
  function cs_manager ($environment) {
     $this->_environment = $environment;
     $this->reset();
     $this->_room_limit      =  $this->_environment->getCurrentContextID();
     $this->_attribute_limit =  NULL;
     $this->_current_user    =  $this->_environment->getCurrentUser();
     $this->_db_connector    =  $this->_environment->getDBConnector();
  }

  /** set context id
    * this method sets the context id
    *
    * @param integer id of the context
    */
   function setCurrentContextID($id) {
      $this->_current_context = $id;
   }

   public function setCacheOff () {
      $this->_cache_on = false;
   }

  /** reset class
    * reset limits and data of this class
    */
  function reset () {
     $this->resetLimits();
     $this->resetData();
  }

  /** reset limits
    * reset limits of this class: room limit, delete limit
    */
  function resetLimits () {
     $this->_attribute_limit   =  NULL;
     $this->_room_limit = $this->_environment->getCurrentContextID();
     $this->_ref_id_limit = NULL;
     $this->_ref_user_limit = NULL;
     $this->_existence_limit = NULL;
     $this->_age_limit = NULL;
     $this->_tag_limit = NULL;
     $this->_buzzword_limit = NULL;
     $this->reset_search_limit();
     $this->delete_limit = true;
     $this->_update_with_changing_modification_information = true;
     $this->_output_limit = '';
     $this->_only_files_limit = NULL;
     $this->_room_array_limit = NULL;
  }

  /** reset data
    * reset data of this class: reset list of items
    */
  function resetData () {
     $this->_data = NULL;
     $this->_id_array = NULL;
  }

   function setBuzzwordLimit ($limit) {
      $this->_buzzword_limit = (int)$limit;
   }

   function setTagLimit ($limit) {
      $this->_tag_limit = (int)$limit;
   }

   function _getTagIDArrayByTagID($id){
      $id_array = array($id);
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      $id_array = array_merge($id_array,$tag2tag_manager->getRecursiveChildrenItemIDArray($id));
      return $id_array;
   }

   /** reset search limit
    * reset the limit of this class: search limit
    */
   function reset_search_limit () {
      $this->_search_array = array();
   }

   function setOutputLimitToXML () {
      $this->_output_limit = 'XML';
   }

   function setOnlyFilesLimit () {
      $this->_only_files_limit = true;
   }

   /** set search limit
    * this method sets a search limit for dates
    *
    * @param string limit search limit for dates
    */
   function setSearchLimit ($limit){
      $limit = addcslashes(encode(AS_DB,(string)$limit),'%');
      $limit = cs_strtoupper($limit);

      //find all occurances of quoted text and store them in an array
      preg_match_all('/(\\"(.+?)\\\")/',$limit,$literal_array);
      //delete this occurances from the original string
      $limit = preg_replace('/(\\\"(.+?)\\\")/','',$limit);

      preg_match_all('/\s-([\w'.SPECIAL_CHARS.']+)/',$limit,$this->_search_negative_array);
      $limit = preg_replace('/\s-([\w'.SPECIAL_CHARS.']+)/','',$limit);

      //clean up the resulting array from quots
      $literal_array = str_replace('"','',$literal_array[2]);
      //clean up rest of $limit and get an array with entrys
      $limit = str_replace('  ',' ',$limit);
      $limit = trim($limit);
      $split_array = explode(' ',$limit);

      //check which array contains search limits and act accordingly
      if ($split_array[0] != '' AND count($literal_array) > 0) {
         $this->_search_array = array_merge($split_array,$literal_array);
      } else {
         if ($split_array[0] != '') {
            $this->_search_array = $split_array;
         } else {
            $this->_search_array = $literal_array;
         }
      }
   }

   //Help function to provide the sql code for the search limit
   // @param Array with names of DB-Fields where search must find a match
   function _generateSearchLimitCode($field_array) {
      $search_limit_query = '';
      //text to be searched for
      for ($i = 0; $i < count($this->_search_array); $i++) {
         $search_limit_query .= '(';
         for($j = 0; $j < count($field_array); $j++) {
            $search_limit_query .= '(';
            $search_limit_query .= 'UPPER('.$field_array[$j].') LIKE BINARY "%'.encode(AS_DB,$this->_search_array[$i]).'%"';
            $search_limit_query .= ' OR ';
            $search_limit_query .= 'UPPER('.$field_array[$j].') LIKE BINARY "%'.encode(AS_DB,strtoupper(htmlentities($this->_search_array[$i]))).'%"';
            $search_limit_query .= ')';
            if ($j+1 < count($field_array)) {
               $search_limit_query .= ' OR ';
            }
         }
         if ($i+1 < count($this->_search_array)) {
               $search_limit_query .= ') AND ';
         }
      }
      $search_limit_query .= ')';
      // text to be excluded- if any
      if (count($this->_search_negative_array[1]) > 0) {
         $search_limit_query .= ' AND ';
         for ($i = 0; $i < count($this->_search_negative_array[1]); $i++) {
            $search_limit_query .= '(';
            for ($j = 0; $j < count($field_array); $j++) {
               $search_limit_query .= '(';
               $search_limit_query .= 'UPPER('.$field_array[$j].') NOT LIKE BINARY "%'.encode(AS_DB,$this->_search_negative_array[1][$i]).'%"';
               $search_limit_query .= ' AND ';
               $search_limit_query .= 'UPPER('.$field_array[$j].') NOT LIKE BINARY "%'.encode(AS_DB,strtoupper(htmlentities($this->_search_negative_array[1][$i]))).'%"';
               $search_limit_query .= ')';
               if ($j+1 < count($field_array)) {
                  $search_limit_query .= ' AND ';
               }
            }
            if ($i+1 < count($this->_search_negative_array[1])) {
               $search_limit_query .= ') AND ';
            }
         }
         $search_limit_query .= ')';
      }
      return $search_limit_query;
   }

   /** set context limit
    * this method sets a context limit
    *
    * @param integer limit id of the context
    */
   function setContextLimit ($limit) {
      $this->_room_limit = (int)$limit;
   }

   function setContextArrayLimit($limit) {
      $this->_room_array_limit = $limit;
   }

   function setRubricLimit($type, $limit){
      switch($type){
         case CS_TOPIC_TYPE: $this->setTopicLimit($limit);break;
         case CS_INSTITUTION_TYPE: $this->setInstitutionLimit($limit);break;
         case CS_GROUP_TYPE: $this->setGroupLimit($limit);break;
      }
   }

  /** set attribute limit
    * this method sets a attribute limit
    *
    * @param string limit
    *
    * @author CommSy Development Group
    */
  function setAttributeLimit ($limit) {
     $this->_attribute_limit = $limit;
  }

  /** set delete limit
    * this method sets the delete limit: true, all deleted items will be not observed - false, all items will be observed
    *
    * @param boolean limit with delete limit ?
    */
  function setDeleteLimit ($limit) {
     $this->_delete_limit = (boolean)$limit;
  }

   /** set limit
    * this method sets a group limit for material
    *
    * @param integer limit id of the group
    *
    * @author CommSy Development Group
    */
   function setRefIDLimit ($limit) {
      $this->_ref_id_limit = (int)$limit;
   }

   function setRefUserLimit ($limit) {
      $this->_ref_user_limit = (int)$limit;
   }

   /** set existence limit
    * The existence limit sets the max number of days that passed
    * since the creation of this item
    *
    * @param integer max number of days
    */
   function setExistenceLimit ($limit) {
      $this->_existence_limit = (int)$limit;
   }

   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

  function saveWithoutChangingModificationInformation () {
     $this->_update_with_changing_modification_information = false;
  }

  /** get error number
    * this method returns the number of an error, if an error occured
    *
    * @return integer error number
    */
  function getErrorNumber () {
     return $this->_db_connector->getErrno();;
  }

  /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error number
    */
  function getErrorMessage () {
     return $this->_db_connector->getError();
  }

  /** get item id of created item
    * this method returns the item id of the item just created
    *
    * @return integer item id
    */
  function getCreateID () {
     return $this->_create_id;
  }

  /** get version id of created item
    * this method returns the version id of the item just created
    *
    * @return integer version id
    */
  function getVersionID () {
     return $this->_version_id;
  }

  /** get the data of the manager
    * this method returns a list of commsy items
    *
    * @return object cs_list list of commsy items
    */
  function get () {
     return $this->_data;
  }

  /** get one item (newest version)
    * this method returns an item in his newest version - this method needs to be overwritten
    *
    * @param integer item_id id of the commsy item
    *
    * @return object cs_item one commsy items
    */
  function getItem ($item_id) {
     echo('cs_manager (getItem): needs to be overwritten !!!<br />'."\n");
  }

    /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    *
    * @author CommSy Development Group
    */
  function getItemList ($id_array) {
     echo(get_class($this).': cs_manager->getItemList needs to be overwritten !!!<br />'."\n");
  }

    /** get a list of items
    * this method returns a list of items
    *
    * @param type name of the db-table to query
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
   function _getItemList ($type, $id_array) {
       /** cs_list is needed for storage the commsy items
       */
      include_once('classes/cs_list.php');
      if ( empty($id_array) ) {
         return new cs_list();
      } else {
         if ( $type == 'discussion' ) {
            $type = 'discussions';
         }
         elseif ( $type == 'todo' ) {
            $type = 'todos';
         }
         $query = "SELECT * FROM ".encode(AS_DB,$type)." WHERE ".encode(AS_DB,$type).".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$type.' items.',E_USER_WARNING);
         } else {
            $list = new cs_list();
            foreach ($result as $rs ) {
               $list->add($this->_buildItem($rs));
            }
            unset($result);
         }
         unset($query);
         unset($id_array);
         unset($type);

         return $list;
      }
   }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();

     $modifier = $item->getModificatorItem();
     if ( !isset($modifier) ) {
        $user = $this->_environment->getCurrentUser();
        $item->setModificatorItem($user);
     } else {
        $modifier_id = $modifier->getItemID();
        if (empty($modifier_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setModificatorItem($user);
        }
     }

     if (!empty($item_id)) {
        $this->_update($item);
     } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setCreatorItem($user);
        }
        $this->_create($item);
     }

     //Add modifier to all users who ever edited this section
     if ( $this->_update_with_changing_modification_information ) {
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_modifier_item_manager->markEdited($item->getItemID());
     }
  }

  /** update an item, with new informations, e.g. creator and modificator
    * this method updates an item initially
    *
    * @param object cs_item
    */
   function saveItemNew ($item) {
      // needs to be overwritten
   }

   /** update modification date of item in items table
   * this method updates the database record for a given item
   *
   * @param cs_item the item for which an update should be made
   */
   function _update($item) {
      $query = 'UPDATE items SET';
      if ( $item->isChangeModificationOnSave() ) {
         $query .= ' modification_date="'.getCurrentDateTimeInMySQL().'",';
      }
      $query .= ' context_id="'.encode(AS_DB,$item->getContextID()).'"';
      $query .= ' WHERE item_id = "'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating item in table items.',E_USER_WARNING);
      } else {
         unset($result);
      }
      unset($query);
      unset($item);
   }

  /** delete a commsy item
    * this method deletes a commsy item
    *
    * @param integer item_id the item id of the commsy item
    */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE items SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.$item_id.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting item in table items.',E_USER_WARNING);
      } else {
         unset($result);
      }
      unset($query);
      unset($item_id);
  }

  function undeleteItemByItemID ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->_db_table.' SET'.
               ' deletion_date=NULL,'.
               ' deleter_id=NULL,'.
               ' modification_date="'.$current_datetime.'",'.
               ' modifier_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems undeleting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         unset($result);
         $this->undelete($item_id);
      }
      unset($query);
      unset($item_id);
   }

   function undelete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $query = 'UPDATE items SET '.
               'modification_date="'.$current_datetime.'",'.
               'deletion_date=NULL,'.
               'deleter_id=NULL'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems undeleting item in table items.',E_USER_WARNING);
      } else {
         unset($result);
      }
      unset($query);
      unset($item_id);
   }

  /** build an item out of an (database) array - internal method, do not use
   * this method returns a item out of a row form the database
   *
   * @param array item_array array with information about the item out of the respective database table
   *
   * @return object cs_item an item
   */
   function _buildItem ($db_array) {
      $item = $this->getNewItem();
      $item->_setItemData(encode(FROM_DB,$db_array));
      return $item;
   }

   /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select () {
      $result = $this->_performQuery();
      $this->_id_array = NULL;
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>'.LF;
      } else {
         include_once('classes/cs_list.php');
         $this->_data = new cs_list();
      }

      if ( is_array($result) ) {
         // do nothing
      } else {
         $result = array();
      }
      foreach ($result as $query_result) {
         if ( isset($this->_output_limit)
              and !empty($this->_output_limit)
              and $this->_output_limit == 'XML'
            ) {
            if ( isset($query_result)
                 and !empty($query_result) ) {
               $this->_data .= '<'.$this->_db_table.'_item>'.LF;
               foreach ($query_result as $key => $value) {
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  if ( $key == 'extras' ) {
                     $value = serialize($value);
                  }
                  $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
                  unset($value);
                  unset($key);
               }
               $this->_data .= '</'.$this->_db_table.'_item>'.LF;
            }
         } else {
            $item = $this->_buildItem($query_result);
            $this->_data->add($item);
            unset($item);
         }
         unset($query_result);
         //$this->_id_array[] = $query_result['item_id'];
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>'.LF;
      }
      unset($result);
   }

 /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function selectDistinct () {
      $result = $this->_performQuery('distinct');
      include_once('classes/cs_list.php');
      $this->_data = new cs_list();
      $this->_id_array = NULL;
      if ( is_array($result) ) {
         // do nothing
      } else {
         $result = array();
      }
      foreach ($result as $query_result) {
         $item = $this->_buildItem($query_result);
         $this->_data->add($item);
         unset($item);
      }
      unset($result);
   }

  /** count all items limited by the limits
    * this method returns the number of selected items limited by the limits.
    * if no items are loaded, the count is performed by the database
    * depends on _performQuery(), which must be overwritten
    *
    * @return integer count annotations
    */
   function getCountAll () {
      $result = 0;
      if (empty($this->_id_array)) {
         $rs = $this->_performQuery('count');
         if ( is_array($rs) ) {
            $result = $rs[0]['count'];
         }
      } else {
         $result = count($this->_id_array);
      }
      return $result;
   }

   /** get all ids of the selected items as an array
    * this method returns all ids of the selected items limited by the limits as an array.
    * if no items are loaded, the ids are loaded from the database
    * depends on _performQuery(), which must be overwritten
    *
    * @return array $this->_id_array id array of selected materials
    */
   function getIDArray () {
      if (empty($this->_id_array)) {
         $result = $this->_performQuery('id_array');
         if ( is_array($result) ) {
            foreach ( $result as $row ) {
               $this->_id_array[] = $row['item_id'];
            }
         }
      }
      return $this->_id_array;
   }

   function getIDs () {
      return $this->getIDArray();
   }


   /** perform database query : select and count
   * abstract method for performing database queries; must be overwritten
   *
   * @param string mode    one of select, count or id_array
   *
   * @return resource result from database
   * @author CommSy Development Group
   */
   function _performQuery($mode) {
      include_once('functions/error_functions.php');
      trigger_error("must be overwritten!", E_USER_ERROR);
   }

   function mergeAccounts ($new_id, $old_id) {
     // creator id
     if ( $this->_db_table != 'links'
          and $this->_db_table != 'items'
       ) {
         $query1 = 'UPDATE '.$this->_db_table.' SET creator_id = "'.encode(AS_DB,$new_id).'" WHERE creator_id = "'.encode(AS_DB,$old_id).'";';
         $result = $this->_db_connector->performQuery($query1);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems merging accounts "'.$this->_db_table.'".',E_USER_WARNING);
         } else {
            unset($result);
            unset($query1);
         }
     }

      // modifier id
      if ( $this->_db_table != 'files'
           and $this->_db_table != 'link_items'
           and $this->_db_table != 'links'
           and $this->_db_table != 'tasks'
           and $this->_db_table != 'items'
         ) {
         $query2 = ' UPDATE '.$this->_db_table.' SET modifier_id = "'.encode(AS_DB,$new_id).'" WHERE modifier_id = "'.encode(AS_DB,$old_id).'";';
         $result = $this->_db_connector->performQuery($query2);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems merging accounts "'.$this->_db_table.'".',E_USER_WARNING);
         } else {
            unset($result);
            unset($query2);
         }
      }

      // deleter id
      $query3 = ' UPDATE '.$this->_db_table.' SET deleter_id = "'.encode(AS_DB,$new_id).'" WHERE deleter_id = "'.encode(AS_DB,$old_id).'";';
      $result = $this->_db_connector->performQuery($query3);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems merging accounts "'.$this->_db_table.'": "'.$this->_dberror.'" from query: "'.$query3.'"',E_USER_WARNING);
      } else {
         unset($result);
         unset($query3);
      }
   }

   function copyDataFromRoomToRoom ($old_id, $new_id, $user_id='', $id_array='') {
      $retour = array();
      $current_date = getCurrentDateTimeInMySQL();

      $query  = '';
      $query .= 'SELECT * FROM '.$this->_db_table.' WHERE context_id="'.encode(AS_DB,$old_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';

      # special for links
      # should be deleted when data clean
      if (DBTable2Type($this->_db_table) == CS_LINK_TYPE) {
         $query.= ' AND to_item_id != "-2"';
      }

      // not group all, is allready in the new room
      if (DBTable2Type($this->_db_table) == CS_LABEL_TYPE) {
         $query.= ' AND name != "ALL"';
      }

      // not root tag, is allready in the new room
      if ( DBTable2Type($this->_db_table) == CS_TAG_TYPE ) {
         $query .= ' AND title != "CS_TAG_ROOT"';
      }


      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            if ( DBTable2Type($this->_db_table) != CS_LINKITEMFILE_TYPE
                 and DBTable2Type($this->_db_table) != CS_LINK_TYPE
                 and DBTable2Type($this->_db_table) != CS_TAG2TAG_TYPE
                 and isset($query_result['item_id'])
                 and !isset($retour[$query_result['item_id']])
               ) {
               $new_item_id = $this->_createItemInItemTable($new_id,DBTable2Type($this->_db_table),$current_date);
            }
            $insert_query  = '';
            $insert_query .= 'INSERT INTO '.$this->_db_table.' SET';
            $first = true;
            $old_item_id = '';
            $do_it = true;
            foreach ($query_result as $key => $value) {
               $value = encode(AS_DB,$value);
               if ($first) {
                  $first = false;
                  $before = ' ';
               } else {
                  $before = ',';
               }
               if ( $key == 'item_id' ) {
                  $old_item_id = $value;
                  if (!empty($retour[$value])) {
                     $insert_query .= $before.$key.'="'.$retour[$value].'"';
                  } elseif (!empty($new_item_id)) {
                     $insert_query .= $before.$key.'="'.encode(AS_DB,$new_item_id).'"';
                  } else {
                     $do_it = false;
                  }
               } elseif ($key == 'context_id') {
                  $insert_query .= $before.$key.'="'.encode(AS_DB,$new_id).'"';
               } elseif ( $key == 'modification_date'
                          or $key == 'creation_date'
                        ) {
                  $insert_query .= $before.$key.'="'.$current_date.'"';
               } elseif ( !empty($user_id)
                          and ( $key == 'creator_id'
                                or $key == 'modifier_id' )
                        ) {
                  $insert_query .= $before.$key.'="'.encode(AS_DB,$user_id).'"';
               } elseif ( $key == 'deletion_date'
                          or $key == 'deleter_id'
                        ) {
                  // do nothing
               }

               // special for ANNOTATION
               elseif ( $key == 'linked_item_id'
                        and DBTable2Type($this->_db_table) == CS_ANNOTATION_TYPE
                        and isset($id_array[$value])
                      ) {
                  $insert_query .= $before.$key.'="'.$id_array[$value].'"';
               }

               // special for DISCUSSIONARTICLE
               elseif ( $key == 'discussion_id'
                        and DBTable2Type($this->_db_table) == CS_DISCARTICLE_TYPE
                        and isset($id_array[$value])
                      ) {
                  $insert_query .= $before.$key.'="'.$id_array[$value].'"';
               }

               // special for SECTION
               elseif ( $key == 'material_item_id'
                        and DBTable2Type($this->_db_table) == CS_SECTION_TYPE
                        and isset($id_array[$value])
                      ) {
                  $insert_query .= $before.$key.'="'.$id_array[$value].'"';
               }

               // special for LINKS / TAG2TAG
               elseif ( ( $key == 'from_item_id'
                          or $key == 'to_item_id'
                        ) and ( DBTable2Type($this->_db_table) == CS_LINK_TYPE
                                or DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE
                              )
                      ) {
                  if ( isset($id_array[$value]) ) {
                     $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                  } else {
                     $do_it = false;
                  }
               }

               // special for TAG2TAG
               elseif ( $key == 'link_id'
                        and DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE
                      ) {
                  // link_id is primary key so don't insert it
               }

               // special for LINK_ITEM
               elseif ( ( $key == 'first_item_id' or $key == 'second_item_id' )
                          and DBTable2Type($this->_db_table) == CS_LINKITEM_TYPE
                      ) {
                  if ( isset($id_array[$value]) ) {
                     $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                  } else {
                     $do_it = false;
                  }
               }

               // special for MATERIAL
               elseif ( $key == 'copy_of'
                        and empty($value)
                        and DBTable2Type($this->_db_table) == CS_MATERIAL_TYPE
                      ) {
                  $insert_query .= $before.$key.'=NULL';
               }

               // default
               else {
                  $insert_query .= $before.$key.'="'.encode(AS_DB,encode(FROM_DB,$value)).'"';
               }
            }
            if (!$do_it) {
               $do_it = true;
            } else {
               $insert_query = str_replace('SET,','SET ',$insert_query);
               $result_insert = $this->_db_connector->performQuery($insert_query);
               if ( !isset($result_insert) ) {
                  include_once('functions/error_functions.php');
                  trigger_error('Problem creating item.',E_USER_ERROR);
               } else {
                  if (!empty($old_item_id)) {
                     if (!empty($new_item_id)) {
                        if (DBTable2Type($this->_db_table) == CS_FILE_TYPE) {
                           $retour[CS_FILE_TYPE.$old_item_id] = $new_item_id;
                        } else {
                           $retour[$old_item_id] = $new_item_id;
                        }
                        unset($new_item_id);
                     }
                     unset($old_item_id);
                  }
               }
               unset($result_insert);
               unset($insert_query);
            }
         }
         unset($result);
      }
      return $retour;
   }

   public function refreshInDescLinks ($context_id, $id_array) {
      $query  = '';
      $query .= 'SELECT item_id, description FROM '.$this->_db_table.' WHERE context_id="'.encode(AS_DB,$context_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $item_id = $query_result['item_id'];
            $desc = $query_result['description'];
            preg_match_all('/\[[0-9]*(\]|\|)/', $query_result['description'], $matches);
            if ( isset($matches[0]) ) {
               foreach ($matches[0] as $match) {
                  $id = substr($match,1);
                  $last_char = substr($id,strlen($id));
                  $id = substr($id,0,strlen($id)-1);
                  if ( isset($id_array[$id]) ) {
                     $desc = str_replace('['.$id.$last_char,'['.$id_array[$id].$last_char,$desc);
                  }
               }
            }
            $query = 'UPDATE '.$this->_db_table.' SET description="'.encode(AS_DB,$desc).'" WHERE item_id='.encode(AS_DB,$item_id);
            $result_update = $this->_db_connector->performQuery($query);
            if ( !isset($result_update) or !$result_update ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems refresh links in description "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               unset($result_update);
            }
         }
         unset($result);
      }
   }

   function _createItemInItemTable ($context_id, $type, $date) {
      $retour = '';
      $query = 'INSERT INTO items SET '.
             'context_id="'.encode(AS_DB,$context_id).'",'.
             'modification_date="'.encode(AS_DB,$date).'",'.
             'type="'.$type.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem creating item.',E_USER_ERROR);
      } else {
         $retour = $result;
         unset($result);
      }
      return $retour;
   }

   function deleteReallyOlderThan ($days) {
      $retour = false;
      $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
      $query = 'DELETE FROM '.$this->_db_table.' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem deleting items.',E_USER_ERROR);
      } else {
         unset($result);
         $retour = true;
      }
      return $retour;
   }

   function getLastQuery() {
      return $this->_last_query;
   }


   function backupDataFromXMLObject ($xml_object) {
      $major_success = true;

      if ( isset($xml_object) and !empty($xml_object) ) {
         foreach ($xml_object->children() as $xml_item) {
            $data_array = array();
            foreach ($xml_item->children() as $xml_element) {
               $value = utf8_decode((string)$xml_element);
               if ($xml_element->getName() == 'extras') {
                  include_once('functions/text_functions.php');
                  $value = cs_unserialize($value);
               }
               if ( !empty($value) ) {
                  $value = str_replace('lt_commsy_export','<',$value);
                  $value = str_replace('gt_commsy_export','>',$value);
                  $value = str_replace('and_commsy_export','&',$value);
                  $data_array[$xml_element->getName()] = $value;
               } elseif ( isset($value) ) {
                  $data_array[$xml_element->getName()] = '0';
               }
               unset($xml_element);
            }
            if ( isset($data_array) and !empty($data_array) ) {
               $success = $this->_updateFromBackup($data_array);
               $major_success = $major_success and $success;
            }
            unset($xml_item);
         }
      }

      return $major_success;
   }

   function _updateFromBackup ( $data_array ) {

      $success = false;

      // get columns form database table
      if ( !isset($this->_key_array) ) {
         $query = 'SHOW COLUMNS FROM '.$this->_db_table;
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problem get colums from table '.$this->_db_table.'.',E_USER_ERROR);
         } else {
            $this->_key_array = array();
            foreach ($result as $query_result) {
               $this->_key_array[] = $query_result['Field'];
            }
            unset($result);
         }
      }

      // perform update
      $query  = '';
      $query .= 'UPDATE '.$this->_db_table.'';

      $query .= ' SET ';
      $first = true;

      foreach ($data_array as $key => $value) {
         if ( $key != 'item_id'
              and $key != 'files_id'
              and $key != 'version_id'
              and in_array($key,$this->_key_array)
            ) {
            if ($first) {
               $first = false;
            } else {
               $query .= ',';
            }
            $query .= $key.'="'.encode(AS_DB,$value).'"';
         }
      }

      if ( !isset($data_array['deleter_id']) or empty($data_array['deleter_id']) ) {
         $query .= ',deleter_id=NULL';
      }
      if ( !isset($data_array['deletion_date']) or empty($data_array['deletion_date']) ) {
         $query .= ',deletion_date=NULL';
      }

      if ( DBTable2Type($this->_db_table) == CS_FILE_TYPE ) {
         $query .= ' WHERE files_id="'.encode(AS_DB,$data_array['files_id']).'"';
      } elseif ( DBTable2Type($this->_db_table) == CS_LINKHOMEPAGEHOMEPAGE_TYPE
                 or DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE
               ) {
         $query .= ' WHERE link_id="'.encode(AS_DB,$data_array['link_id']).'"';
      } else {
         $query .= ' WHERE item_id="'.encode(AS_DB,$data_array['item_id']).'"';
      }
      if ( isset($data_array['version_id']) ) {
         $query .= ' AND version_id="'.encode(AS_DB,$data_array['version_id']).'"';
      } elseif (DBTable2Type($this->_db_table) == CS_MATERIAL_TYPE) {
         $query .= ' AND version_id="0"';
      }
      $query .= ';';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem backuping item.',E_USER_ERROR);
      } else {
         $success = true;
         unset($result);
      }

      return $success;
   }

  /**
   * method to init and perform ftsearch queries
   *
   * @return string SQL-statement as result of ftsearch
   */
   function initFTSearch() {
      if ( !empty($this->_search_array) ) {
         // get FTSearchManager
         $ftsearch_manager = $this->_environment->getFTSearchManager();
         // set search status for
         $ftsearch_manager->setSearchStatus(true);
         //set search words
         $ftsearch_manager->setWords($this->_search_array);
         // ... and perform search
         $ft_result = $ftsearch_manager->performFTSearch();
         unset($ftsearch_manager);
         if ( isset($ft_result) and !empty($ft_result) ) {
            // combine sql statment
            include_once('functions/misc_functions.php');
            if ( $this->_db_table == type2Table(CS_DISCUSSION_TYPE) ) {
               $table = type2table(CS_DISCARTICLE_TYPE);
            } else {
               $table = $this->_db_table;
            }
            $ft_sql_result = ' OR (' . $table . '.item_id IN (';
            for ($i = 0; $i < count($ft_result) - 1; $i++) {
               $ft_sql_result .= $ft_result[$i] . ',';
            }
            $ft_sql_result .= $ft_result[count($ft_result) - 1] . '))';
            if ( $this->_db_table == type2Table(CS_MATERIAL_TYPE) ) {
               $ft_sql_result .= ' OR (' . type2Table(CS_SECTION_TYPE) . '.item_id IN (';
               for ($i = 0; $i < count($ft_result) - 1; $i++) {
                  $ft_sql_result .= $ft_result[$i] . ',';
               }
               $ft_sql_result .= $ft_result[count($ft_result) - 1] . '))';
            }
            unset($ft_result);
            return $ft_sql_result;
         }
      } else {
         return '';
      }
   }

   public function existsItem ( $item_id ) {
      $retour = false;
      if ( !empty($item_id) ) {
         $query = 'SELECT item_id FROM '.$this->_db_table;
         $query .= ' WHERE item_id = "'.encode(AS_DB,$item_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one label.',E_USER_WARNING);
         } elseif ( !empty($result[0]) ) {
            $retour = true;
         }
      }
      return $retour;
   }
}
?>