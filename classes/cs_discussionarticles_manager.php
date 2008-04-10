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
include_once('classes/cs_list.php');

/** cs_discussion_item is needed to create discussionarticle items
 */
include_once('classes/cs_discussionarticle_item.php');

/** upper class of the annotation manager
 */
include_once('classes/cs_manager.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

// TBD
include_once('functions/text_functions.php');

/** class for database connection to the database table "discussionarticles"
 * this class implements a database manager for the table "discussionarticles"
 */
class cs_discussionarticles_manager extends cs_manager {

  /**
   * integer - containing the age of the discussionarticle as a limit
   */
  var $_age_limit = NULL;

  /**
   * integer - containing the id of a discussion as a limit for the selected discussionarticles
   */
  var $_discussion_limit = NULL;

  /**
   * integer - containing a start point for the selected discussionarticles
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many discussions the select statement should get
   */
  var $_interval_limit = NULL;

  /**
   * string - containing an order limit for the selected discussion
   */
  var $_order = NULL;

  /**
   * object manager - containing object to the select materials for discussionarticles
   */
  var $_material_manager = NULL;

  /**
   * object manager - containing object to the manage discussions
   */
  var $_discussion_manager = NULL;

  /**
   * integer - containing the item id of the current article
   */
  var $_current_article_id = NULL;

  /**
   * string - containing the modification date of the current article
   */
  var $_current_article_modification_date = NULL;

  var $_sort_position = false;

  var $_all_discarticle_list = NULL;
  var $_cached_discussion_item_ids = array();



  /** constructor: cs_discussionarticles_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function cs_discussionarticles_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'discussionarticles';
   }

  /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_age_limit = NULL;
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_order = NULL;
     $this->_sort_position = false;
  }

  /** set age limit
    * this method sets an age limit for discussionarticles
    *
    * @param integer limit age limit for discussionarticles
    */
  function setAgeLimit ($limit) {
     $this->_age_limit = (integer)$limit;
  }

  /** set discussion limit
    * this method sets an discussion limit for discussionarticles
    *
    * @param integer limit discussion limit for discussionarticles
    */
  function setDiscussionLimit ($discussion) {
     $this->_discussion_limit = (integer)$discussion;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected discussionarticles
    * @param integer interval interval limit for selected discussionarticles
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (integer)$interval;
     $this->_from_limit = (integer)$from;
  }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected discussionarticles
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

  function setSortPosition(){
     $this->_sort_position = true;
  }


  function setRoomLimit ($limit) {
     $this->_room_limit = (string)$limit;
  }

  function _performQuery($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count(discussionarticles.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT discussionarticles.item_id';
     } else {
        $query = 'SELECT discussionarticles.*';
     }
     $query .= ' FROM discussionarticles';

     $query .= ' WHERE 1';

     // fifth, insert limits into the select statement
     if (isset($this->_room_limit) and $this->_environment->getCurrentFunction()!='clipboard_index') {
        $query .= ' AND discussionarticles.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND discussionarticles.deleter_id IS NULL';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND discussionarticles.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND discussionarticles.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
     if (isset($this->_typ_limit)) {
        $query .= ' AND discussionarticles.type = "'.encode(AS_DB,$this->_typ_limit).'"';
     }
     if (isset($this->_discussion_limit)) {
        $query .= ' AND discussionarticles.discussion_id = "'.encode(AS_DB,$this->_discussion_limit).'"';
     }
     if (isset($this->_group_limit)) {
        $query .= ' AND links.to_item_id="'.encode(AS_DB,$this->_group_limit).'" AND links.link_type="relevant_for"';
     }

      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }


     if ($this->_sort_position){
        $query .= ' ORDER BY discussionarticles.position ASC';
     }else{
        $query .= ' ORDER BY discussionarticles.creation_date ASC, discussionarticles.subject DESC';
     }
      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting discarticles.',E_USER_WARNING);
     } else {
        return $result;
     }
  }

  /** get a discussionartcile in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a discussionarticle
    */
  function getItem ($item_id) {
     $discussionarticles = NULL;
     $query = "SELECT * FROM discussionarticles WHERE discussionarticles.item_id = '".encode(AS_DB,$item_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or empty($result[0]) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting one discussionarticles item.',E_USER_WARNING);
     } else {
        $discussionarticles = $this->_buildItem($result[0]);
     }
     return $discussionarticles;
  }

  /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
  function getItemList ($id_array) {
     return $this->_getItemList('discussionarticles', $id_array);
  }

  /** build a new discussionarticle item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    */
   function getNewItem () {
      return new cs_discussionarticle_item($this->_environment);
   }

  /** get a list of section in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_section_items
    */
   function getAllDiscArticlesItemListByIDArray ($id_array) {
      if (empty($id_array)) {
         include_once('classes/cs_section_list.php');
         return new cs_section_list();
      } else {
         $section = NULL;
         $query = "SELECT * FROM discussionarticles WHERE discussion_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " AND discussionarticles.deleter_id IS NULL";
         $query .= " AND discussionarticles.deletion_date IS NULL";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of section items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $discarticle_list = new cs_list();
            foreach ($result as $rs) {
               $discarticle_list->add($this->_buildItem($rs));
            }
         }
         $this->_all_discarticle_list = $discarticle_list;
         $this->_cached_discussion_item_ids = $id_array;
         return $discarticle_list;
      }
   }


   function getAllArticlesForItem($discussion_item,$show_all=false){
      $item_id = $discussion_item->getItemID();
      $version_id = $discussion_item->getVersionID();
      if (in_array($item_id,$this->_cached_discussion_item_ids)){
         $list = new cs_list();
         $discarticle_list = $this->_all_discarticle_list;
         $discarticle_item = $discarticle_list->getFirst();
         while($discarticle_item){
            if($item_id == $discarticle_item->getDiscussionID() ){
               $list->add($discarticle_item);
            }
            $discarticle_item = $discarticle_list->getNext();
         }
         unset($discarticle_list);
         unset($discarticle_item);
         return $list;
      }else{
         $this->reset();
         $this->setContextLimit($discussion_item->getContextID());
         $this->setDiscussionLimit($discussion_item->getItemID());
         if ($show_all == true) {
            $this->setDeleteLimit(false);
         }
         $this->select();
         return $this->get();
      }
   }

  /** update a discussion - internal, do not use -> use method save
    * this method updates a discussion
    *
    * @param object cs_item discussion_item the discussion
    */
  function _update ($discussionarticle_item) {
     parent::_update($discussionarticle_item);
     $this->_current_article_modification_date = getCurrentDateTimeInMySQL();
     $this->_current_article_id = $discussionarticle_item->getItemID();
     $query = 'UPDATE discussionarticles SET '.
              'modification_date="'.$this->_current_article_modification_date.'",'.
              'subject="'.encode(AS_DB,$discussionarticle_item->getSubject()).'",'.
              'description="'.encode(AS_DB,$discussionarticle_item->getDescription()).'",'.
              'position="'.encode(AS_DB,$discussionarticle_item->getPosition()).'",'.
              'modifier_id="'.encode(AS_DB,$this->_current_user->getItemID()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$discussionarticle_item->getItemID()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating discussionarticle item: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($discussionarticle_item);
  }

  /** create a discussionarticle - internal, do not use -> use method save
    * this method creates a discussionarticle
    *
    * @param object cs_item discussion_item the discussionarticle
    */
  function _create ($discussionarticle_item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$discussionarticle_item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="'.CS_DISCARTICLE_TYPE.'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating discussionarticle item.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $this->_current_article_id = $this->_create_id;
        $discussionarticle_item->setItemID($this->getCreateID());
        $this->_newDiscussionArticle($discussionarticle_item);
     }
     unset($discussionarticle_item);
  }

  /** creates a new discarticlearticle - internal, do not use -> use method save
    * this method creates a new discarticlearticle
    *
    * @param object cs_item discarticlearticle_item the discarticlearticle
    */
  function _newDiscussionArticle ($discussionarticle_item) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $this->_current_article_modification_date = $current_datetime;
     $query = 'INSERT INTO discussionarticles SET '.
              'item_id="'.encode(AS_DB,$discussionarticle_item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$discussionarticle_item->getContextID()).'",'.
              'discussion_id="'.encode(AS_DB,$discussionarticle_item->getDiscussionID()).'",'.
              'creator_id="'.encode(AS_DB,$this->_current_user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modification_date="'.$current_datetime.'",'.
              'subject="'.encode(AS_DB,$discussionarticle_item->getSubject()).'",'.
              'position="'.encode(AS_DB,$discussionarticle_item->getPosition()).'",'.
              'description="'.encode(AS_DB,$discussionarticle_item->getDescription()).'"';
     $discussionarticle_item->setCreationDate($current_datetime);
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating discarticle.',E_USER_WARNING);
     }
     unset($discussionarticle_item);
  }

  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID();
     $query = 'UPDATE discussionarticles SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting discarticle.',E_USER_WARNING);
     } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         parent::delete($item_id);
         unset($link_manager);
     }
     unset($current_user);
  }

   ########################################################
   # statistic functions
   ########################################################

   function getCountDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' and ((".$this->_db_table.".creation_date > '".encode(AS_DB,$start)."' and ".$this->_db_table.".creation_date < '".encode(AS_DB,$end)."') or (".$this->_db_table.".modification_date > '".encode(AS_DB,$start)."' and ".$this->_db_table.".modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->_db_table.".creation_date > '".encode(AS_DB,$start)."' and ".$this->_db_table.".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->_db_table.".modification_date > '".encode(AS_DB,$start)."' and ".$this->_db_table.".modification_date < '".encode(AS_DB,$end)."' and ".$this->_db_table.".modification_date != ".$this->_db_table.".creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function deleteDiscarticlesOfUser($uid) {
      $query  = 'SELECT discussionarticles.* FROM discussionarticles WHERE discussionarticles.creator_id = "'.encode(AS_DB,$uid).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !empty($result) ) {
         foreach ( $result as $rs ) {
            $insert_query = 'UPDATE discussionarticles SET';
            $insert_query .= ' subject = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
            $insert_query .= ' description = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'"';
            $insert_query .=' WHERE item_id = "'.encode(AS_DB,$rs['item_id']).'"';
            $result2 = $this->_db_connector->performQuery($insert_query);
            if ( !isset($result2) or !$result2 ) {
               include_once('functions/error_functions.php');trigger_error('Problems automatic deleting discussionarticles.',E_USER_WARNING);
            }
         }
      }
   }
}
?>