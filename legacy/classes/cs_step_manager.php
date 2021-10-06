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


/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_step_list.php');

/** upper class of the stepq manager
 */
include_once('classes/cs_manager.php');

/** cs_step_item is needed to create step items
 */
include_once('classes/cs_step_item.php');

include_once('functions/text_functions.php');

/** class for database connection to the database table "step"
 * this class implements a database manager for the table "step"
 * @version 2.1 $Revision$
 */

class cs_step_manager extends cs_manager implements cs_export_import_interface {

   /**
   * integer - containing a start point for the select step
   */
   var $_from_limit = NULL;

   /**
   * integer - containing how many step the select statement should get
   */
   var $_interval_limit = NULL;

   /**
    * string - containing a string as a search limit
    */
   var $_search_limit = NULL;

   /**
   *  array - containing an id-array as search limit
   */
   var $_id_array_limit = array();

   /**
   *  int - containing an item_id as search limit
   */
   var $_todo_item_id_limit = 0;

   /**
   *  int - containing an version_id as search limit
   */
   var $_version_id_limit = 0;

   /**
   * bool - tells if the next step will be saved without setting new modification date
   */
   var $_save_step_without_date = false;

   var $_all_step_list = NULL;
   var $_cached_todo_item_ids = array();

   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor: cs_step_manager
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = CS_STEP_TYPE;
      $this->_translator = $environment->getTranslationObject();
   }

    /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    *
    * @version $Revision$
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->reset_search_limit();
      $this->_order = NULL;
      $this->_todo_item_id_limit = 0;
      $this->_version_id_limit = 0;
   }

   /** reset search limit
    * reset the limit of this class: search limit
    *
    * @author CommSy Development Group
    */
   function reset_search_limit () {
      $this->_search_limit = NULL;
   }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected step
    * @param integer interval interval limit for selected step
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

   /**
   * tells to save the next step without a new modifying date
   */
   function setSaveStepWithoutDate() {
      $this->_save_step_without_date = true;
   }

   /** set todo_item_id limit
    * this method sets an refid limit for the select statement
    *
    * @param string limit order limit
    */
  function setTodoItemIDLimit ($limit) {
     $this->_todo_item_id_limit = (int)$limit;
  }

   /** set search limit
    * this method sets a search limit for step
    *
    * @param string limit search limit for step
    */
   function setSearchLimit ($limit){
     $this->_search_limit = addcslashes(encode(AS_DB,(string)$limit),"%");
     $this->_search_limit = cs_strtoupper($this->_search_limit);
   }

   function getIDs () {
      return $this->getIDArray();
   }

   function _performQuery($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->addDatabasePrefix('step').'.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('step').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('step').'.*';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('step');
     $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('step').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

     $query .= ' WHERE 1';

     // fifth, insert limits into the select statement
     if ( isset($this->_todo_item_id_limit) and !empty($this->_todo_item_id_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('step').'.todo_item_id='.encode(AS_DB,$this->_todo_item_id_limit);
     }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('step').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND '.$this->addDatabasePrefix('step').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('step').'.deleter_id IS NULL';
     }
     if (!empty($this->_id_array_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('step').'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('step').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
      if ( isset($this->_age_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('step').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
              // restrict sql-statement by search limit, create wheres
     if (isset($this->_search_limit) AND !empty($this->_search_limit)) {
        $query .= ' AND (';

        // todo item
        $query .= ' UPPER('.$this->addDatabasePrefix('step').'.title) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        $query .= ' OR UPPER('.$this->addDatabasePrefix('step').'.description) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        if ( $this->_search_limit != ':' and $this->_search_limit != '-' ) {
           $query .= ' OR UPPER('.$this->addDatabasePrefix('step').'.modification_date) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        }

        // creation date - modification date language problem (TBD)

        // creator and modificator
        $query .= ' OR UPPER(TRIM(CONCAT(people.firstname," ",people.lastname))) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';

        // groups
        $query .= ' OR UPPER(groups.name) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        $query .= ' )';
     }

      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }

     if (isset($this->_search_limit) AND !empty($this->_search_limit)) {
        $query .= ' GROUP BY '.$this->addDatabasePrefix('step').'.item_id';
     }
     $query .= ' ORDER BY '.$this->addDatabasePrefix('step').'.item_id ASC, '.$this->addDatabasePrefix('step').'.modification_date DESC, '.$this->addDatabasePrefix('step').'.title DESC';

      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }

      // perform query
      if ( isset($this->_cached_sql[$query]) ) {
         $result = $this->_cached_sql[$query];
      } else {
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting step from query: "'.$query.'"',E_USER_WARNING);
         } else {
              // sql caching
              if ( $this->_cache_on ) {
                 $this->_cached_sql[$query] = $result;
              }
         }
      }
       return $result;
   }

   /** build a new todo item
    * this method returns a new EMTPY todo item
    *
    * @return object cs_item a new EMPTY todo
    */
   function getNewItem () {
      return new cs_step_item($this->_environment);
   }

    function getItem ($item_id) {
        $step = NULL;
        if ( !empty($this->_cache_object[$item_id]) ) {
             $step = $this->_cache_object[$item_id];
        } else {
           $query = "SELECT * FROM ".$this->addDatabasePrefix("step")." WHERE ".$this->addDatabasePrefix("step").".item_id = '".encode(AS_DB,$item_id)."'";
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result) or empty($result[0])) {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting one step item from query: "'.$query.'"',E_USER_WARNING);
           } else {
              $step = $this->_buildItem($result[0]);
           }
        }
        return $step;
     }

  /** get a list of step in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_step_items
    */
   function getItemList ($id_array) {
      if (empty($id_array)) {
         return new cs_step_list();
      } else {
         $step = NULL;
         $query = "SELECT * FROM ".$this->addDatabasePrefix("step")." WHERE ".$this->addDatabasePrefix("step").".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " ORDER BY ".$this->addDatabasePrefix("step").".item_id";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');trigger_error('Problems selecting list of step items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $step_list = new cs_step_list();
            foreach ($result as $rs) {
               $step_list->append($this->_buildItem($rs));
            }
         }
         return $step_list;
      }
   }

  /** get a list of step in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_step_items
    */
   function getAllStepItemListByIDArray ($id_array) {
      if (empty($id_array)) {
         return new cs_step_list();
      } else {
         $step = NULL;
         $query = "SELECT * FROM ".$this->addDatabasePrefix("step")." WHERE todo_item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " AND ".$this->addDatabasePrefix("step").".deleter_id IS NULL";
         $query .= " AND ".$this->addDatabasePrefix("step").".deletion_date IS NULL";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of step items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $step_list = new cs_step_list();
            foreach ($result as $rs) {
               $step_item = $this->_buildItem($rs);
               if ( isset($step_item) ) {
                  $step_list->append($step_item);
               }
               unset($step_item);
            }
         }
         if ( $this->_cache_on ) {
            $this->_all_step_list = $step_list;
            $this->_cached_todo_item_ids = $id_array;
         }
         return $step_list;
      }
   }


  /** update a step - internal, do not use -> use method save
   * this method updates the database record for a given step item
   *
   * @param cs_step_item the step item for which an update should be made
   * @param bool can disable setting of new modification date
   */
   function _update ($item) {
        $date_string = '';
        if (!$this->_save_step_without_date) {
           parent::_update($item);
           $date_string = 'modification_date="'.getCurrentDateTimeInMySQL().'",';
        }
        $modificator_item = $item->getModificatorItem();

        $query = 'UPDATE '.$this->addDatabasePrefix('step').' SET '.
              $date_string.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'description="'.encode(AS_DB,$item->getDescription()).'",'.
              'minutes="'.encode(AS_DB,$item->getMinutes()).'",'.
              'time_type="'.encode(AS_DB,$item->getTimeType()).'",'.
              'todo_item_id="'.encode(AS_DB,$item->getTodoID()).'",'.
              'modifier_id="'.encode(AS_DB,$modificator_item->getItemID()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
     // extras (TBD)

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating step from query: "'.$query.'"',E_USER_WARNING);
     }
     $this->_save_step_without_date = false; //restore default
     unset($item);
   }

   /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'nstep' in the database and sets the step items item id.
   * it then calls the private method _newNews to store the step item itself.
   * @param cs_step_item the step item for which an entry should be made
   */
  function _create ($item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="step",'.
              'draft="'.encode(AS_DB,$item->isDraft()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating step from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newStep($item);
     }
     unset($item);
  }

  /** store a new step item to the database - internal, do not use -> use method save
    * this method stores a newly created step item to the database
    *
    * @param cs_step_item the step item to be stored
    */
  function _newStep ($item) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $query = 'INSERT INTO '.$this->addDatabasePrefix('step').' SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$item->getCreatorID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'description="'.encode(AS_DB,$item->getDescription()).'",'.
               $this->returnQuerySentenceIfFieldIsValid($item->getMinutes(), "minutes").
              'time_type="'.encode(AS_DB,$item->getTimeType()).'",'.
              'todo_item_id="'.encode(AS_DB,$item->getTodoID()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating step from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($item);
  }

   /**  delete a step item
   *
   * @param cs_step_item the step item to be deleted
   *
   * @access public
   */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID() ?: 0;
      $query = 'UPDATE '.$this->addDatabasePrefix('step').' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting step from query: "'.$query.'"',E_USER_WARNING);
      } else {
         parent::delete($item_id);
      }
   }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item,$with_date=true) {
     $item_id = $item->getItemID();
     if (!empty($item_id)) {
        if ($item->_version_id_changed){
           $this->_newStep($item);
        }else{
           $this->_update($item,$with_date);
        }
     } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $item->setCreatorItem($this->_environment->getCurrentUser());
        }
        $this->_create($item);
     }

     //Add modifier to all users who ever edited this step
     $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
     $link_modifier_item_manager->markEdited($item->getItemID());
     unset($item);
     unset($link_modifier_item_manager);
  }

  /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select() {
      $result = $this->_performQuery();
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>';
      } else {
         $this->_data = new cs_step_list();
      }
      $this->_id_array = NULL;
      foreach ($result as $query_result) {
         if ( isset($this->_output_limit)
              and !empty($this->_output_limit)
              and $this->_output_limit == 'XML'
            ) {
            if ( isset($query_result)
                 and !empty($query_result) ) {
               $this->_data .= '<'.$this->_db_table.'_item>';
               foreach ($query_result as $key => $value) {
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  if ( $key == 'extras' ) {
                     $value = serialize($value);
                  }
                  $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
               }
               $this->_data .= '</'.$this->_db_table.'_item>';
            }
         } else {
            $item = $this->_buildItem($query_result);
            $this->_data->set($item);
            unset($item);
         }
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>';
      }
   }

    function deleteStepsOfUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('step').'.* FROM ' . $this->addDatabasePrefix('step').' WHERE ' . $this->addDatabasePrefix('step') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('step') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'FLAG') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === 'FALSE') {
                        $updateQuery .= ' title = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB,$rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        trigger_error('Problems automatic deleting steps from query: "' . $updateQuery . '"',E_USER_WARNING);
                    }
                }
            }
        }
    }
	
	function export_item($id) {
	   $item = $this->getItem($id);
	
   	$xml = new SimpleXMLElementExtended('<step_item></step_item>');
   	$xml->addChildWithCDATA('item_id', $item->getItemID());
      $xml->addChildWithCDATA('context_id', $item->getContextID());
      $xml->addChildWithCDATA('creator_id', $item->getCreatorID());
      $xml->addChildWithCDATA('modifier_id', $item->getModificatorID());
      $xml->addChildWithCDATA('creation_date', $item->getCreationDate());
      $xml->addChildWithCDATA('deleter_id', $item->getDeleterID());
      $xml->addChildWithCDATA('deletion_date', $item->getDeleterID());
      $xml->addChildWithCDATA('modification_date', $item->getModificationDate());
      $xml->addChildWithCDATA('title', $item->getTitle());
      $xml->addChildWithCDATA('description', $item->getDescription());
      $xml->addChildWithCDATA('minutes', $item->getMinutes());
      $xml->addChildWithCDATA('time_type', $item->getTimeType());
      $xml->addChildWithCDATA('todo_item_id', $item->getTodoID());

   	$extras_array = $item->getExtraInformation();
      $xmlExtras = $this->getArrayAsXML($xml, $extras_array, true, 'extras');
      $this->simplexml_import_simplexml($xml, $xmlExtras);
   
      $xml->addChildWithCDATA('public', $item->isPublic());
   
      $xmlFiles = $this->getFilesAsXML($item->getItemID());
      $this->simplexml_import_simplexml($xml, $xmlFiles);
   
   	return $xml;
	}
	
   function export_sub_items($xml, $top_item) {
      
   }
   
   function import_item($xml, $top_item, &$options) {
      $item = null;
      if ($xml != null) {
         $item = $this->getNewItem();
         $item->setTitle((string)$xml->title[0]);
         $item->setContextId($top_item->getContextId());
         $item->setDescription((string)$xml->description[0]);
         $item->setMinutes((string)$xml->minutes[0]);
         $item->setTimeType((string)$xml->time_type[0]);
         $item->setTodoID($top_item->getItemId());
         //$item->setPublic((string)$xml->public[0]);
         $extra_array = $this->getXMLAsArray($xml->extras);
         $item->setExtraInformation($extra_array['extras']);
         $item->save();
      }
      return $item;
   }
   
   function import_sub_items($xml, $top_item, &$options) {
      
   }
}
?>