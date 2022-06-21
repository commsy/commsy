<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/**
 */

use Doctrine\DBAL\Schema\Column;

include_once('custom/SimpleXMLElementExtended.php');
include_once('functions/date_functions.php');

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
    protected $_current_user;

    /**
     * integer - containing the room id as a limit for select statements
     */
    var $_room_limit = null;
    var $_room_array_limit = null;

    /**
     * String - containing the attribute as a limit for select statements
     */
    var $_attribute_limit = null;

    /**
     * boolean - true: then all deleted items where not observed in select statements, false: then all deleted items where observed in select statements
     */
    var $_delete_limit = true;

    /**
     * object cs_list - contains stored commsy items
     */
    var $_data = null;

    /**
     * Environment - the environment of the CommSy
     */
    protected cs_environment $_environment;

    /**
     * id_array for item_ids
     */
    var $_id_array = null;

    /**
     * integer - containing the item id of the ref item as a limit
     */
    var $_ref_id_limit = null;

    /**
     * integer - containing the item id of the user as a limit
     */
    var $_ref_user_limit = null;

    /**
     * integer - max number of days since creation of item
     */
    var $_existence_limit = null;
    var $_age_limit = null;

    /**
     * @var \DateTime
     */
    protected $creationNewerThenLimit = null;

    /**
     * @var \DateTime
     */
    protected $modificationOlderThenLimit = null;

    /**
     * @var \DateTime
     */
    protected $modificationNewerThenLimit = null;

    /**
     * @var int[]
     */
    protected $excludedIdsLimit = [];

    protected $inactiveEntriesLimit = self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED;

    var $_update_with_changing_modification_information = true;

    var $_db_table = null;

    /**
     * Array containing search limits
     */
    var $_search_array = array();

    var $_tag_limit = null;
    var $_tag_limit_array = null;
    var $_buzzword_limit = null;
    var $_user_limit = null;

    /**
     * Array containing negative search limits (words that mustn't occure)
     */
    var $_search_negative_array = array();

    /**
     * Stores last query if method assigns string
     */
    var $_last_query = '';

    var $_output_limit = '';
    var $_key_array = null;
    var $_only_files_limit = null;
    protected db_mysql_connector $_db_connector;

    var $_cached_items = array();
    var $_cache_object = array();
    var $_cache_on = true;
    var $_cached_sql = array();

    public $_class_factory = null;

    protected $_id_array_limit = null;

    public $_link_modifier = true;
    public $_db_prefix = '';
    public $_with_db_prefix = true;

    var $_force_sql = false;

    public const SHOW_ENTRIES_ONLY_ACTIVATED = 'only.activated';
    public const SHOW_ENTRIES_ONLY_DEACTIVATED = 'only.deactivated';
    public const SHOW_ENTRIES_ACTIVATED_DEACTIVATED = 'either';

    /** constructor: cs_manager
     * the only available constructor, initial values for internal variables. sets room limit to room
     *
     * @param object cs_environment the environment
     */
    public function __construct(cs_environment $environment)
    {
        $this->_environment = $environment;
        $this->_class_factory = $this->_environment->getClassFactory();
        $this->reset();
        $this->_room_limit = $this->_environment->getCurrentContextID();
        $this->_attribute_limit = null;
        $this->_current_user = $this->_environment->getCurrentUser();
        $this->_db_connector = $this->_environment->getDBConnector();
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

   public function forceSQL () {
      $this->_force_sql = true;
   }

   public function setSaveWithoutLinkModifier () {
      $this->_link_modifier = false;
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
     $this->_delete_limit = true;
     $this->_update_with_changing_modification_information = true;
     $this->_output_limit = '';
     $this->_only_files_limit = NULL;
     $this->_room_array_limit = NULL;
     $this->inactiveEntriesLimit = self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED;
     $this->_id_array_limit = NULL;
     $this->modificationOlderThenLimit = null;
     $this->modificationNewerThenLimit = null;
     $this->creationNewerThenLimit = null;
     $this->excludedIdsLimit = [];
  }

  /** reset data
    * reset data of this class: reset list of items
    */
  function resetData () {
     $this->_data = NULL;
     $this->_id_array = NULL;
  }

   /** set limit to array of announcement item_ids
    *
    * @param array array of ids to be loaded from db
    */
   function setIDArrayLimit ($id_array) {
   	if ( is_array($id_array) ) {
   		// remove NULL, FALSE and Empty Strings (""), but leave values of 0 (zero)
   		$id_array = array_filter( $id_array, 'strlen' );
   	}
      $this->_id_array_limit = (array)$id_array;
   }

    /**
     * Set a limit to show only activate, inactive or both items
     *
     * @param string $limit
     * @return $this
     */
    public function setInactiveEntriesLimit(string $limit): self
    {
       if (
           $limit !== self::SHOW_ENTRIES_ONLY_ACTIVATED &&
           $limit !== self::SHOW_ENTRIES_ONLY_DEACTIVATED &&
           $limit !== self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED
       ) {
           throw new InvalidArgumentException('unknown limit given');
       }

       $this->inactiveEntriesLimit = $limit;

       return $this;
    }

   function setBuzzwordLimit ($limit) {
      $this->_buzzword_limit = (int)$limit;
   }

   function setTagLimit ($limit) {
      $this->_tag_limit = (array) $limit;
   }

   function setTagArrayLimit ($limit) {
   	 $this->_tag_limit = $limit;
   }

   function _getTagIDArrayByTagIDArray($array) {
   	 $id_array = array();
   	 $first_element = array();
   	 $tag2tag_manager = $this->_environment->getTag2TagManager();
   	 foreach($array as $key => $value) {
   	 	$output = preg_replace( '/[^0-9]/', '', $value );
   	 	if(!empty($output)){
   	 		$first_element[] = $value;
   	 	} else {
   	 		$first_element[] = substr($key, 7);
   	 		if($array[$key]){
   	 			$id_array = array_unique(array_merge($id_array,$tag2tag_manager->getRecursiveChildrenItemIDArray(substr($key, 7))));
   	 		}
   	 	}
   	 }
   	 $id_array = array_merge($id_array,$first_element);
   	 #pr($id_array);
   	 return $id_array;
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
      preg_match_all('~(\\"(.+?)\\\")~u',$limit,$literal_array);
      //delete this occurances from the original string
      $limit = preg_replace('~(\\\"(.+?)\\\")~u','',$limit);

      preg_match_all('~\s-([\w'.SPECIAL_CHARS.']+)~u',$limit,$this->_search_negative_array);
      $limit = preg_replace('~\s-([\w'.SPECIAL_CHARS.']+)~u','',$limit);

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

   /**
    * Helper function to provide sql code for search limit
    *
    * @param Array with names of DB-Fields where search must find a match
    * @param Array with names of fields and additional checks that must match
    */
    function _generateSearchLimitCode($fieldArray, $checkedFieldArray = array())
    {
        $searchLimitQuery = '';

        // criteria that must match
        $numSearchArray = count($this->_search_array);
        for ($i=0; $i < $numSearchArray; $i++) {
            $search = $this->_search_array[$i];

            $searchLimitQuery .= '(';

            $numFieldArray = count($fieldArray);
            for ($j=0; $j < $numFieldArray; $j++) {
                $field = $fieldArray[$j];

                $searchLimitQuery .= 'UPPER(' . $field . ') LIKE BINARY "%' . encode(AS_DB, mb_strtoupper(htmlentities($search, ENT_NOQUOTES, 'UTF-8'), 'UTF-8')) . '%"';
                $searchLimitQuery .= ' OR ';
                $searchLimitQuery .= 'UPPER(' . $field . ') LIKE BINARY "%' . encode(AS_DB, mb_strtoupper($search, 'UTF-8')) . '%"';

                if ($j+1 < $numFieldArray) {
                    $searchLimitQuery .= ' OR ';
                }
            }

            // criteria that must match with additional checks
            if (!empty($checkedFieldArray)) {
                $searchLimitQuery .= ' OR (';

                $numCheckedFields = count($checkedFieldArray['fields']);
                if ($numCheckedFields > 0) {
                    $searchLimitQuery .= '(';

                    for ($k=0; $k < $numCheckedFields; $k++) {
                        $checkedField = $checkedFieldArray['fields'][$k];

                        $searchLimitQuery .= 'UPPER(' . $checkedField . ') LIKE BINARY "%' . encode(AS_DB, mb_strtoupper(htmlentities($search, ENT_NOQUOTES, 'UTF-8'), 'UTF-8')) . '%"';
                        $searchLimitQuery .= ' OR ';
                        $searchLimitQuery .= 'UPPER(' . $checkedField . ') LIKE BINARY "%' . encode(AS_DB, mb_strtoupper($search, 'UTF-8')) . '%"';

                        if ($k+1 < $numCheckedFields) {
                            $searchLimitQuery .= ' OR ';
                        }
                    }

                    $searchLimitQuery .= ')';
                }

                $numCheckedFieldChecks = count($checkedFieldArray['checks']);
                if ($numCheckedFieldChecks > 0) {
                    $searchLimitQuery .= ' AND ';

                    for ($k=0; $k < $numCheckedFieldChecks; $k++) {
                        $checkedFieldCheck = $checkedFieldArray['checks'][$k];

                        $searchLimitQuery .= $checkedFieldCheck;

                        if ($k+1 < $numCheckedFieldChecks) {
                            $searchLimitQuery .= ' AND ';
                        }
                    }
                }

                $searchLimitQuery .= ')';
            }

            if ($i+1 < $numSearchArray) {
                $searchLimitQuery .= ') AND ';
            }
        }

        $searchLimitQuery .= ')';

        // criteria that must not match
        if (count($this->_search_negative_array[1]) > 0) {
            $searchLimitQuery .= ' AND ';

            $numNegativeArray = sizeof($this->_search_negative_array[1]);
            for ($i=0; $i < $numNegativeArray[1]; $i++) {
                $negativeSearch = $this->_search_negative_array[1][$i];

                $searchLimitQuery .= '(';

                $numFieldArray = count($fieldArray);
                for ($j=0; $j < $numFieldArray; $j++) {
                    $field = $fieldArray[$j];

                    $searchLimitQuery .= '(';
                    $searchLimitQuery .= 'UPPER(' . $field . ') NOT LIKE BINARY "%' . encode(AS_DB, $negativeSearch) . '%"';
                    $searchLimitQuery .= ' AND ';
                    $searchLimitQuery .= 'UPPER(' . $field . ') NOT LIKE BINARY "%' . encode(AS_DB, mb_strtoupper(htmlentities($search, ENT_NOQUOTES, 'UTF-8'), 'UTF-8')) . '%"';
                    $searchLimitQuery .= ')';

                    if ($j+1 < $numFieldArray) {
                        $searchLimitQuery .= ' AND ';
                    }
                }

                if ($i+1 < $numNegativeArray) {
                    $searchLimitQuery .= ') AND ';
                }
            }

            $searchLimitQuery .= ')';
        }

        return $searchLimitQuery;
    }

   function setUserLimit ($limit) {
      $this->_user_limit = (int)$limit;
   }

   /** set context limit
    * this method sets a context limit
    *
    * @param integer limit id of the context
    */
   function setContextLimit ($limit) {
      $this->_room_limit = (int)$limit;
   }

   function unsetContextLimit () {
      $this->_room_limit = NULL;
   }

   function setContextArrayLimit($limit) {
      $this->_room_array_limit = $limit;
   }

   function setRubricLimit($type, $limit){
      switch($type){
         case CS_TOPIC_TYPE: $this->setTopicLimit($limit);break;
         case CS_GROUP_TYPE: $this->setGroupLimit($limit);break;
         case CS_USER_TYPE: $this->setUserLimit($limit);break;
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

   public function setModificationOlderThenLimit(\DateTime $olderThen) {
       $this->modificationOlderThenLimit = $olderThen;
   }

   public function setModificationNewerThenLimit(\DateTime $newerThen) {
       $this->modificationNewerThenLimit = $newerThen;
   }

   public function setCreationNewerThenLimit(\DateTime $newerThen) {
       $this->creationNewerThenLimit = $newerThen;
   }

   public function setExcludedIdsLimit($ids) {
       $this->excludedIdsLimit = $ids;
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
     return $this->_db_connector->getErrno();
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
     * @return cs_list|null list of commsy items
     */
    public function get(): ?\cs_list
    {
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


   function _existsField ( $table, $field ) {
      $retour = false;
      $sql = 'SHOW COLUMNS FROM '.$this->addDatabasePrefix($table);
      if ( empty($this->_cached_sql[$sql]) ) {
         $db = $this->_environment->getDBConnector();
         $result = $db->performQuery($sql);
         if ($this->_cache_on) {
            $this->_cached_sql[$sql] = $result;
         }
      } else {
         $result = $this->_cached_sql[$sql];
      }
      foreach ( $result as $field_array ) {
         if ( !empty($field_array)
              and !empty($field_array['Field'])
              and $field_array['Field'] == $field
            ) {
            $retour = true;
            break;
         }
      }
      return $retour;
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
         $query = "SELECT * FROM ".encode(AS_DB,$this->addDatabasePrefix($type))." WHERE ".encode(AS_DB,$this->addDatabasePrefix($type)).".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$type.' items.',E_USER_WARNING);
         } else {
            $list = new cs_list();
            foreach ($result as $rs ) {
               // special for todo
               if ( $type == 'todos' and isset($rs['date']) ){
                  $rs['end_date'] = $rs['date'];
                  unset($rs['date']);
               }
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
      $query = 'UPDATE '.$this->addDatabasePrefix('items').' SET';
      $modification_date = getCurrentDateTimeInMySQL();
      $activation_date = getCurrentDateTimeInMySQL();
      if ($item->isNotActivated()){
          $activation_date = $item->getActivationDate();
      }
      if ( $item->isChangeModificationOnSave()
           or $this->_update_with_changing_modification_information
         ) {
         $query .= ' modification_date="'.$modification_date.'",';
         $query .= ' activation_date="'.$activation_date.'",';
      }
      $query .= ' context_id="'.encode(AS_DB,$item->getContextID()).'"';
      if (get_class($item) == 'cs_item') {
          $query .= ', draft="' . encode(AS_DB, $item->isDraft()) . '"';
      }
      $query .= ' WHERE item_id = "'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating item in table items.',E_USER_WARNING);
      } else {
         unset($result);
      }
      unset($query);

      global $symfonyContainer;
      $checkLocking = $symfonyContainer->getParameter('commsy.settings.item_locking');

      if ($checkLocking && $item->hasLocking()) {
          $item->unlock();
      }

      unset($item);
   }

    /** delete a commsy item
     * this method deletes a commsy item
     *
     * @param integer item_id the item id of the commsy item
     */
    public function delete($itemId)
    {
        $currentDatetime = getCurrentDateTimeInMySQL();
        $currentUser = $this->_environment->getCurrentUserItem();
        $deleterId = ($currentUser->getItemID() !== '') ? $currentUser->getItemID() : 0;
        $query = 'UPDATE ' . $this->addDatabasePrefix('items') . ' SET ' .
            'deletion_date="' . $currentDatetime . '",' .
            'deleter_id="' . encode(AS_DB, $deleterId) . '"' .
            ' WHERE item_id="' . $itemId . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) || !$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems deleting item in table items.', E_USER_WARNING);
        }
    }

  function undeleteItemByItemID ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
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
      $query = 'UPDATE '.$this->addDatabasePrefix('items').' SET '.
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
    protected function _buildItem(array $db_array)
    {
        /** @var cs_item $item */
        $item = $this->getNewItem();
        if (isset($item)) {
            $item->_setItemData(encode(FROM_DB, $db_array));

            // archive
            if (function_exists('get_called_class') && strstr(get_called_class(), '_zzz_')) {
                $item->setArchiveStatus();
            }

            if (method_exists($item, 'getItemID')) {
                $item_id = $item->getItemID();
                if (!empty($item_id)) {
                    // external viewer
                    $itemManager = $this->_environment->getItemManager();
                    $externalViewer = $itemManager->getExternalViewerUserArrayForItem($item_id);
                    $item->setExternalViewerAccounts($externalViewer);

                    // cache
                    if (empty($this->_cache_object[$item_id])) {
                        $this->_cache_object[$item_id] = $item;
                    }
                }
            }
        }

        if ($this->_cache_on && method_exists($item, 'getItemID')) {
            $item_id = $item->getItemID();
            if (!empty($item_id) && empty($this->_cache_object[$item_id])) {
                $this->_cache_object[$item_id] = $item;
            }
        }

        return $item;
    }

   /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select () {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
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
                  // ------------
                  // --->UTF8<---
                  // innerhalb einer Kodierung kein Problem
                  // an dieser Stelle noch vor dem encode, d.h.
                  // entweder latin-1 oder utf-8 - je nach DB Zustand
                  //
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  // --->UTF8<---
                  // ------------
                  if ( $key == 'extras' ) {
                     // ------------
                     // --->UTF8<---
                     // innerhalb einer Kodierung kein Problem
                     // an dieser Stelle noch vor dem encode, d.h.
                     // entweder latin-1 oder utf-8 - je nach DB Zustand
                     //
                     $value = serialize($value);
                     // --->UTF8<---
                     // ------------
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
         if ( is_array($rs)
              and isset($rs[0]['count'])
            ) {
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
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if ( empty($this->_id_array) ) {
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
   */
   function _performQuery($mode = 'select') {
      include_once('functions/error_functions.php');
      trigger_error("must be overwritten!", E_USER_ERROR);
   }

    public function mergeAccounts($new_id, $old_id)
    {
        // creator id
        if (!in_array($this->_db_table, ['links', 'items', 'portal'])) {
            $query1 = 'UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET creator_id = "' . encode(AS_DB,
                    $new_id) . '" WHERE creator_id = "' . encode(AS_DB, $old_id) . '";';
            $result = $this->_db_connector->performQuery($query1);
            if (!isset($result) or !$result) {
                include_once('functions/error_functions.php');
                trigger_error('Problems merging accounts "' . $this->_db_table . '".', E_USER_WARNING);
            }
        }

        // modifier id
        if (!in_array($this->_db_table, ['files', 'link_items', 'links', 'tasks', 'items', 'portal'])) {
            $query2 = ' UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET modifier_id = "' . encode(AS_DB,
                    $new_id) . '" WHERE modifier_id = "' . encode(AS_DB, $old_id) . '";';
            $result = $this->_db_connector->performQuery($query2);
            if (!isset($result) or !$result) {
                include_once('functions/error_functions.php');
                trigger_error('Problems merging accounts "' . $this->_db_table . '".', E_USER_WARNING);
            }
        }

        // deleter id
        $query3 = ' UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET deleter_id = "' . encode(AS_DB,
                $new_id) . '" WHERE deleter_id = "' . encode(AS_DB, $old_id) . '";';
        $result = $this->_db_connector->performQuery($query3);
        if (!isset($result) or !$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems merging accounts "' . $this->_db_table . '": "' . $this->_dberror . '" from query: "' . $query3 . '"',
                E_USER_WARNING);
        }
    }

   public function copyDataFromRoomToRoom ($old_id, $new_id, $user_id='', $id_array='')
   {
      $retour = array();
      $current_date = getCurrentDateTimeInMySQL();

       $query = 'SELECT * FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE context_id="' .
           encode(AS_DB, $old_id) . '" AND deleter_id IS NULL AND deletion_date IS NULL';

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
         $current_data_array = array();
         $current_copy_date_array = array();
         $current_mod_date_array = array();
         if ( DBTable2Type($this->_db_table) == CS_LABEL_TYPE
              or DBTable2Type($this->_db_table) == CS_TAG_TYPE
            ) {
            $title_field = 'title';
            $type_field = '';
            if ( DBTable2Type($this->_db_table) == CS_LABEL_TYPE ) {
               $title_field = 'name';
               $type_field = 'type';
            }
            $type_sql_statement = '';
            if ( !empty($type_field) ) {
               $type_sql_statement = ', '.$type_field;
            }
            $sql = 'SELECT item_id,'.$title_field.$type_sql_statement.' FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if ( !isset($sql_result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               foreach ( $sql_result as $sql_row ) {
                  if ( !empty($sql_row[$title_field]) ) {
                     if ( empty($type_field) ) {
                        $current_data_array[$sql_row[$title_field]] = $sql_row['item_id'];
                     } else {
                        $current_data_array[$sql_row[$type_field]][$sql_row[$title_field]] = $sql_row['item_id'];
                     }
                  }
               }
            }
         } elseif ( DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE ) {
            $sql = 'SELECT to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if ( !isset($sql_result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               foreach ( $sql_result as $sql_row ) {
                  $current_data_array[] = $sql_row['to_item_id'];
               }
            }
         } elseif ( DBTable2Type($this->_db_table) == CS_MATERIAL_TYPE
                    or DBTable2Type($this->_db_table) == CS_SECTION_TYPE
                    or DBTable2Type($this->_db_table) == CS_ANNOUNCEMENT_TYPE
                    or DBTable2Type($this->_db_table) == CS_DATE_TYPE
                    or DBTable2Type($this->_db_table) == CS_DISCUSSION_TYPE
                    or DBTable2Type($this->_db_table) == CS_TODO_TYPE
                    or DBTable2Type($this->_db_table) == CS_ANNOTATION_TYPE
                    or DBTable2Type($this->_db_table) == CS_DISCARTICLE_TYPE
                    or DBTable2Type($this->_db_table) == CS_STEP_TYPE
                  ) {
            $item_id = 'item_id';
            $modification_date = 'modification_date';
            $sql  = 'SELECT '.$item_id.','.$modification_date.',extras FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$new_id).'"';
            $sql .= ' AND extras LIKE "%s:4:\"COPY\";a:2:{s:7:\"ITEM_ID\";%"';
            $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if ( !isset($sql_result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               foreach ( $sql_result as $sql_row ) {
                  include_once('functions/text_functions.php');
                  $extra_array = mb_unserialize($sql_row['extras']);
                  $current_data_array[$extra_array['COPY']['ITEM_ID']] = $sql_row[$item_id];
               }
            }
         } elseif ( DBTable2Type($this->_db_table) == CS_LINK_TYPE ) {
            $sql  = 'SELECT from_item_id,to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$new_id).'"';
            $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if ( !isset($sql_result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               foreach ( $sql_result as $sql_row ) {
                  $current_data_array[] = array($sql_row['from_item_id'],$sql_row['to_item_id']);
               }
            }
         } elseif ( DBTable2Type($this->_db_table) == CS_LINKITEM_TYPE ) {
            $sql  = 'SELECT first_item_id,second_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$new_id).'"';
            $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if ( !isset($sql_result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
            } else {
               foreach ( $sql_result as $sql_row ) {
                  $current_data_array[] = array($sql_row['first_item_id'],$sql_row['second_item_id']);
               }
            }
         }
         foreach ($result as $query_result) {
            $do_it = true;

            if ( DBTable2Type($this->_db_table) == CS_LABEL_TYPE
                 and !empty($current_data_array)
                 and !empty($current_data_array[$query_result[$type_field]])
                 and is_array($current_data_array[$query_result[$type_field]])
                 and array_key_exists($query_result[$title_field],$current_data_array[$query_result[$type_field]])
               ) {
               $retour[$query_result['item_id']] = $current_data_array[$query_result[$type_field]][$query_result[$title_field]];
               $do_it = false;
            } elseif ( DBTable2Type($this->_db_table) == CS_TAG_TYPE
                       and array_key_exists($query_result[$title_field],$current_data_array)
                     ) {
               $retour[$query_result['item_id']] = $current_data_array[$query_result[$title_field]];
               $do_it = false;
            } elseif ( DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE
                       and in_array($id_array[$query_result['to_item_id']],$current_data_array)
                     ) {
               $do_it = false;
            } elseif ( ( DBTable2Type($this->_db_table) == CS_MATERIAL_TYPE
                         or DBTable2Type($this->_db_table) == CS_SECTION_TYPE
                         or DBTable2Type($this->_db_table) == CS_ANNOUNCEMENT_TYPE
                         or DBTable2Type($this->_db_table) == CS_DATE_TYPE
                         or DBTable2Type($this->_db_table) == CS_DISCUSSION_TYPE
                         or DBTable2Type($this->_db_table) == CS_TODO_TYPE
                         or DBTable2Type($this->_db_table) == CS_ANNOTATION_TYPE
                         or DBTable2Type($this->_db_table) == CS_DISCARTICLE_TYPE
                         or DBTable2Type($this->_db_table) == CS_STEP_TYPE
                       )
                       and array_key_exists($query_result['item_id'],$current_data_array)) {
               $retour[$query_result['item_id']] = $current_data_array[$query_result['item_id']];
               $do_it = false;
            } elseif ( DBTable2Type($this->_db_table) == CS_LINK_TYPE
                       and !empty($id_array[$query_result['from_item_id']])
                       and !empty($id_array[$query_result['to_item_id']])
                       and in_array(array($id_array[$query_result['from_item_id']],$id_array[$query_result['to_item_id']]),$current_data_array)
                     ) {
               $do_it = false;
            } elseif ( DBTable2Type($this->_db_table) == CS_LINKITEM_TYPE
                       and !empty($id_array[$query_result['first_item_id']])
                       and !empty($id_array[$query_result['second_item_id']])
                       and ( in_array(array($id_array[$query_result['first_item_id']],$id_array[$query_result['second_item_id']]),$current_data_array)
                             or in_array(array($id_array[$query_result['second_item_id']],$id_array[$query_result['first_item_id']]),$current_data_array)
                           )
                     ) {
               $do_it = false;
            }

            // Skip draft items
            if ($do_it && isset($query_result['item_id'])) {
                $itemManager = $this->_environment->getItemManager();
                $correspondingItem = $itemManager->getItem($query_result['item_id']);
                $do_it = !$correspondingItem->isDraft();
            }

            if ( $do_it
                 and DBTable2Type($this->_db_table) != CS_LINKITEMFILE_TYPE
                 and DBTable2Type($this->_db_table) != CS_LINK_TYPE
                 and DBTable2Type($this->_db_table) != CS_TAG2TAG_TYPE
                 and isset($query_result['item_id'])
                 and !isset($retour[$query_result['item_id']])
               ) {
               $new_item_id = $this->_createItemInItemTable($new_id,DBTable2Type($this->_db_table),$current_date);
            }
            if ($do_it) {
               $insert_query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET';
               $first = true;
               $old_item_id = '';
               foreach ($query_result as $key => $value) {
                  $value = encode(FROM_DB,$value);
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

                  // special for STEP
                  elseif ( $key == 'todo_item_id'
                           and DBTable2Type($this->_db_table) == CS_STEP_TYPE
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

                  // special for labels
                  elseif ( $key == 'name'
                           and empty($value)
                           and DBTable2Type($this->_db_table) == CS_LABEL_TYPE
                         ) {
                     $insert_query .= $before.$key.'=" "';
                  }

                  // extra
                  elseif ( $key == 'extras'
                           and !empty($old_item_id)
                         ) {
                     include_once('functions/text_functions.php');
                     $extra_array = mb_unserialize($value);
                     $extra_array['COPY']['ITEM_ID'] = $old_item_id;
                     $extra_array['COPY']['COPYING_DATE'] = $current_date;
                     $value = serialize($extra_array);
                     $insert_query .= $before.$key.'="'.encode(AS_DB,$value).'"';
                  }

                  // default
                  elseif ( !empty($value) ) {
                     $insert_query .= $before.$key.'="'.encode(AS_DB,$value).'"';
                  }
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
                     }
                  }

                  // link_item_modifier
                  if ( !empty($new_item_id)
                       and !empty($user_id)
                       and DBTable2Type($this->_db_table) != CS_FILE_TYPE
                       and DBTable2Type($this->_db_table) != CS_LINKITEMFILE_TYPE
                       and DBTable2Type($this->_db_table) != CS_LINK_TYPE
                       and DBTable2Type($this->_db_table) != CS_TAG2TAG_TYPE
                     ) {
                     $this->_createEntryInLinkItemModifier($new_item_id,$user_id);
                  }
               }
            }
         }
      }
      return $retour;
   }

   function _createEntryInLinkItemModifier ($item_id,$user_id) {
      $manager = $this->_environment->getLinkModifierItemManager();
      $manager->markEdited($item_id,$user_id);
   }

   public function refreshInDescLinks ($context_id, $id_array) {
      $query  = '';
      $query .= 'SELECT item_id, description FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,$context_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $replace = false;
            $item_id = $query_result['item_id'];
            $desc = $query_result['description'];
            preg_match_all('~\[[0-9]*(\]|\|)~u', $query_result['description'], $matches);
            if ( isset($matches[0]) ) {
               foreach ($matches[0] as $match) {
                  $id = mb_substr($match,1);
                  $last_char = mb_substr($id,mb_strlen($id));
                  $id = mb_substr($id,0,mb_strlen($id)-1);
                  if ( isset($id_array[$id]) ) {
                     $desc = str_replace('['.$id.$last_char,'['.$id_array[$id].$last_char,$desc);
                     $replace = true;
                  }
               }
            }
            #preg_match_all('~\(:item ([0-9]*) ~u', $query_result['description'], $matches);
            // because of html tags from (f)ckeditor
            preg_match_all('~\(:item[^0-9]*([0-9]*) ~u', $query_result['description'], $matches);
            if ( isset($matches[1])
                 and !empty($matches[1])
               ) {
               foreach ($matches[1] as $key => $match) {
                  $id = $match;
                  if ( isset($id_array[$id]) ) {
                     #$desc = str_replace('(:item '.$id,'(:item '.$id_array[$id],$desc);
                     $match2 = str_replace($id,$id_array[$id],strip_tags($matches[0][$key]));
                     # if there are html tags, then there are double spaces, don't know why (IJ 27.10.2011)
                     $match2 = str_replace('  ',' ',$match2);
                     $desc = str_replace($matches[0][$key],$match2,$desc);
                     $replace = true;
                  }
               }
            }
            if ( strstr($desc,'<!-- KFC TEXT')
                 and $replace
               ) {
               include_once('functions/security_functions.php');
               $desc = renewSecurityHash($desc);
            }
            $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET description="'.encode(AS_DB,$desc).'" WHERE item_id='.encode(AS_DB,$item_id);
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
      $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
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
      $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'"';
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
                  $value = mb_unserialize($value);
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
         $query = 'SHOW COLUMNS FROM '.$this->addDatabasePrefix($this->_db_table);
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
      $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';

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
      } elseif ( DBTable2Type($this->_db_table) == CS_TAG2TAG_TYPE ) {
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
            $ft_sql_result = ' OR (' . $this->addDatabasePrefix($table) . '.item_id IN (';
            for ($i = 0; $i < count($ft_result) - 1; $i++) {
               $ft_sql_result .= $ft_result[$i] . ',';
            }
            $ft_sql_result .= $ft_result[count($ft_result) - 1] . ') AND '.$this->addDatabasePrefix($table).'.deleter_id IS NULL)';
            if ( $this->_db_table == type2Table(CS_MATERIAL_TYPE) ) {
               $ft_sql_result .= ' OR (' . $this->addDatabasePrefix(type2Table(CS_SECTION_TYPE)) . '.item_id IN (';
               for ($i = 0; $i < count($ft_result) - 1; $i++) {
                  $ft_sql_result .= $ft_result[$i] . ',';
               }
               $ft_sql_result .= $ft_result[count($ft_result) - 1] . ') AND '.$this->addDatabasePrefix(type2Table(CS_SECTION_TYPE)).'.deleter_id IS NULL)';
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
         $query = 'SELECT item_id FROM '.$this->addDatabasePrefix($this->_db_table);
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

   function getDatabasePrefix () {
      return $this->_db_prefix;
   }

   private function getNonGeneratedColumnsNames(string $tableName): array
   {
       $connection = $this->_db_connector->getConnection();
       $sm = $connection->createSchemaManager();

       $table = $sm->listTableDetails($this->_db_table);
       $notGeneratedColumns = array_filter($table->getColumns(), function (Column $column) {
           /**
            * The column "not_deleted" is a generated column
            */
           return $column->getName() !== 'not_deleted';
       });

       return array_map(function (Column $column) {
           return $column->getName();
       }, $notGeneratedColumns);
   }

    public function moveFromDbToBackup($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $sourceTable = $this->_db_table;
            $targetTable = $c_db_backup_prefix . '_' . $this->_db_table;

            $implodedColumnNames = implode(', ', $this->getNonGeneratedColumnsNames($sourceTable));
            $sql = "INSERT INTO $targetTable ($implodedColumnNames)
                SELECT $implodedColumnNames FROM $sourceTable WHERE context_id = :contextId"
            ;

            $this->_db_connector->performQuery($sql, ['contextId' => $context_id]);
            $this->deleteFromDb($context_id);
        }
    }

    public function moveFromBackupToDb($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $sourceTable = $c_db_backup_prefix . '_' . $this->_db_table;
            $targetTable = $this->_db_table;

            $implodedColumnNames = implode(', ', $this->getNonGeneratedColumnsNames($sourceTable));
            $sql = "INSERT INTO $targetTable ($implodedColumnNames)
                SELECT $implodedColumnNames FROM $sourceTable WHERE context_id = :contextId"
            ;

            $this->_db_connector->performQuery($sql, ['contextId' => $context_id]);
            $this->deleteFromDb($context_id, true);
        }
    }

    function deleteFromDb($context_id, $from_backup = false)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        $db_prefix = '';
        if ($from_backup) {
            $db_prefix .= $c_db_backup_prefix . '_';
        }
        $query = 'DELETE FROM ' . $db_prefix . $this->_db_table . ' WHERE ' . $db_prefix . $this->_db_table . '.context_id = "' . $context_id . '"';
        $this->_db_connector->performQuery($query);
    }


   /*
    * Functions needed for ex- and import of items
    */

   function getArrayAsXML($xml, $array, $create_top_element = false, $top_element_name = ''){
      if ($create_top_element) {
         if ($top_element_name != '') {
            $xml = new SimpleXMLElementExtended('<'.$top_element_name.'></'.$top_element_name.'>');
         }
      }
      if ($array != null) {
         foreach ($array as $key => $value) {
            // Tag names must not start with a number.
            if (is_numeric($key)) {
               $key = 'COMMSY_'.$key;
            }
            if (!is_array($value)) {
               $xml->addChildWithCDATA($key, $value);
            } else {
               $tempXml = new SimpleXMLElementExtended('<'.$key.'></'.$key.'>');
               $temp = $this->getArrayAsXML($tempXml, $value);
               $this->simplexml_import_simplexml($xml, $temp);
            }
         }
      }
      return $xml;
   }

   function getXMLAsArray($xml){
      $arr = array();
      foreach ($xml as $element) {
         $tag = $element->getName();
         $e = get_object_vars($element);
         if (!empty($e)) {
            $arr[$tag] = $element instanceof SimpleXMLElement ? $this->getXMLAsArray($element) : $e;
         } else {
            $arr[$tag] = ''.trim($element);
         }
      }
      return $arr;
   }

   function simplexml_import_xml(SimpleXMLElementExtended $parent, $xml, $before = false) {
      $xml = (string)$xml;
      // check if there is something to add
      if ($nodata = !strlen($xml) or $parent[0] == NULL) {
         return $nodata;
      }
      // add the XML
      $node     = dom_import_simplexml($parent);
      $fragment = $node->ownerDocument->createDocumentFragment();
      $fragment->appendXML($xml);
      if ($before) {
         return (bool)$node->parentNode->insertBefore($fragment, $node);
      }
      return (bool)$node->appendChild($fragment);
    }

    function simplexml_import_simplexml(SimpleXMLElementExtended $parent, SimpleXMLElementExtended $child, $before = false) {
      // check if there is something to add
      if ($child[0] == NULL) {
         return true;
      }
      // if it is a list of SimpleXMLElements default to the first one
      $child = $child[0];
      // insert attribute
      if ($child->xpath('.') != array($child)) {
         $parent[$child->getName()] = (string)$child;
         return true;
      }
      $xml = $child->asXML();
      // remove the XML declaration on document elements
      if ($child->xpath('/*') == array($child)) {
         $pos = strpos($xml, "\n");
         $xml = substr($xml, $pos + 1);
      }
      return $this->simplexml_import_xml($parent, $xml, $before);
    }

    function getAnnotationsAsXML ($itemID) {
       $item_manager = $this->_environment->getManager('item');
       $item = $item_manager->getItem($itemID);

       $annotations_manager = $this->_environment->getManager('annotations');
       $annotations_manager->setContextLimit($item->getContextID());
       $annotations_manager->setLinkedItemID($item->getItemID());
       $annotations_manager->select();
       $annotations_list = $annotations_manager->get();

   	 // get XML for each section
       $annotations_item_xml_array = array();
       if (!$annotations_list->isEmpty()) {
          $annotations_item = $annotations_list->getFirst();
          while ($annotations_item) {
             $annotations_id = $annotations_item->getItemID();
             $annotations_item_xml_array[] = $annotations_manager->export_item($annotations_id);
             $annotations_item = $annotations_list->getNext();
          }
       }

       // combine in tag
       $annotations_xml = new SimpleXMLElementExtended('<annotations></annotations>');
       foreach ($annotations_item_xml_array as $annotations_item_xml) {
          $this->simplexml_import_simplexml($annotations_xml, $annotations_item_xml);
       }

       return $annotations_xml;
    }

    function importAnnotationsFromXML ($xml, $top_item) {
    }

    function getFilesAsXML ($itemID) {
       $item_manager = $this->_environment->getManager('item');
       $item = $item_manager->getItem($itemID);

       $file_manager = $this->_environment->getFileManager();
       $file_list = $item->getFileList();

   	 // get XML for each section
       $file_item_xml_array = array();
       if (!$file_list->isEmpty()) {
          $file_item = $file_list->getFirst();
          while ($file_item) {
             $file_id = $file_item->getFileID();
             $file_item_xml_array[] = $file_manager->export_item($file_id);
             $file_item = $file_list->getNext();
          }
       }

       // combine in tag
       $file_xml = new SimpleXMLElementExtended('<files></files>');
       foreach ($file_item_xml_array as $file_item_xml) {
          $this->simplexml_import_simplexml($file_xml, $file_item_xml);
       }

       return $file_xml;
    }

    function importFilesFromXML ($xml, $top_item, &$options) {
       $file_manager = $this->_environment->getFileManager();
       foreach ($xml->files->children() as $file) {
          $file_manager->import_item($file, $top_item, $options);
       }
    }

    function getTagsAsXML ($xml, $tag_array) {
       foreach ($tag_array as $tag) {
          $tag_manager = $this->_environment->getTagManager();
          $tag_xml = $tag_manager->export_item($tag['item_id']);
          if (!empty($tag['children'])) {
             $children_xml = new SimpleXMLElementExtended('<children></children>');
             $children_xml_temp = $this->getTagsAsXML($children_xml, $tag['children']);
             $this->simplexml_import_simplexml($tag_xml, $children_xml_temp);
          }
          $this->simplexml_import_simplexml($xml, $tag_xml);
       }
       return $xml;
    }

    function importTagsFromXML ($xml, $top_item, &$options) {
       $tag_manager = $this->_environment->getTagManager();
       $tag_item = $tag_manager->import_item($xml, $top_item, $options);
       foreach ($xml->children->children() as $child) {
          $this->importTagsFromXML($child, $tag_item, $options);
       }
    }

    public function updateLocking($itemId, $date) {
      $userItem = $this->_environment->getCurrentUserItem();
      $query = "
          UPDATE
              " . $this->addDatabasePrefix($this->_db_table) . " AS t
          SET
              t.locking_date = '".$date."',
              t.locking_user_id = " . encode(AS_DB, $userItem->getItemId()) . "
          WHERE
              t.item_id = '" . encode(AS_DB, $itemId) . "'
      ";

      return $this->_db_connector->performQuery($query);
   }

   public function clearLocking($itemId) {
      $query = "
          UPDATE
              " . $this->addDatabasePrefix($this->_db_table) . " AS t
          SET
              t.locking_date = NULL,
              t.locking_user_id = NULL
          WHERE
              t.item_id = '" . encode(AS_DB, $itemId) . "'
      ";

      return $this->_db_connector->performQuery($query);
   }

    /**
     * @param int[] $contextIds List of context ids
     * @param array Limits for buzzwords / categories
     * @param \DateTime $newerThen The oldest modification date to consider
     * @param int[] $excludedIds Ids to exclude
     *
     * @return \cs_list
     */
    protected function setGenericNewestItemsLimits($contextIds, $limits, \DateTime $newerThen = null, $excludedIds = [])
    {
        $this->reset();

        $this->setContextArrayLimit($contextIds);
        $this->setDeleteLimit(true);
        $this->setInactiveEntriesLimit(self::SHOW_ENTRIES_ONLY_ACTIVATED);

        if ($newerThen) {
            $this->setModificationNewerThenLimit($newerThen);
        }

        if ($excludedIds) {
            $this->setExcludedIdsLimit($excludedIds);
        }

        if (isset($limits['buzzword'])) {
            $this->setBuzzwordLimit($limits['buzzword']);
        }

        if (isset($limits['categories'])) {
            $this->setTagArrayLimit($limits['categories']);
        }
    }
}