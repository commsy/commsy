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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');


/** upper class of the material manager
 */
include_once('classes/cs_manager.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** text functions are needed for ???
 */
include_once('functions/text_functions.php');

/** class for database connection to the database table "material"
 * this class implements a database manager for the table "material"
 */
class cs_material_manager extends cs_manager implements cs_export_import_interface {

   /**
    * integer - containing the age of material as a limit
    */
   var $_age_limit = NULL;

   /**
    * integer - containing the world_public of material as a limit
    */
   var $_public_limit = NULL;

   /**
    * array - containing the id's of materials as a limit
    */
   var $_id_limit = NULL;

   /**
    * integer - containing the id of a group as a limit for the selected material
    */
   var $_group_limit = NULL;

   /**
    * integer - containing a start point for the select material
    */
   var $_from_limit = NULL;

   /**
    * integer - containing how many material the select statement should get
    */
   var $_interval_limit = NULL;

   /**
    * integer - containing the item id of the intstitution as a limit
    */
   var $_institution_limit = NULL;

   /**
    * integer - containing the item id of the topic as a limit
    */
   var $_topics_limit = NULL;

   /**
    * integer - containing the item id of the ref item as a limit
    */
   var $_ref_id_limit = NULL;

   /**
    * integer - containing the item id of the user as a limit
    */
   var $_ref_user_limit = NULL;



   /**
    * string - containing an order limit for the select material
    */
   var $_order = NULL;

   /**
    * array - containing the cached items already loaded from the database
    */
   var $_cache = NULL;

   /**
    * array - containing the selected ids
    */
   var $_id_array = array();

   var $_limit_only_files_mode = NULL;

   var $_handle_tmp_manual = false;

   var $_sql_create_temp_material_table = 'CREATE TEMPORARY TABLE temp_material (
  item_id int(11) NOT NULL default "0",
  version_id int(11) NOT NULL default "0",
  context_id int(11) default NULL,
  creator_id int(11) NOT NULL default "0",
  deleter_id int(11) default NULL,
  creation_date datetime NOT NULL default "0000-00-00 00:00:00",
  modifier_id int(11) default NULL,
  modification_date datetime default NULL,
  deletion_date datetime default NULL,
  title varchar(255) NOT NULL,
  description text,
  author varchar(200) default NULL,
  publishing_date varchar(20) default NULL,
  public tinyint(11) NOT NULL default "0",
  world_public smallint(2) NOT NULL default "0",
  extras text,
  new_hack tinyint(1) NOT NULL default "0",
  copy_of int(11) default NULL,
  PRIMARY KEY  (item_id,version_id),
  KEY version_id (version_id),
  KEY room_id (context_id),
  KEY creator_id (creator_id),
  KEY modificator (modifier_id)
) ENGINE=MyISAM;';

   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor: cs_material_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'materials';
      $this->_translator = $environment->getTranslationObject();
   }

   /** reset data
    * reset data of this class: reset list of items and id_array
    */
   function resetData () {
      parent::resetData();
      $this->_id_array = array();
   }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit, type limit, id-array limit, dossier limit and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_public_limit = NULL;
      $this->_age_limit = NULL;
      $this->_group_limit = NULL;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_institution_limit = NULL;
      $this->_topics_limit = NULL;
      $this->_ref_id_limit = NULL;
      $this->_ref_user_limit = NULL;
      $this->_order = NULL;
      $this->_limit_only_files_mode = NULL;
      $this->reset_id_limit();
      $this->_handle_tmp_manual = false;
   }

   /** reset id-array limit
    * reset the limit of this class: id-array limit
    */
   function reset_id_limit () {
      $this->_id_limit = NULL;
   }

   /** set age limit
    * this method sets an age limit for material
    *
    * @param integer limit age limit for material
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   /** set public limit
    * this method sets an public limit for material
    *
    * @param integer limit public limit for material
    *
    * @author CommSy Development Group
    */
   function setPublicLimit ($value) {
      $this->_public_limit = (int) $value;
   }

   /** set id-array limit
    * this method sets an id-array limit for material
    *
    * @param array limit id-array limit for material
    *
    * @author CommSy Development Group
    */
   function setIDLimit ($limit) {
      $this->_id_limit = (array)$limit;
   }

   /** set type limit
    * this method sets a type limit for material
    * This function should be deleted it's of no use anymore ...
    *
    * @param string limit type limit for material
    *
    * @author CommSy Development Group
    */
   function setTypLimit ($limit) {
   }

   /** set Announcements limit
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

   /** set dossier limit
    * this method sets a dossier limit for material
    *
    * @param string limit dossier limit for material
    *
    * @author CommSy Development Group
    */
   function setDossierLimit (){
     $this->_dossier_limit = 'dossier';
   }

   /** set group limit
    * this method sets a group limit for material
    *
    * @param integer limit id of the group
    *
    * @author CommSy Development Group
    */
   function setGroupLimit ($limit) {
      $this->_group_limit = (int)$limit;
   }

   function setInstitutionLimit ($limit) {
      $this->_institution_limit = (int)$limit;
   }
   function setTopicLimit ($limit) {
      $this->_topics_limit = (int)$limit;
   }


   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected material
    * @param integer interval interval limit for selected material
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (int)$interval;
      $this->_from_limit = (int)$from;
   }

   /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected material
    */
   function setOrder ($limit) {
      $this->_order = (string)$limit;
   }

   /** get a material in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
   function getItem ($item_id) {
      $material = NULL;
      if ( !empty($item_id)
           and !empty($this->_cache_object[$item_id])
         ) {
         return $this->_cache_object[$item_id];
      } elseif (array_key_exists($item_id,$this->_cached_items)){
         return $this->_buildItem($this->_cached_items[$item_id]);
      } else {
         $query = "SELECT * FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".item_id = '".encode(AS_DB,$item_id)."'";
         if ($this->_delete_limit == true) {
             $query .= ' AND '.$this->addDatabasePrefix('materials').'.deleter_id IS NULL';
         }
         $query .= " ORDER BY ".$this->addDatabasePrefix("materials").".version_id DESC";
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one material item from query: "'.$query.'"',E_USER_WARNING);
         } elseif ( !empty($result[0]) ) {
            $material = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
         }
         return $material;
      }
   }

   /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
   function getItemList($id_array) {
      if (empty($id_array)) {
         return new cs_list();
      } else {
         $query = "SELECT * FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " ORDER BY ".$this->addDatabasePrefix("materials").".item_id, ".$this->addDatabasePrefix("materials").".version_id DESC";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $list = new cs_list();
            // filter items with highest version_id, doing this in MySQL would be too expensive
            $last_item_id = 0;
            foreach ($result as $rs) {
               if ( $last_item_id != $rs['item_id'] ) {
                  $last_item_id = $rs['item_id'];
                  $list->add($this->_buildItem($rs));
               }
            }
         }
         return $list;
      }
   }

   /**
    * documentation TBD
    */
   function getItemByVersion ($item_id,$version_id) {
      $material = NULL;
      $query = "SELECT * FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".item_id = '".encode(AS_DB,$item_id)."'";
      $query .=" AND ".$this->addDatabasePrefix("materials").".version_id = '".encode(AS_DB,$version_id)."'";
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting one materials item from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $material = $this->_buildItem($result[0]);
      }
      return $material;
   }

   /** select all versions of a material
    * this method returns a list (cs_list) of materials in specific versions
    *
    * @param integer material_id item-id of material
    *
    * @return cs_list version_list of versions of the material
    */
   function getVersionList($material_id){
      $version_list = new cs_list();
      $query  = 'SELECT * FROM '.$this->addDatabasePrefix('materials');
      $query .= ' WHERE '.$this->addDatabasePrefix('materials').'.item_id="'.encode(AS_DB,$material_id).'"';
      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.deleter_id IS NULL';
      }
      $query .= " ORDER BY ".$this->addDatabasePrefix("materials").".version_id DESC";
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting versions of a material from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $material_item = $this->_buildItem($query_result);
            $version_list->add($material_item);
         }
      }
      return $version_list;
   }

 /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function selectDistinct () {
      $result = $this->_performQuery('distinct');
      $this->_data = new cs_list();
      $this->_id_array = NULL;
      if ( !empty($result) ) {
         foreach ($result as $query_result) {
            $item = $this->_buildItem($query_result);
            $this->_data->add($item);
            if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
               $this->_id_array[] = $query_result['item_id'];
            }
         }
      }
   }

   function _performQuery ($mode = 'select') {
      $retour = '';
      if ( isset($this->_only_files_limit) and $this->_only_files_limit and $mode != 'id_array' ) {
         $session_item = $this->_environment->getSessionItem();
         $query = str_replace('temp_material','f'.$session_item->getSessionID(),$this->_sql_create_temp_material_table);
         $result = $this->_db_connector->performQuery($query);
         $this->_limit_only_files_mode = 'both';
         $this->_performQuery2($mode);
         $query = 'SELECT DISTINCT item_id FROM f'.encode(AS_DB,$session_item->getSessionID()).' ORDER BY title;';
         $retour = $this->_db_connector->performQuery($query);
         foreach ($retour as $query_result) {
            $this->_limit_not_item_id_array[] = $query_result['item_id'];
         }
         $this->_limit_only_files_mode = 'item';
         $this->_performQuery2($mode);
         $this->_limit_only_files_mode = 'subitem';
         $this->_performQuery2($mode);
         $query = 'SELECT DISTINCT * FROM f'.encode(AS_DB,$session_item->getSessionID()).' ORDER BY title;';
         $retour = $this->_db_connector->performQuery($query);
         unset($this->_limit_only_files_mode);
         unset($this->_limit_not_item_id_array);
      } else {
         $retour = $this->_performQuery2($mode);
      }
      return $retour;
   }

   /** perform query for material: select and count
    * this method perform query for selecting and counting materials
    *
    * @param boolean count true: count materials
    *                      false: select materials
    *
    * @return integer num of materials if count = true
    */

   function _performQuery2 ($mode = 'select') {
      $this->_data = new cs_list();
      $session = $this->_environment->getSessionItem();
      if ( isset($session) ) {
         $temp_number = $session->getSessionID();
      } else {
         include_once('functions/date_functions.php');
         $current_time = getCurrentDateTimeInMySQL();
         $randum_number = rand(0,999999);
         $uid = 'cron_job';
         $temp_number = '';
         for ($i=0; $i<mb_strlen($current_time); $i++) {
            $temp_number .= mb_substr($current_time,$i,1).mb_substr($uid,$i,1).mb_substr($randum_number,$i,1);
         }
         $temp_number = md5($temp_number);
      }
      $cancel = false;
      if (!$this->_handle_tmp_manual){
         $query  = 'CREATE TEMPORARY TABLE tmp3'.$temp_number.' (item_id INT(11) NOT NULL, version_id INT(11) NOT NULL, PRIMARY KEY (item_id, version_id));';
         $result = $this->_db_connector->performQuery($query);
         $query  = 'INSERT INTO tmp3'.$temp_number.' (item_id,version_id) SELECT item_id,MAX(version_id) FROM '.$this->addDatabasePrefix('materials');
         if ( isset($this->_room_limit) ) {
            $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
         } else {
            $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
         }
         $query .= ' GROUP BY item_id;';
         $result = $this->_db_connector->performQuery($query);
      }
      $query = '';

      if ( isset($this->_limit_only_files_mode) ) {
         $query = 'INSERT INTO f'.$temp_number.' ';
      }

      if ($mode == 'count') {
         $query .= 'SELECT count(DISTINCT '.$this->addDatabasePrefix('materials').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.item_id';
      } elseif ($mode == 'distinct') {
         $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
      } else {
         $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.*';
      }

	  if((isset($this->_order) && ($this->_order == 'assessment' || $this->_order == 'assessment_rev'))) {
	  	$query .= ', AVG(assessments.assessment) AS assessments_avg';
	  }

      $query .= ' FROM '.$this->addDatabasePrefix('materials');
      $query .= ' INNER JOIN tmp3'.$temp_number.' ON '.$this->addDatabasePrefix('materials').'.item_id=tmp3'.$temp_number.'.item_id AND '.$this->addDatabasePrefix('materials').'.version_id=tmp3'.$temp_number.'.version_id';
      $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('materials').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

      if ( ( isset($this->_search_array) AND !empty($this->_search_array) )
           or ( isset($this->_only_files_limit) and $this->_only_files_limit )
          ) {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('section').' ON ('.$this->addDatabasePrefix('section').'.material_item_id = '.$this->addDatabasePrefix('materials').'.item_id AND '.$this->addDatabasePrefix('section').'.version_id = '.$this->addDatabasePrefix('materials').'.version_id AND '.$this->addDatabasePrefix('section').'.context_id = "'.$this->_room_limit.'")';
      }

     if ( isset($this->_institution_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l11 ON ( l11.deletion_date IS NULL AND ((l11.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l11.second_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l12 ON ( l12.deletion_date IS NULL AND ((l12.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l12.first_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
     }
     if ( isset($this->_topics_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'") ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'") ';
     }

      // restrict materials by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l5 ON l5.from_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.from_version_id='.$this->addDatabasePrefix('materials').'.version_id AND l5.link_type="buzzword_for"';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l5.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l5 ON l5.from_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.from_version_id='.$this->addDatabasePrefix('materials').'.version_id AND l5.link_type="buzzword_for"';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l5.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }

      // restrict material by ref item
      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR (l5.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") ) AND l5.deletion_date IS NULL';
      }

//	  // join annotations if needed
//	  if(!isset($this->_order) || (isset($this->_order) && !in_array($this->_order, array(	'date_rev',
//	  																						'publishing_date',
//	  																						'publishing_date_rev',
//	  																						'author',
//	  																						'author_rev',
//																							'modificator',
//																							'modificator_rev',
//																							'title',
//																							'title_rev')))) {
//		$query .= ' LEFT JOIN '.$this->addDatabasePrefix('annotations').' AS annotations ON '.$this->addDatabasePrefix('materials').'.item_id=annotations.linked_item_id';
//	  }

      // restrict sql-statement by search limit, create joins
      if (isset($this->_search_array) AND !empty($this->_search_array)) {

         // join to user database table
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS creator ON '.$this->addDatabasePrefix('materials').'.creator_id=creator.item_id';
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS modificator ON '.$this->addDatabasePrefix('materials').'.modifier_id=modificator.item_id';

         if (!isset($this->_buzzword_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l8 ON l8.from_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l8.from_version_id='.$this->addDatabasePrefix('materials').'.version_id AND l8.link_type="buzzword_for"';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l8.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }

         //look in filenames of linked files for the search_limit
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('item_link_file').' ON '.$this->addDatabasePrefix('materials').'.item_id = '.$this->addDatabasePrefix('item_link_file').'.item_iid'.
                   ' LEFT JOIN '.$this->addDatabasePrefix('files').' ON '.$this->addDatabasePrefix('item_link_file').'.file_id = '.$this->addDatabasePrefix('files').'.files_id';
         //look in filenames of linked files for the search_limit
      }elseif((isset($this->_order) and
           ($this->_order == 'modificator' || $this->_order == 'modificator_rev' || $this->_order == 'creator' || $this->_order == 'creator_rev'))){
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS creator ON (creator.item_id='.$this->addDatabasePrefix('materials').'.creator_id )';
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS modificator ON (modificator.item_id='.$this->addDatabasePrefix('materials').'.modifier_id )';
      } elseif((isset($this->_order) && ($this->_order == 'assessment' || $this->_order == 'assessment_rev'))) {
      	$query .= ' LEFT JOIN ' . $this->addDatabasePrefix('assessments') . ' ON ' . $this->addDatabasePrefix('materials') . '.item_id=assessments.item_link_id AND assessments.deletion_date IS NULL';
      }

      // only files limit -> entries with files (material)
      if ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'item' ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf2 ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf2.item_iid';
      }

      // only files limit -> entries with files (sections)
      elseif ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'subitem' ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf1 ON '.$this->addDatabasePrefix('section').'.item_id = lf1.item_iid';
      }

      // only files limit -> entries with files (sections and material)
      elseif ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'both' ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf2 ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf2.item_iid';
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf1 ON '.$this->addDatabasePrefix('section').'.item_id = lf1.item_iid';
      }

      $query .= ' WHERE 1';
      if (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
      //if (isset($this->_search_limit) AND !empty($this->_search_limit)) {
      //   if (isset($this->_room_limit)) {
      //      $query .= ' AND (" OR section.room_id IS NULL)';
      //   }
      //}

      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.deletion_date IS NULL';
      }


/***Activating Code***/
      if (!$this->_show_not_activated_entries_limit) {
         $query .= ' AND ('.$this->addDatabasePrefix('materials').'.modification_date IS NULL OR '.$this->addDatabasePrefix('materials').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
      }
/*********************/

      if (isset($this->_ref_user_limit)) {
         $query .= ' AND ('.$this->addDatabasePrefix('materials').'.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'" )';
      }
      if (isset($this->_public_limit)) {
         if ($this->_public_limit == 6){
            $query .= ' AND ('.$this->addDatabasePrefix('materials').'.world_public >= "1" )';
         }else{
            $query .= ' AND ('.$this->addDatabasePrefix('materials').'.world_public = "'.encode(AS_DB,$this->_public_limit).'" )';
         }
      }
      if ( isset($this->_topics_limit) ){
         if($this->_topics_limit == -1){
            $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
            $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB,$this->_topics_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topics_limit).'")';
            $query .= ' OR (l22.first_item_id = "'.encode(AS_DB,$this->_topics_limit).'" OR l22.second_item_id = "'.encode(AS_DB,$this->_topics_limit).'"))';
         }
      }
      if ( isset($this->_institution_limit) ){
         if ($this->_institution_limit == -1){
            $query .= ' AND (l11.first_item_id IS NULL AND l11.second_item_id IS NULL)';
            $query .= ' AND (l12.first_item_id IS NULL AND l12.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l11.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l11.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'")';
            $query .= ' OR (l12.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l12.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'"))';
         }
      }
      if ( isset($this->_group_limit) ){
         if($this->_group_limit == -1){
            $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
            $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
            $query .= ' OR (l32.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$this->_group_limit).'"))';
         }
      }

      if (isset($this->_age_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }

      if ( isset($this->_tag_limit) ) {
        $query .= ' AND l41.deletion_date IS NULL ';
        $query .= ' AND l42.deletion_date IS NULL ';

         $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
         $id_string = implode(', ',$tag_id_array);
         if( isset($tag_id_array[0]) and $tag_id_array[0] == -1 ){
            $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
            $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
         }else{
            $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB,$id_string).') OR l41.second_item_id IN ('.encode(AS_DB,$id_string).') )';
            $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB,$id_string).') OR l42.second_item_id IN ('.encode(AS_DB,$id_string).') ))';
         }
      }
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit ==-1){
            $query .= ' AND (l5.to_item_id IS NULL OR l5.deletion_date IS NOT NULL)';
         }else{
            $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_buzzword_limit).'"';
         }
      }
      if (isset($this->_id_limit)) {
         $id_string = implode(', ',$this->_id_limit);
         $query .= ' AND '.$this->addDatabasePrefix('materials').'.item_id IN ('.encode(AS_DB,$id_string).')';
      }

      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

      if (isset($this->_attribute_limit) and !($this->_attribute_limit=='all')
      and isset($this->_search_array) and !empty($this->_search_array)){
         if ( 'modificator'== $this->_attribute_limit ) {
            $query .= ' AND '.$this->_generateSearchLimitCode(array('TRIM(CONCAT(creator.firstname," ",creator.lastname))'));
         } elseif ( 'title'==$this->_attribute_limit ) {
            $query .= ' AND '.$this->_generateSearchLimitCode(array($this->addDatabasePrefix('materials').'.title'));
         }elseif ( 'author'==$this->_attribute_limit ) {
            $query .= ' AND '.$this->_generateSearchLimitCode(array($this->addDatabasePrefix('materials').'.author'));
         } elseif ( 'description'==$this->_attribute_limit ) {
            $query .= ' AND '.$this->_generateSearchLimitCode(array($this->addDatabasePrefix('materials').'.description'));
         }elseif ( 'file'==$this->_attribute_limit ){
              $query .= $this->initFTSearch();
         }
      }
      // restrict sql-statement by search limit, create wheres
      elseif (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';

        $fieldArray = array(
            'TRIM(CONCAT(modificator.firstname," ",modificator.lastname))',
            'TRIM(CONCAT(creator.firstname," ",creator.lastname))',
            $this->addDatabasePrefix('materials') . '.publishing_date',
            $this->addDatabasePrefix('materials') . '.author',
            $this->addDatabasePrefix('materials') . '.title',
            $this->addDatabasePrefix('materials') . '.description',
            'buzzwords.name',
            $this->addDatabasePrefix('files') . '.filename'
        );

        $checkedFieldArray = array(
            'fields' => array(
                $this->addDatabasePrefix('section') . '.description',
                $this->addDatabasePrefix('section') . '.title'
            ),
            'checks' => array(
                $this->addDatabasePrefix('section') . '.deleter_id IS NULL',
                $this->addDatabasePrefix('section') . '.deletion_date IS NULL'
            )
        );

        $search_limit_query_code = $this->_generateSearchLimitCode($fieldArray, $checkedFieldArray);
        $query .= $search_limit_query_code;
        $query .= ' )';
      }

      // init and perform ft search action
      if (!empty($this->_search_array) and
          !(isset($this->_attribute_limit) and !($this->_attribute_limit=='all'))
         ) {
         $query .= $this->initFTSearch();
      }

      // only entries with files
      if ( isset($this->_limit_not_item_id_array) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(',',encode(AS_DB,$this->_limit_not_item_id_array)).')';
      }

      // only files limit -> entries with files (material)
      if ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'item' ) {
         $query .= ' AND lf2.deleter_id IS NULL AND lf2.deletion_date IS NULL';
      }

      // only files limit -> entries with files (sections)
      elseif ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'subitem' ) {
         $query .= ' AND lf1.deleter_id IS NULL AND lf1.deletion_date IS NULL';
      }

      // only files limit -> entries with files (sections and material)
      elseif ( isset($this->_limit_only_files_mode)
           and $this->_limit_only_files_mode == 'both' ) {
         $query .= ' AND lf2.deleter_id IS NULL AND lf2.deletion_date IS NULL';
         $query .= ' AND lf1.deleter_id IS NULL AND lf1.deletion_date IS NULL';
      }

      if (isset($this->_only_files_limit) && $this->_only_files_limit) {
         $query .= ' AND '.$this->addDatabasePrefix('section').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('section').'.deletion_date IS NULL';
      }

      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' GROUP BY '.$this->addDatabasePrefix('materials').'.item_id';
      }

	  if((isset($this->_order) && ($this->_order == 'assessment' || $this->_order == 'assessment_rev'))) {
	  	$query .= ' GROUP BY '.$this->addDatabasePrefix('materials').'.item_id';
	  }

      if ( isset($this->_order) ) {
         if ( $this->_order == 'date_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date ASC, '.$this->addDatabasePrefix('materials').'.title DESC';
         } elseif ( $this->_order == 'publishing_date' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.publishing_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC';
         } elseif ( $this->_order == 'publishing_date_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.publishing_date ASC, '.$this->addDatabasePrefix('materials').'.title DESC';
         } elseif ($this->_order == 'author') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.author ASC, '.$this->addDatabasePrefix('materials').'.title ASC';
         } elseif ($this->_order == 'author_rev') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.author DESC, '.$this->addDatabasePrefix('materials').'.title DESC';
         } elseif ( $this->_order == 'creator' ) {
            $query .= ' ORDER BY creator.lastname';
         } elseif ( $this->_order == 'creator_rev' ) {
            $query .= ' ORDER BY creator.lastname DESC';
         }elseif ( $this->_order == 'modificator' ) {
            $query .= ' ORDER BY modificator.lastname';
         } elseif ( $this->_order == 'modificator_rev' ) {
            $query .= ' ORDER BY modificator.lastname DESC';
		 } elseif( $this->_order == 'assessment' ) {
		 	$query .= ' ORDER BY assessments_avg DESC';
		 } elseif( $this->_order == 'assessment_rev') {
		 	$query .= ' ORDER BY assessments_avg ASC';
         } elseif ($this->_order == 'title') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.title ASC, '.$this->addDatabasePrefix('materials').'.modification_date DESC';
         } elseif ($this->_order == 'title_rev') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.title DESC, '.$this->addDatabasePrefix('materials').'.modification_date ASC';
         } elseif ($this->_order == 'workflow_status') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.workflow_status ASC, '.$this->addDatabasePrefix('materials').'.modification_date DESC';
         } elseif ($this->_order == 'workflow_status_rev') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.workflow_status DESC, '.$this->addDatabasePrefix('materials').'.modification_date ASC';
         } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC'; // default: sort by date
         }
      } else {
         $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC'; // default: sort by date
      }
      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

      // perform query
      if (!$cancel or $mode != 'select') {
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            if ($mode == 'count') {
               include_once('functions/error_functions.php');
               trigger_error('Problems counting material from query: "'.$query.'"', E_USER_WARNING);
            } else {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting material from query: "'.$query.'"',E_USER_WARNING);
            }
         }
         if (!$this->_handle_tmp_manual){
            $query = 'DROP TABLE tmp3'.$temp_number.';';
            $this->_db_connector->performQuery($query);
         }
         if ($result) {
            return $result;
         }
      } // end of if (cancel)
   } // end of methode _performQuery


   function create_tmp_table($room_id) {
      $session = $this->_environment->getSessionItem();
      $query = 'CREATE TEMPORARY TABLE tmp3'.encode(AS_DB,$session->getSessionID()).' (item_id INT(11) NOT NULL, version_id INT(11) NOT NULL, PRIMARY KEY (item_id, version_id));';
      $result = $this->_db_connector->performQuery($query);
      $query = 'INSERT INTO tmp3'.encode(AS_DB,$session->getSessionID()).' (item_id,version_id) SELECT item_id,MAX(version_id) FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').'.context_id ="'.$room_id.'" GROUP BY item_id;';
      $result = $this->_db_connector->performQuery($query);
      unset($session);
      $this->_handle_tmp_manual = true;
   }

   function create_tmp_table_by_id_array($id_array) {
      $session = $this->_environment->getSessionItem();
      $query = 'CREATE TEMPORARY TABLE tmp3'.encode(AS_DB,$session->getSessionID()).' (item_id INT(11) NOT NULL, version_id INT(11) NOT NULL, PRIMARY KEY (item_id, version_id));';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($id_array) and !empty($id_array) ) {
         $query = 'INSERT INTO tmp3'.encode(AS_DB,$session->getSessionID()).' (item_id,version_id) SELECT item_id,MAX(version_id) FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').'.context_id IN ('.implode(",", $id_array).') GROUP BY item_id;';
         $result = $this->_db_connector->performQuery($query);
      }
      unset($session);
      $this->_handle_tmp_manual = true;
   }

   function delete_tmp_table() {
      $session = $this->_environment->getSessionItem();
      $query = 'DROP TABLE tmp3'.encode(AS_DB,$session->getSessionID()).';';
      $this->_db_connector->performQuery($query);
      unset($session);
      $this->_handle_tmp_manual = false;
   }

   /**
      get latest version id for a material item
   */
   function getLatestVersionID($item_id) {
      $latest_version = NULL;
      $query = "SELECT MAX(".$this->addDatabasePrefix("materials").".version_id) AS version_id FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".item_id = '".encode(AS_DB,$item_id)."'";
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting one material item from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $rs = $result[0];
         $latest_version = $rs['version_id'];
      }
      return $latest_version;
   }

   /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      include_once('functions/text_functions.php');
      $db_array['extras'] = mb_unserialize($db_array['extras']);
      return parent::_buildItem($db_array);
   }

   /** build a new material item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    */
   function getNewItem () {
      include_once('classes/cs_material_item.php');
      return new cs_material_item($this->_environment);
   }

   /** update a material - internal, do not use -> use method save
    * this method updates a material
    *
    * @param object cs_item material_item the material
    */
   function _update ($material_item) {
      parent::_update($material_item);
      $modificator = $material_item->getModificatorItem();
      if ( !isset($modificator) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating new material: Modificator is not set',E_USER_ERROR);
      } else {
         $public = $material_item->isPublic() ? '1' : '0';
         $copy_id = NULL;
         $copy_item = $material_item->getCopyItem();
         if (isset($copy_item)){
            $copy_id = $copy_item->getItemID();
         } else {
            $copy_id = '0';
         }
         if ($material_item->getWorldPublic()) {
            $world_public = $material_item->getWorldPublic();
         } else {
            $world_public = '0';
         }
         $modification_date = getCurrentDateTimeInMySQL();
         if ($material_item->isNotActivated()){
            $modification_date = $material_item->getModificationDate();
         }
         $query = 'UPDATE '.$this->addDatabasePrefix('materials').' SET '.
                  'modification_date="'.$modification_date.'",'.
                  'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
                  'title="'.encode(AS_DB,$material_item->getTitle()).'",'.
                  'description="'.encode(AS_DB,$material_item->getDescription()).'",'.
                  'publishing_date="'.encode(AS_DB,$material_item->getPublishingDate()).'",'.
                  'author="'.encode(AS_DB,$material_item->getAuthor()).'",'.
                  'public="'.encode(AS_DB,$public).'",'.
                  'world_public="'.encode(AS_DB,$world_public).'",'.
                  'copy_of="'.encode(AS_DB,$copy_id).'",'.
                  'extras="'.encode(AS_DB,serialize($material_item->getExtraInformation())).'",'.
                  'workflow_status="'.encode(AS_DB,$material_item->getWorkflowTrafficLight()).'",'.
                  'workflow_resubmission_date="'.encode(AS_DB,$material_item->getWorkflowResubmissionDate()).'",'.
                  'workflow_validity_date="'.encode(AS_DB,$material_item->getWorkflowValidityDate()).'"'.
                  ' WHERE item_id="'.encode(AS_DB,$material_item->getItemID()).'"'.
                  ' AND version_id="'.encode(AS_DB,$material_item->getVersionID()).'"';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems updating material from query: "'.$query.'"',E_USER_WARNING);
         }
      }
   }

   /** create a material - internal, do not use -> use method save
    * this method creates a material
    *
    * @param object cs_item material_item the material
    */
   function _create ($material_item) {
     $context_id = $material_item->getContextID();
     if ( !isset($context_id) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating new material: ContextID is not set',E_USER_ERROR);
     } else {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB,$context_id).'",'.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'type="'.encode(AS_DB,$material_item->getItemType(NONE)).'",'.
                 'draft="'.encode(AS_DB,$material_item->isDraft()).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems creating material from query: "'.$query.'"',E_USER_WARNING);
           $this->_create_id = NULL;
        } else {
           $this->_create_id = $result;
           $material_item->setItemID($this->getCreateID());
           $this->_newmaterial($material_item);
        }
     }
  }

  /** creates a new material - internal, do not use -> use method save
    * this method creates a new material
    *
    * @param object cs_item material_item the material
    */
  function _newmaterial ($material_item) {
     $user = $material_item->getCreatorItem();
     $modificator = $material_item->getModificatorItem();
     $context_id = $material_item->getContextID();
     if ( !isset($user) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating new material: Creator is not set',E_USER_ERROR);
     } elseif ( !isset($modificator) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating new material: Modificator is not set',E_USER_ERROR);
     } elseif ( !isset($context_id) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating new material: ContextID is not set',E_USER_ERROR);
     } else {
        $current_datetime = getCurrentDateTimeInMySQL();
        $copy_id = NULL;
        $copy_item = $material_item->getCopyItem();
        if (isset($copy_item)){
           $copy_id = $copy_item->getItemID();
        } else {
           $copy_id = '0';
        }
        $public = $material_item->isPublic() ? '1' : '0';
         if ($material_item->getWorldPublic()) {
            $world_public = $material_item->getWorldPublic();
         } else {
            $world_public = '0';
         }
        $modification_date = getCurrentDateTimeInMySQL();
        if ($material_item->isNotActivated()){
           $modification_date = $material_item->getModificationDate();
        }
        $query = 'INSERT INTO '.$this->addDatabasePrefix('materials').' SET '.
                 'item_id="'.encode(AS_DB,$material_item->getItemID()).'",'.
                 'version_id="'.encode(AS_DB,$material_item->getVersionID()).'",'.
                 'context_id="'.encode(AS_DB,$context_id).'",'.
                 'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
                 'creation_date="'.$current_datetime.'",'.
                 'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
                 'modification_date="'.$modification_date.'",'.
                 'title="'.encode(AS_DB,$material_item->getTitle()).'",'.
                 'description="'.encode(AS_DB,$material_item->getDescription()).'",'.
                 'publishing_date="'.encode(AS_DB,$material_item->getPublishingDate()).'",'.
                 'author="'.encode(AS_DB,$material_item->getAuthor()).'",'.
                 'public="'.encode(AS_DB,$public).'",'.
                 'world_public="'.encode(AS_DB,$world_public).'",'.
                 'copy_of="'.encode(AS_DB,$copy_id).'",'.
                 'extras="'.encode(AS_DB,serialize($material_item->getExtraInformation())).'",'.
                 'workflow_status="'.encode(AS_DB,$material_item->getWorkflowTrafficLight()).'",'.
                 'workflow_resubmission_date="'.encode(AS_DB,$material_item->getWorkflowResubmissionDate()).'",'.
                 'workflow_validity_date="'.encode(AS_DB,$material_item->getWorkflowValidityDate()).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
          include_once('functions/error_functions.php');
          trigger_error('Problems creating material from query: "'.$query.'"',E_USER_WARNING);
        }
     }
  }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();
     if (!empty($item_id)) {
        if ($item->_version_id_changed){
           $this->_newmaterial($item);
        }else{
           $this->_update($item);
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

  /** save a new version of a material
    *
    * @param object cs_item material_item the material
    */
  function _save_version ($material_item) {
     $context_id = $material_item->getContextID();
     if (isset($context_id) and ($context_id != $this->_environment->getCurrentContextID())) {
        include_once('functions/error_functions.php');trigger_error('Context ID is not equal: ',E_USER_WARNING);
     }
     $this->_newmaterial($material_item);
     unset($material_item);
  }

  /**
   * documentation TBD
   */
  function delete ($material_id, $version_id = NULL) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID();
     if ( !isset($current_user) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting material: Deleter is not set',E_USER_ERROR);
     } else {
        $query = 'UPDATE '.$this->addDatabasePrefix('materials').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB,$user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB,$material_id).'"';
        if (!is_null($version_id)) {
           $query .= ' AND version_id="'.encode(AS_DB,$version_id).'"';
        }
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) or !$result ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems deleting material: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
        } else {
           if ( is_null($version_id) ) {
              parent::delete($material_id);
           }
        }
     }
     unset($current_user);
  }

  /**
   * documentation TBD
   */
  /*function _deleteVersion ($material_id, $version_id) {
     $this->delete($material_id, $version_id);
  }*/

   /**
    * checks if label type is supported in the current context
    * so far only groups are checked within contexts, since they can be "switched off"
    * @return boolean TRUE if supported, FALSE otherwise
    */
   function _isAvailable() {
      // check if materials are available in the context
      include_once('functions/error_functions.php');trigger_error('n i y',E_USER_ERROR);
      if($this->_environment->inProjectRoom()) {
         if(!empty($this->_room_limit)) {
            $room_manager = $this->_environment->getProjectManager();
            $room_item = $room_manager->getItem($this->_room_limit);
            unset($room_manager);
         } else {
            $room_item = $this->_environment->getCurrentRoomItem();
         }
         return $room_item->withRubric(CS_MATERIAL_TYPE);
      } else {
         return true;
      }
   }

   function mergeAccount($new_id,$old_id) {
      parent::mergeAccounts($new_id,$old_id);
      $query = 'UPDATE '.$this->addDatabasePrefix('material_link_file').' SET deleter_id = "'.encode(AS_DB,$new_id).'" WHERE deleter_id = "'.encode(AS_DB,$old_id).'";';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems creating material_link_file from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountMaterials ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("materials").".item_id) as number FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ((".$this->addDatabasePrefix("materials").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("materials").".creation_date < '".encode(AS_DB,$end)."') or (".$this->addDatabasePrefix("materials").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("materials").".modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all materials from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewMaterials ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("materials").".item_id) as number FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("materials").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("materials").".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting materials from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModMaterials ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("materials").".item_id) as number FROM ".$this->addDatabasePrefix("materials")." WHERE ".$this->addDatabasePrefix("materials").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("materials").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("materials").".modification_date < '".encode(AS_DB,$end)."' and ".$this->addDatabasePrefix("materials").".modification_date != ".$this->addDatabasePrefix("materials").".creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting materials from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

    function deleteMaterialsOfUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== true) {
            // create backup of item
            $this->backupItem($uid, array(
                'title' => 'title',
                'description' => 'description',
                'modification_date' => 'modification_date',
                'public' => 'public',
            ), array(
                'author', 'publishing_date', 'extras'
            ));

            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('materials').'.* FROM ' . $this->addDatabasePrefix('materials').' WHERE ' . $this->addDatabasePrefix('materials') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('materials') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'flag') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === false) {
                        $updateQuery .= ' title = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '",';
                        $updateQuery .= ' author = "",';
                        $updateQuery .= ' publishing_date = "",';
                        $updateQuery .= ' extras = "",';
                        $updateQuery .= ' public = "1"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB,$rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        trigger_error('Problems automatic deleting materials from query: "' . $insert_query . '"', E_USER_WARNING);
                    }
                }
            }
        }
    }

	function getResubmissionItemIDsByDate($year, $month, $day){
	   $query = 'SELECT item_id, version_id FROM '.$this->addDatabasePrefix('materials').' WHERE workflow_resubmission_date = "'.$year.'-'.$month.'-'.$day.'" AND deletion_date IS NULL';
	   return $this->_db_connector->performQuery($query);
	}

	function setWorkflowStatus($item_id, $status, $version_id){
	   $query = 'UPDATE '.$this->addDatabasePrefix('materials').' SET workflow_status = "'.$status.'" WHERE item_id = '.$item_id.' AND version_id = '.$version_id;
	   return $this->_db_connector->performQuery($query);
	}

   function getValidityItemIDsByDate($year, $month, $day){
	   $query = 'SELECT item_id, version_id FROM '.$this->addDatabasePrefix('materials').' WHERE workflow_validity_date = "'.$year.'-'.$month.'-'.$day.'" AND deletion_date IS NULL';
	   return $this->_db_connector->performQuery($query);
	}
	
	function export_item($id) {
	   $item = $this->getItem($id);
	
   	$xml = new SimpleXMLElementExtended('<material_item></material_item>');
   	$xml->addChildWithCDATA('item_id', $item->getItemID());
   	$xml->addChildWithCDATA('version_id', $item->getVersionID());
   	$xml->addChildWithCDATA('context_id', $item->getContextID());
   	$xml->addChildWithCDATA('creator_id', $item->getCreatorID());
   	$xml->addChildWithCDATA('deleter_id', $item->getDeleterID());
   	$xml->addChildWithCDATA('creation_date', $item->getCreationDate());
   	$xml->addChildWithCDATA('modifier_id', $item->getModificatorID());
   	$xml->addChildWithCDATA('modification_date', $item->getModificationDate());
   	$xml->addChildWithCDATA('deletion_date', $item->getDeletionDate());
   	$xml->addChildWithCDATA('title', $item->getTitle());
   	$xml->addChildWithCDATA('description', $item->getDescription());
   	$xml->addChildWithCDATA('author', $item->getAuthor());
   	$xml->addChildWithCDATA('publishing_date', $item->getPublishingDate());
   	$xml->addChildWithCDATA('public', $item->isPublic());
   	$xml->addChildWithCDATA('world_public', $item->isWorldPublic());

   	$extras_array = $item->getExtraInformation();
      $xmlExtras = $this->getArrayAsXML($xml, $extras_array, true, 'extras');
      $this->simplexml_import_simplexml($xml, $xmlExtras);
   	
   	//$xml->addChild('new_hack', $item->getItemID());
   	$copy_item = $item->getCopyItem();
   	if ($copy_item != null) {
   	   $xml->addChildWithCDATA('copy_of', $copy_item->getItemID());
   	} else {
      	$xml->addChildWithCDATA('copy_of', '');
   	}
   	//$xml->addChild('workflow_status', $item->getWorkflowStatus());
   	$xml->addChildWithCDATA('workflow_resubmission_date', $item->getWorkflowResubmissionDate());
   	$xml->addChildWithCDATA('workflow_validity_date', $item->getWorkflowValidityDate());
   	
   	$xmlFiles = $this->getFilesAsXML($item->getItemID());
      $this->simplexml_import_simplexml($xml, $xmlFiles);
   	
   	$xmlAnnotations = $this->getAnnotationsAsXML($item->getItemID());
      $this->simplexml_import_simplexml($xml, $xmlAnnotations);
   	
   	$xml = $this->export_sub_items($xml, $item);
   	
   	return $xml;
	}
	
   function export_sub_items($xml, $top_item) {
      $section_manager = $this->_environment->getManager('section');
      $section_manager->setContextLimit($top_item->getContextID());
      $section_manager->setMaterialItemIDLimit($top_item->getItemID());
      $section_manager->select();
      $section_list = $section_manager->get();
   	
      $section_item_xml_array = array();
      if (!$section_list->isEmpty()) {
         $section_item = $section_list->getFirst();
         while ($section_item) {
            $section_id = $section_item->getItemID();
            $section_item_xml_array[] = $section_manager->export_item($section_id);
            $section_item = $section_list->getNext();
         }
      }

      $section_xml = new SimpleXMLElementExtended('<section></section>');
      foreach ($section_item_xml_array as $section_item_xml) {
         $this->simplexml_import_simplexml($section_xml, $section_item_xml);
      }
   
      $this->simplexml_import_simplexml($xml, $section_xml);
      
      return $xml;
   }
   
   function import_item($xml, $top_item, &$options) {
      $item = null;
      if ($xml != null) {
         $item = $this->getNewItem();
         $item->setTitle((string)$xml->title[0]);
         $item->setDescription((string)$xml->description[0]);
         $item->setContextId($top_item->getItemId());
         $item->setVersionId((string)$xml->version_id[0]);
         $item->setAuthor((string)$xml->author[0]);
         $item->setPublishingDate((string)$xml->publishing_date[0]);
         $item->setPublic((string)$xml->public[0]);
         $item->setWorldPublic((string)$xml->world_public[0]);
         $extra_array = $this->getXMLAsArray($xml->extras);
         $item->setExtraInformation($extra_array['extras']);
         $temp_item = $this->getNewItem();
         $temp_item->setItemID((string)$xml->copy_of[0]);
         $item->setCopyItem($temp_item);
         $item->setWorkflowResubmissionDate((string)$xml->workflow_resubmission_date[0]);
         $item->setWorkflowValidityDate((string)$xml->workflow_validity_date[0]);
         $item->save();
         $this->importAnnotationsFromXML($xml, $item);
         $this->importFilesFromXML($xml, $item, $options);
         $this->import_sub_items($xml, $item, $options);
      }
      
      $options[(string)$xml->item_id[0]] = $item->getItemId();

      return $item;
   }
	
	function import_sub_items($xml, $top_item, &$options) {
      if ($xml->section != null) {
         $section_manager = $this->_environment->getSectionManager();
         foreach ($xml->section->children() as $section_xml) {
            $temp_section_item = $section_manager->import_item($section_xml, $top_item, $options);
         }
      }
   }
} // end of class
?>