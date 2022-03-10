<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the tag manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "tag"
 * this class implements a database manager for the table "tag".
 */
class cs_tag_manager extends cs_manager {

  /**
   * integer - containing the age of last change as a limit in days
   */
  var $_age_limit = NULL;

  /**
   * string - containing a title as a limit for select labels
   */
  var $_title_limit = NULL;

  /**
   * string - containing a title as a limit for select tag - exact title limit
   */
  var $_exact_title_limit = NULL;

  /**
   * integer - containing a start point for the select statement
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many labels the select statement should get
   */
  var $_interval_limit = NULL;

  var $_sort_order = NULL;

  /**
   * string - containing an order limit for the select statement
   */
  var $_order = NULL;

  /**
   * array - containing the data from the database -> cache data
   */
  var $_internal_data = NULL;

  var $_object_data = NULL;

  var $_cached_sql = array();

  /*
   * Translation Object
   */
  private $_translator = null;

  /** constructor: cs_tag_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     cs_manager::__construct($environment);
     $this->_db_table = CS_TAG_TYPE;
     $this->_translator = $environment->getTranslationObject();
  }

  /** reset limits
    * reset limits of this class: type limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_age_limit = NULL;
     $this->_title_limit = NULL;
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_order = NULL;
     $this->_sort_order = NULL;
     $this->_exact_title_limit = NULL;
     $this->_id_array_limit = array();
  }

  /** set age limit
    * this method sets an age limit for the label (modification date)
    *
    * @param integer limit age limit
    */
  function setAgeLimit ($limit) {
     $this->_age_limit = (int)$limit;
  }


  /** set title limit
    * this method sets a title limit
    *
    * @param string limit title limit for labels
    */
  function setTitleLimit ($limit) {
     $this->_title_limit = (string)$limit;
  }

  /** set exact title limit
    * this method sets a title limit - exact
    *
    * @param string limit title limit (exact) for tags
    */
  function setExactTitleLimit ($limit) {
     $this->_exact_title_limit = (string)$limit;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected labels
    * @param integer interval interval limit for selected labels
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (int)$interval;
     $this->_from_limit = (int)$from;
  }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected labels
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

   /** get all ids of the selected items as an array
    * this method returns all ids of the selected items limited by the limits as an array.
    * if no items are loaded, the ids are loaded from the database
    * depends on _performQuery(), which must be overwritten
    *
    * @return array $this->_id_array id array of selected materials
    */
   function getIDArray () {
      if ($this->_isAvailable()) {
         return parent::getIDArray();
      } else {
         return array();
      }
   }

   private function _getItemOutofCache ($item_id) {
      $retour = NULL;

      if (isset($this->_room_limit)) {
         $current_context = $this->_room_limit;
      } else {
         $current_context = $this->_environment->getCurrentContextID();
      }

      if ( !isset($this->_object_cache[$current_context]) ) {
         if ( !isset($this->_internal_data[$current_context]) ) {
            $this->_loadAllTags();
         }
         if ( !empty($this->_internal_data[$current_context][$item_id]) ) {
            $this->_object_data[$current_context][$item_id] = $this->_buildItem($this->_internal_data[$current_context][$item_id]);
         }
      }

      if ( !empty($this->_object_data[$current_context][$item_id]) ) {
         $retour = $this->_object_data[$current_context][$item_id];
      }

      return $retour;
   }

   public function resetCache(){
   	$this->_internal_data = NULL;
      $this->_object_data = NULL;
      $this->_cached_sql = array();
   }

  /** select labels limited by limits
    * this method returns a list (cs_list) of labels within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
   function select () {
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>';
      } else {
         include_once('classes/cs_list.php');
         $this->_data = new cs_list();
      }

      if ( isset($this->_id_array_limit)
           and !empty($this->_id_array_limit)
           and !$this->_output_limit == 'XML'
         ) {
         foreach ( $this->_id_array_limit as $id ) {
            $item_outof_cache = $this->_getItemOutofCache($id);
            if ( isset($item_outof_cache) ) {
               $this->_data->add($item_outof_cache);
            }
         }
         if ( isset($this->_order) ) {
            if ( 'title' ) {
               $this->_data->sortby('title');
            } elseif ( 'modification_date' ) {
               $this->_data->sortby('date');
            } else {
               $this->_data->sortby('title');
            }
         } else {
         	/*
            // sort tags(alphabet) if no order is given
            $tag2tag_manager = $this->_environment->getTag2TagManager();
            $query = 'SELECT link_id,sorting_place,title FROM '.$this->addDatabasePrefix($tag2tag_manager->_db_table).' INNER JOIN '.$this->addDatabasePrefix($this->_db_table).' ON item_id = to_item_id WHERE '.$this->addDatabasePrefix($tag2tag_manager->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($tag2tag_manager->_db_table).'.deleter_id IS NULL ';
            $query .=' AND '.$this->addDatabasePrefix($tag2tag_manager->_db_table).'.context_id ="'.$this->_environment->getCurrentContextID().'"';
            $query .=' ORDER BY title ASC';
		     if ( !$this->_force_sql
		          and isset($this->_cached_sql[$query])
		        ) {
		        $result = $this->_cached_sql[$query];
		     } else {
		        $this->_force_sql = false;
            	$result = $tag2tag_manager->_db_connector->performQuery($query);
		        if ( !isset($result) ) {
		        	trigger_error('Problems selecting '.$this->_db_table.'.', E_USER_WARNING);
		        } else {
		             if ( $this->_cache_on ) {
		                $this->_cached_sql[$query] = $result;
		             }
		        }
		     }
            if (!isset($result)) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting tags from query: "'.$query.'"',E_USER_WARNING);
            } else {
               $flag = false;
               if (isset($result['0']['sorting_place'])){
                  $i = $result['0']['sorting_place'];
                  foreach ( $result as $result_array ) {
                     if ( $result_array['sorting_place'] != $i  ) {
                           $flag = true;
                           break;
                     }
                     $i = $i - 1;
                  }
               }
               unset($result);
         }
         if($flag == false){
            $query = 'SELECT item_id,title FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND title != "CS_TAG_ROOT" ORDER BY title ASC;';
		     // sixth, perform query
		     if ( !$this->_force_sql
		          and isset($this->_cached_sql[$query])
		        ) {
		        $result = $this->_cached_sql[$query];
		     } else {
		        $this->_force_sql = false;
            	$result = $tag2tag_manager->_db_connector->performQuery($query);
		        if ( !isset($result) ) {
		        	trigger_error('Problems selecting '.$this->_db_table.'.', E_USER_WARNING);
		        } else {
		             if ( $this->_cache_on ) {
		                $this->_cached_sql[$query] = $result;
		             }
		        }
		     }


             $result = $this->_db_connector->performQuery($query);
             if (!isset($result)) {
                  include_once('functions/error_functions.php');
                  trigger_error('Problems selecting tags from query: "'.$query.'"',E_USER_WARNING);
               } else {
                $sorting_place_id = 1;
                foreach ( $result as $result_array ) {
                    $update = 'UPDATE '.$this->addDatabasePrefix($tag2tag_manager->_db_table).' SET sorting_place='.$sorting_place_id.' WHERE to_item_id = '.$result_array["item_id"].';';
                       $result = $tag2tag_manager->_db_connector->performQuery($update);
                       $sorting_place_id = $sorting_place_id + 1;
               }
            $this->_data->sortby('title');
             }
         }
         unset($tag2tag_manager);
         */
         }
      } else {
         $result = $this->_performQuery();
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
               $this->_data->add($item);
               unset($item);
            }
         }
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>';
      }
   }

  /** perform query for labels: select and count
    * this method perform query for selecting and counting labels
    *
    * @param boolean count true: count labels
    *                      false: select labels
    *
    * @return integer num of labels if count = true
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT DISTINCT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
     } else {
        if ($mode == 'id_array') {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        } else {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }
     }
     $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
     $query .= ' WHERE 1';

      // insert limits into the select statement
      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL';
     }
     if (isset($this->_title_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title like "%'.encode(AS_DB,$this->_title_limit).'%"';
     }
     if (isset($this->_exact_title_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title = "'.encode(AS_DB,$this->_exact_title_limit).'"';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         if ( !isset($this->_attribute_limit) || ('all' == $this->_attribute_limit) ) {
            $field_array = array(''.$this->addDatabasePrefix($this->_db_table).'.title',''.$this->addDatabasePrefix($this->_db_table).'.modification_date');
            $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
            $query .= $search_limit_query_code;
         }
         $query .= ' )';
         $query .= ' GROUP BY '.$this->addDatabasePrefix($this->_db_table).'.item_id';
      }

     if ( isset($this->_sort_order) ) {
        if ( $this->_sort_order == 'title' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ( $this->_sort_order == 'title_rev' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
        }
     }


     elseif (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
     }
     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
        }
     }

     // sixth, perform query
     if ( !$this->_force_sql
          and isset($this->_cached_sql[$query])
        ) {
        $result = $this->_cached_sql[$query];
     } else {
        $this->_force_sql = false;
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           if ($mode == 'count') {
              include_once('functions/error_functions.php');
              trigger_error('Problems counting '.$this->_db_table.'.', E_USER_WARNING);
           } elseif ($mode == 'id_array') {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting '.$this->_db_table.' ids.', E_USER_WARNING);
           } else {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting '.$this->_db_table.'.', E_USER_WARNING);
           }
        } else {
             if ( $this->_cache_on ) {
                $this->_cached_sql[$query] = $result;
             }
        }
     }
     return $result;
  }

  /** get all tags and cache it - INTERNAL
    * this method get all tags for the context and cache it in this class
    */
   function _loadAllTags () {
      $data_array = array();
      if (isset($this->_room_limit)) {
         $current_context = $this->_room_limit;
      } else {
         $current_context = $this->_environment->getCurrentContextID();
      }

      $this->resetLimits();
      $this->setContextLimit($current_context);
      $result = $this->_performQuery();
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting all '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $data_array[$query_result['item_id']] = $query_result;
         }
      }

      $this->_internal_data[$current_context] = $data_array;
   }

  /** get one tag - INTERNAL
    * this method gets one tag
    *
    * @param integer  item_id  item id of the tag
    */
  function _getTag ($item_id) {
     $item = NULL;
     if ( !empty($item_id) ) {
        $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
        $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id = "'.encode(AS_DB,$item_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting one '.$this->_db_table.'.',E_USER_WARNING);
        } elseif ( !empty($result[0]) ) {
           $item = $this->_buildItem($result[0]);
        } else {
 #          include_once('functions/error_functions.php');
 #          trigger_error(''.$this->_db_table.' ['.$item_id.'] does not exists.',E_USER_WARNING);
        }
     }
     return $item;
  }

  /**
   *   get empty tag_item
   *   @return cs_tag_item a tag
   */
   function getNewItem () {
      include_once('classes/cs_tag_item.php');
      return new cs_tag_item($this->_environment);
   }

  /** get a tag
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a tag
    */
  function getItem ($item_id) {
     $retour = NULL;

     if (isset($this->_room_limit)) {
        $current_context = $this->_room_limit;
     } else {
        $current_context = $this->_environment->getCurrentContextID();
     }

     if ( !isset($this->_internal_data[$current_context]) ) {
        $this->_loadAllTags();
     }

     if ( !isset($this->_internal_data[$current_context][$item_id])
          or empty($this->_internal_data[$current_context][$item_id])
        ) {
        $retour = $this->_getTag($item_id);
     } else {
        $retour = $this->_buildItem($this->_internal_data[$current_context][$item_id]);
     }

     return $retour;
  }

  public function getRootTagItem () {
     $retour = NULL;
     $this->setExactTitleLimit('CS_TAG_ROOT');
     $this->select();
     $list = $this->get();
     if ( $list->isNotEmpty() and $list->getCount() == 1 ) {
        $retour = $list->getFirst();
     }
     return $retour;
  }

  public function getRootTagItemFor ($context_id) {
     $retour = NULL;
     $this->setExactTitleLimit('CS_TAG_ROOT');
     $this->setContextLimit($context_id);
     $this->select();
     $list = $this->get();
     if ( $list->isNotEmpty() and $list->getCount() == 1 ) {
        $retour = $list->getFirst();
     } elseif ( $list->isNotEmpty() and $list->getCount() > 1 ) {
        include_once('functions/error_functions.php');
        trigger_error('ERROR: there are more than one root tag item in database table '.$this->_db_table.' for context id '.$context_id,E_USER_ERROR);
     }
     return $retour;
  }

   public function createRootTagItem () {
      $this->createRootTagItemFor($this->_environment->getCurrentContextID());
   }

   public function createRootTagItemFor ($context_id) {
      if ( !empty($context_id) ) {
         $item = $this->getNewItem();
         $item->setTitle('CS_TAG_ROOT');
         $item->setContextID($context_id);
         $item->setCreatorItem($this->_environment->getCurrentUserItem());
         $item->save();
         unset($item);
      }
   }

  /** get a list of items
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
   function getItemList($id_array) {
      return $this->_getItemList('tag', $id_array);
   }

  /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
  function _buildItem($db_array) {
     $item = parent::_buildItem($db_array);
     return $item;
  }

  /** update a tag - internal, do not use -> use method save
    * this method updates a tag
    *
    * @param object cs_item tag_item the tag
    */
  function _update ($item) {
     parent::_update($item);

     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating '.$this->_db_table.': "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  /** create a tag - internal, do not use -> use method save
    * this method creates a tag
    *
    * @param object cs_item tag_item the tag
    */
  function _create ($item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="'.CS_TAG_TYPE.'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating '.$this->_db_table.'.', E_USER_ERROR);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newTag($item);
     }
  }

  /** creates a new tag - internal, do not use -> use method save
    * this method creates a new tag
    *
    * @param object cs_item tag_item the tag
    */
  function _newTag ($item) {
     $user = $item->getCreatorItem();
     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $user->getItemID();
     if ( empty($user_id) ) {
        $user_id = $this->_environment->getRootUserItemID();
     }
     $modificator_id = $modificator->getItemID();
     if ( empty($modificator_id) ) {
        $modificator_id = $this->_environment->getRootUserItemID();
     }

     $query  = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user_id).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modifier_id="'.encode(AS_DB,$modificator_id).'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating '.$this->_db_table.'.',E_USER_WARNING);
     }
  }

  /** save a tag
    *
    * @param object cs_item the tag
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();
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

     //Add modifier to all users who ever edited this item
     $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
     $link_modifier_item_manager->markEdited($item->getItemID());
  }


  /** update a tag, with new informations, e.g. creator and modificator
    * this method updates a tag initially
    *
    * @param object cs_item tag_item the tag
    */
   function saveItemNew ($item) {
      $user = $item->getCreatorItem();
      $modificator = $item->getModificatorItem();
      $current_datetime = getCurrentDateTimeInMySQL();

      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating '.$this->_db_table.'.',E_USER_WARNING);
      }
   }

  function delete ($item_id, $deleteTag2TagRecursive = true) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID() ?: 0;
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting '.$this->_db_table.'.',E_USER_WARNING);
     } else {
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
        unset($link_manager);
        $tag2tag_manager = $this->_environment->getTag2TagManager();
        if($deleteTag2TagRecursive) {
           $tag2tag_manager->deleteTagLinksForTag($item_id);
        } else {
           $tag2tag_manager->deleteTagLinks($item_id);
        }
        unset($tag2tag_manager);
        parent::delete($item_id);
     }
  }

   public function copyDataFromRoomToRoom ($old_id, $new_id, $user_id='', $id_array='') {
      $retour = parent::copyDataFromRoomToRoom($old_id,$new_id,$user_id,$id_array);

      $tag_root_item_old = $this->getRootTagItemFor($old_id);
      if ( isset($tag_root_item_old) ) {
         $this->forceSQL();
         $tag_root_item_new = $this->getRootTagItemFor($new_id);
         if ( isset($tag_root_item_new) ) {
            $retour[$tag_root_item_old->getItemID()] = $tag_root_item_new->getItemID();
            unset($tag_root_item_new);
         }
      }
      unset($tag_root_item_old);
      return $retour;
   }

    public function deleteTagsOfUser ($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $current_datetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.* FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.creator_id = "'.encode(AS_DB,$uid).'"';
            $query  .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title != "CS_TAG_ROOT"';
            $result = $this->_db_connector->performQuery($query);
            if ( !empty($result) ) {
                foreach ( $result as $rs ) {
                    $updateQuery = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET';

                    /* flag */
                    if ($disableOverwrite === 'FLAG') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $current_datetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === 'FALSE') {
                        $updateQuery .= ' modification_date = "'.$current_datetime.'",';
                        $updateQuery .= ' title = "'.encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                        $updateQuery .= ' public = "1"';
                    }
                    
                    $updateQuery .= ' WHERE item_id = "'.encode(AS_DB,$rs['item_id']).'"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if ( !isset($result2) or !$result2 ) {
                       include_once('functions/error_functions.php');
                       trigger_error('Problems automatic deleting '.$this->_db_table.'.',E_USER_WARNING);
                    }
                    unset($result2);
                }
                unset($result);
            }
        }
    }
}