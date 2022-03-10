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
include_once('classes/cs_section_list.php');

/** upper class of the sectionq manager
 */
include_once('classes/cs_manager.php');

/** cs_section_item is needed to create section items
 */
include_once('classes/cs_section_item.php');

include_once('functions/text_functions.php');

/** class for database connection to the database table "section"
 * this class implements a database manager for the table "section"
 * @version 2.1 $Revision$
 */

class cs_section_manager extends cs_manager {

   /**
   * integer - containing a start point for the select section
   */
   var $_from_limit = NULL;

   /**
   * integer - containing how many section the select statement should get
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
   var $_material_item_id_limit = 0;

   /**
   *  int - containing an version_id as search limit
   */
   var $_version_id_limit = 0;

   /**
   * bool - tells if the next section will be saved without setting new modification date
   */
   var $_save_section_without_date = false;

   var $_all_section_list = NULL;
   var $_cached_material_item_ids = array();

   /*
    * Translation Object
    */

   /** constructor: cs_section_manager
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = CS_SECTION_TYPE;
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
      $this->_material_item_id_limit = 0;
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
    * @param integer from     from limit for selected section
    * @param integer interval interval limit for selected section
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

   /**
   * tells to save the next section without a new modifying date
   */
   function setSaveSectionWithoutDate() {
      $this->_save_section_without_date = true;
   }

   /** set material_item_id limit
    * this method sets an refid limit for the select statement
    *
    * @param string limit order limit
    */
  function setMaterialItemIDLimit ($limit) {
     $this->_material_item_id_limit = (int)$limit;
  }

   /** set material_version_id limit
    * this method sets an refid limit for the select statement
    *
    * @param string limit order limit
    */
  function setVersionIDLimit ($limit) {
     $this->_version_id_limit = (int)$limit;
  }

   /** set search limit
    * this method sets a search limit for section
    *
    * @param string limit search limit for section
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
        $query = 'SELECT count('.$this->addDatabasePrefix('section').'.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('section').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('section').'.*';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('section');
     $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('section').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

     if (isset($this->_search_limit) AND !empty($this->_search_limit)) {
        // join to user database table
#        $query .= ' LEFT JOIN user AS people ON (people.item_id=section.creator_id )'; // modificator_id (TBD)
        // join groups
#        $query .= ' LEFT JOIN links AS l2 ON l2.from_item_id=section.item_id AND l2.link_type="relevant_for"';
#        $query .= ' LEFT JOIN labels AS groups ON l2.to_item_id=groups.item_id AND groups.type="group"';
     }

     $query .= ' WHERE 1';

     // fifth, insert limits into the select statement
     if ( isset($this->_material_item_id_limit) and !empty($this->_material_item_id_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('section').'.material_item_id='.encode(AS_DB,$this->_material_item_id_limit);
        $query .= ' AND '.$this->addDatabasePrefix('section').'.version_id='.encode(AS_DB,$this->_version_id_limit);
     }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('section').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND '.$this->addDatabasePrefix('section').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('section').'.deleter_id IS NULL';
     }
     if (!empty($this->_id_array_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('section').'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('section').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
      if ( isset($this->_age_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('section').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
              // restrict sql-statement by search limit, create wheres
     if (isset($this->_search_limit) AND !empty($this->_search_limit)) {
        $query .= ' AND (';

        // material item
        $query .= ' UPPER('.$this->addDatabasePrefix('section').'.title) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        $query .= ' OR UPPER('.$this->addDatabasePrefix('section').'.description) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        if ( $this->_search_limit != ':' and $this->_search_limit != '-' ) {
           $query .= ' OR UPPER('.$this->addDatabasePrefix('section').'.modification_date) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
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
        $query .= ' GROUP BY '.$this->addDatabasePrefix('section').'.item_id';
     }
     $query .= ' ORDER BY '.$this->addDatabasePrefix('section').'.number ASC, '.$this->addDatabasePrefix('section').'.modification_date DESC, '.$this->addDatabasePrefix('section').'.title DESC';

      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting section from query: "'.$query.'"',E_USER_WARNING);
     } else {
         return $result;
     }
   }

   /** build a new material item
    * this method returns a new EMTPY material item
    *
    * @return \cs_section_item cs_item a new EMPTY section
    */
   function getNewItem () {
      return new cs_section_item($this->_environment);
   }

  /** get a section in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
     function getItem ($item_id) {
        $section = NULL;
        $query = "SELECT * FROM ".$this->addDatabasePrefix("section")." WHERE ".$this->addDatabasePrefix("section").".item_id = '".encode(AS_DB,$item_id)."'";
        $query .= " ORDER BY ".$this->addDatabasePrefix("section").".version_id DESC";
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting one section item from query: "'.$query.'"',E_USER_WARNING);
        } elseif ( !empty($result[0]) ) {
           $section = $this->_buildItem($result[0]);
        }
        return $section;
     }



   function getItemByVersion ($item_id,$version_id) {
      $section = NULL;

      $query = "SELECT * FROM ".$this->addDatabasePrefix("section")." WHERE ".$this->addDatabasePrefix("section").".item_id = '".encode(AS_DB,$item_id)."'";
      $query .=" AND ".$this->addDatabasePrefix("section").".version_id = '".$version_id."'";
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
          include_once('functions/error_functions.php');
          trigger_error('Problems selecting one materials item from query: "'.$query.'"',E_USER_WARNING);
      } else {
          $section = $this->_buildItem($result[0]);
      }
      return $section;
   }

   function getItemListForCurrentVersion(){
   }

  /** get a list of section in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_section_items
    */
   function getItemList ($id_array, $version_id = NULL) {
      if (empty($id_array)) {
         return new cs_section_list();
      } else {
         $section = NULL;
         $query = "SELECT * FROM ".$this->addDatabasePrefix("section")." WHERE ".$this->addDatabasePrefix("section").".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         if ($version_id) {
            $query .= " AND ".$this->addDatabasePrefix("section").".version_id='".encode(AS_DB,$version_id)."'";
         }
         $query .= " ORDER BY ".$this->addDatabasePrefix("section").".number";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');trigger_error('Problems selecting list of section items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $section_list = new cs_section_list();
            foreach ($result as $rs) {
               $section_list->append($this->_buildItem($rs));
            }
         }
         return $section_list;
      }
   }

  /** get a list of section in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_section_items
    */
   function getAllSectionItemListByIDArray ($id_array) {
      if (empty($id_array)) {
         return new cs_section_list();
      } else {
         $section = NULL;
         $query = "SELECT * FROM ".$this->addDatabasePrefix("section")." WHERE material_item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " AND ".$this->addDatabasePrefix("section").".deleter_id IS NULL";
         $query .= " AND ".$this->addDatabasePrefix("section").".deletion_date IS NULL";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');trigger_error('Problems selecting list of section items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $section_list = new cs_section_list();
            foreach ($result as $rs) {
               $section_list->append($this->_buildItem($rs));
            }
         }
         if ( $this->_cache_on ) {
            $this->_all_section_list = $section_list;
            $this->_cached_material_item_ids = $id_array;
         }
         return $section_list;
      }
   }

   function  getSectionForCurrentVersion($material_item){
      $item_id = $material_item->getItemID();
      $version_id = $material_item->getVersionID();
      if (in_array($item_id,$this->_cached_material_item_ids)){
         $list = new cs_list();
         $section_list = $this->_all_section_list;
         $section_item = $section_list->getFirst();
         while($section_item){
            if($item_id == $section_item->getLinkedItemID()
              and $version_id == $section_item->getVersionID() ){
               $list->add($section_item);
            }
            $section_item = $section_list->getNext();
         }
         unset($section_list);
         unset($section_item);
         return $list;
      }else{
         $this->reset();
         $this->setContextLimit($material_item->getContextID());
         $this->setMaterialItemIDLimit($material_item->getItemID());
         $this->setVersionIDLimit($material_item->getVersionID());
         $this->select();
         return $this->get();
      }
   }

  /** update a section - internal, do not use -> use method save
   * this method updates the database record for a given section item
   *
   * @param cs_section_item the section item for which an update should be made
   * @param bool can disable setting of new modification date
   */
   function _update ($item) {
        $date_string = '';
        if (!$this->_save_section_without_date) {
           parent::_update($item);
           $date_string = 'modification_date="'.getCurrentDateTimeInMySQL().'",';
        }
        $modificator_item = $item->getModificatorItem();

        if ( !isset($modificator_item) ) {
          $modificator_item = $this->_environment->getCurrentUserItem();
        }

        $query = 'UPDATE '.$this->addDatabasePrefix('section').' SET '.
              $date_string.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'number="'.encode(AS_DB,$item->getNumber()).'",'.
              'description="'.encode(AS_DB,$item->getDescription()).'",'.
              'material_item_id="'.encode(AS_DB,$item->getLinkedItemID()).'",'.
              'modifier_id="'.encode(AS_DB,$modificator_item->getItemID()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"'.
              ' AND version_id="'.encode(AS_DB,$item->getVersionID()).'"';
     // extras (TBD)

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating section from query: "'.$query.'"',E_USER_WARNING);
     }
     $this->_save_section_without_date = false; //restore default
     unset($item);
   }

   /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'nsection' in the database and sets the section items item id.
   * it then calls the private method _newNews to store the section item itself.
   * @param cs_section_item the section item for which an entry should be made
   */
  function _create ($item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="section",'.
              'draft="'.encode(AS_DB,$item->isDraft()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating section from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newSection($item);
     }
     unset($item);
  }

  /** store a new section item to the database - internal, do not use -> use method save
    * this method stores a newly created section item to the database
    *
    * @param cs_section_item the section item to be stored
    */
  function _newSection ($item) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $query = 'INSERT INTO '.$this->addDatabasePrefix('section').' SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
              'version_id="'.encode(AS_DB,$item->getVersionID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$item->getCreatorID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'number="'.encode(AS_DB,$item->getNumber()).'",'.
              'description="'.encode(AS_DB,$item->getDescription()).'",'.
              'material_item_id="'.encode(AS_DB,$item->getLinkedItemID()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating section from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($item);
  }

   /**  delete a section item
   *
   * @param cs_section_item the section item to be deleted
   *
   * @access public
   */
   function delete ($item_id, $version_id = NULL) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID() ?: 0;
      $query = 'UPDATE '.$this->addDatabasePrefix('section').' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      if ($version_id) {
         $query .= ' AND version_id="'.encode(AS_DB,$version_id).'"';
      }

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting section from query: "'.$query.'"',E_USER_WARNING);
      } else {
         if ( is_null($version_id) ) {
            parent::delete($item_id);
         }
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
           $this->_newSection($item);
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

     //Add modifier to all users who ever edited this section
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
         $this->_data = new cs_section_list();
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

    function deleteSectionsOfUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('section').'.* FROM ' . $this->addDatabasePrefix('section').' WHERE ' . $this->addDatabasePrefix('section') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('section') . ' SET';

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
                        trigger_error('Problems automatic deleting sections from query: "' . $updateQuery . '"',E_USER_WARNING);
                    }
                }
            }
        }
    }
}