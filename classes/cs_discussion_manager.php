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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** upper class of the discussion manager
 */
include_once('classes/cs_manager.php');


include_once('functions/text_functions.php');

/** class for database connection to the database table "discussion"
 * this class implements a database manager for the table "discussion"
 */
class cs_discussion_manager extends cs_manager {

   /**
   * integer - containing the age of discussion as a limit
   */
   var $_age_limit = NULL;

   /**
   * integer - containing a start point for the select discussion
   */
   var $_from_limit = NULL;

   var $_cached_items = array();

   /**
   * integer - containing how many discussion the select statement should get
   */
   var $_interval_limit = NULL;

   var $_discussion_type_limit =NULL;

   /**
   *  array - containing an id-array as search limit
   */
   var $_id_array_limit = array();

   var $_group_limit = NULL;
   var $_topic_limit = NULL;
   var $_institution_limit = NULL;
   var $_sort_order = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @author CommSy Development Group
    */
   function cs_discussion_manager ($environment) {
      $this->cs_manager($environment);
     $this->_db_table = 'discussions';
   }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    *
    * @author CommSy Development Group
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_age_limit = NULL;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_order = NULL;
      $this->_group_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_institution_limit = NULL;
      $this->_sort_order = NULL;
      $this->_discussion_type_limit = NULL;
   }

   /** set age limit
    * this method sets an age limit for discussion
    *
    * @param integer limit age limit for discussion
    *
    * @author CommSy Development Group
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected discussion
    * @param integer interval interval limit for selected discussion
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

  /** set order limit to latest article
    * this method sets an order limit for the select statement
    */
  function setOrderToLatestArticle () {
     $this->_order = 'latest_article';
  }

   function setGroupLimit ($limit) {
      $this->_group_limit = (int)$limit;
   }

   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   function setInstitutionLimit ($limit) {
      $this->_institution_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function setDiscussionTypeLimit ($limit) {
      $this->_discussion_type_limit = (string)$limit;
   }

  /** set limit to array of discussion item_ids
    *
    * @param array array of ids to be loaded from db
    *
    * @author CommSy Development Group
    */
   function setIDArrayLimit ($id_array){
     $this->_id_array_limit = (array)$id_array;
   }

   function getIDs () {
      return $this->getIDArray();
   }

   function _performQuery($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count(DISTINCT discussions.item_id) AS count';
     } elseif ($mode == 'id_array') {
        $query = 'SELECT DISTINCT discussions.item_id';
     } elseif ($mode == 'distinct') {
        $query = 'SELECT DISTINCT '.$this->_db_table.'.*';
     } else {
        $query = 'SELECT DISTINCT discussions.*';
     }
     $query .= ' FROM discussions';

     // join to user database table
     if ((isset($this->_search_array) AND !empty($this->_search_array)) OR isset($this->_sort_order)) {
        // join to user database table
        $query .= ' LEFT JOIN user AS people ON (people.item_id=discussions.creator_id )'; // modificator_id (TBD)
     }
     if ( ( isset($this->_search_array) AND !empty($this->_search_array) )
          or isset($this->_sort_order)
          or ( isset($this->_only_files_limit) and $this->_only_files_limit )
        ) {
        $query .= ' LEFT JOIN discussionarticles ON (discussionarticles.discussion_id = discussions.item_id AND discussionarticles.context_id = "'.encode(AS_DB,$this->_room_limit).'")';
        if (!isset($this->_buzzword_limit)) {
           $query .= ' LEFT JOIN links AS l8 ON l8.from_item_id=discussions.item_id AND l8.link_type="buzzword_for"';
           $query .= ' LEFT JOIN labels AS buzzwords ON l8.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
        }

     }
     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN link_items AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id=discussions.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id=discussions.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_institution_limit) ) {
        $query .= ' LEFT JOIN link_items AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id=discussions.item_id AND l41.second_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id=discussions.item_id AND l42.first_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN link_items AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id=discussions.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id=discussions.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagID($this->_tag_limit);
        $query .= ' LEFT JOIN link_items AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id=discussions.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id=discussions.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
     }

      // restrict discussions by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN links AS l6 ON l6.from_item_id=discussions.item_id AND l6.link_type="buzzword_for"';
            $query .= ' LEFT JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN links AS l6 ON l6.from_item_id=discussions.item_id AND l6.link_type="buzzword_for"';
            $query .= ' INNER JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }

      // restrict material by discusson
      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN link_items AS l5 ON ( (l5.first_item_id=discussions.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR(l5.second_item_id=discussions.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' INNER JOIN item_link_file AS lf ON discussionarticles.item_id = lf.item_iid';
      }

     $query .= ' WHERE 1=1';

     // fifth, insert limits into the select statement
     if (isset($this->_room_limit)) {
        $query .= ' AND discussions.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND discussions.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND discussions.deleter_id IS NULL';
     }
     if (isset($this->_discussion_type_limit)) {
        $query .= ' AND (discussions.discussion_type = "'.encode(AS_DB,$this->_discussion_type_limit).'" )';
     }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND (discussions.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'" )';
      }
     if (isset($this->_age_limit)) {
        $query .= ' AND discussions.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND discussions.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
     if(!empty($this->_id_array_limit)) {
        $query .= ' AND discussions.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
     }

      if ( isset($this->_topic_limit) ){
         if($this->_topic_limit == -1){
            $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
            $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
            $query .= ' OR (l22.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l22.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'"))';
         }
      }
      if ( isset($this->_institution_limit) ){
         if ($this->_institution_limit == -1) {
            $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
            $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
         } else {
            $query .= ' AND ((l41.first_item_id = "_institution_limit" OR l41.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'")';
            $query .= ' OR (l42.first_item_id = "_institution_limit" OR l42.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'"))';
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

      if ( isset($this->_tag_limit) ) {
         $tag_id_array = $this->_getTagIDArrayByTagID($this->_tag_limit);
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
            $query .= ' AND (l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL)';
         }else{
            $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_buzzword_limit).'"';
         }
      }
      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         $field_array = array('TRIM(CONCAT(people.firstname," ",people.lastname))','discussions.title','discussions.modification_date','discussionarticles.subject','discussionarticles.description');
         $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
         $query .= $search_limit_query_code;
         $query .= ')';
      }

      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' AND lf.deleter_id IS NULL AND lf.deletion_date IS NULL';
      }

     if (isset($this->_search_array) AND !empty($this->_search_array)) {
        $query .= ' GROUP BY discussions.item_id';
     }
     if ( isset($this->_sort_order) ) {
        if ( $this->_sort_order == 'latest' ) {
           $query .= ' ORDER BY discussions.latest_article_modification_date DESC';
        } elseif ( $this->_sort_order == 'latest_rev' ) {
           $query .= ' ORDER BY discussions.latest_article_modification_date';
        } elseif ( $this->_sort_order == 'title' ) {
           $query .= ' ORDER BY discussions.title';
        } elseif ( $this->_sort_order == 'title_rev' ) {
           $query .= ' ORDER BY discussions.title DESC';
        } elseif ( $this->_sort_order == 'creator' ) {
           $query .= ' ORDER BY people.lastname';
        } elseif ( $this->_sort_order == 'creator_rev' ) {
           $query .= ' ORDER BY people.lastname DESC';
        }
     }
     else {
           $query .= ' ORDER BY discussions.modification_date DESC, discussions.title DESC';
     }
      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting discussion.     ',E_USER_WARNING);
     } else {
         return $result;
     }
   }

   /** build a new material item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    *
    * @author CommSy Development Group
    */
   function getNewItem () {
      include_once('classes/cs_discussion_item.php');
      return new cs_discussion_item($this->_environment);
   }

  /** get a discussion in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    *
    * @author CommSy Development Group
    */
     function getItem ($item_id) {
        $discussion = NULL;
        if (array_key_exists($item_id,$this->_cached_items)){
           return $this->_buildItem($this->_cached_items[$item_id]);
        }else{
           $query = "SELECT * FROM discussions WHERE discussions.item_id = '".encode(AS_DB,$item_id)."'";
           $result = $this->_db_connector->performQuery($query);
           if ( !isset($result) or empty($result[0]) ) {
              include_once('functions/error_functions.php');trigger_error('Problems selecting one discussions item.',E_USER_WARNING);
           } else {
              $this->_cached_items[$result[0]['item_id']] = $result[0];
              $discussion = $this->_buildItem($result[0]);
           }
           return $discussion;
        }
     }

   function getItemList($id_array) {
      return $this->_getItemList('discussion', $id_array);
   }

  /** update a discussion - internal, do not use -> use method save
   * this method updates the database record for a given discussion item
   *
   * @param cs_discussion_item the discussion item for which an update should be made
   */
   function _update ($item) {
      parent::_update($item);

      $modificator = $item->getModificatorItem();
      $current_datetime = getCurrentDateTimeInMySQL();

     if ( $item->isPublic() ) {
        $public = '1';
     } else {
        $public = '0';
     }

     if ( $item->getDiscussionStatus() ) {
        $status = $item->getDiscussionStatus();
     } else {
        $status = '1';
     }

     if ( $item->getDiscussionType() ) {
        $type = $item->getDiscussionType();
     } else {
        $type = 'simple';
     }

      $query = 'UPDATE discussions SET '.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
               'public="'.encode(AS_DB,$public).'"';
      $article_id = $item->getLatestArticleID();
      if (!empty($article_id)) {
         $query .= ', latest_article_item_id="'.encode(AS_DB,$article_id).'"';
      }
      $article_modification_date = $item->getLatestArticleModificationDate();
      if (!empty($article_modification_date)) {
         $query .= ', latest_article_modification_date="'.encode(AS_DB,$article_modification_date).'"';
      }
      $query .= ', status="'.encode(AS_DB,$status).'"';
      $query .= ', discussion_type="'.encode(AS_DB,$type).'"';
      $query .=      ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems updating discussion',E_USER_WARNING);
      }
      unset($item);
      unset($modificator);
   }

  /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'ndiscussion' in the database and sets the discussion items item id.
   * it then calls the private method _newNews to store the discussion item itself.
   * @param cs_discussion_item the discussion item for which an entry should be made
   */
  function _create ($item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="discussion"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating discussion.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newDiscussion($item);
     }
     unset($item);
  }

  /** store a new discussion item to the database - internal, do not use -> use method save
    * this method stores a newly created discussion item to the database
    *
    * @param cs_discussion_item the discussion item to be stored
    */
  function _newDiscussion ($item) {
     $user = $item->getCreatorItem();
     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ( $item->isPublic() ) {
        $public = '1';
     } else {
        $public = '0';
     }
     if ( $item->getDiscussionType() ) {
        $type = $item->getDiscussionType();
     } else {
        $type = 'simple';
     }

     $query = 'INSERT INTO discussions SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'discussion_type="'.encode(AS_DB,$type).'",'.
              'public="'.encode(AS_DB,$public).'"';
     $article_id = $item->getLatestArticleID();
     if (!empty($article_id)) {
        $query .= ', latest_article_item_id="'.encode(AS_DB,$article_id).'"';
     }
     $article_modification_date = $item->getLatestArticleModificationDate();
     if (!empty($article_modification_date)) {
        $query .= ', latest_article_modification_date="'.encode(AS_DB,$article_modification_date).'"';
     }
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating discussion.',E_USER_WARNING);
     }
     unset($item);
     unset($modificator);
  }

  /**  delete a discussion item
   *
   * @param cs_discussion_item the discussion item to be deleted
   *
   * @access public
   */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE discussions SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems deleting discussion.',E_USER_WARNING);
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         parent::delete($item_id);
         unset($link_manager);
      }
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountDiscussions ($start, $end) {
      $retour = 0;

      $query  = "SELECT count(DISTINCT discussions.item_id) as number FROM discussions, discussionarticles";
      $query .= " WHERE discussions.context_id = '".encode(AS_DB,$this->_room_limit)."'";
      $query .= " and ((discussions.creation_date > '".encode(AS_DB,$start)."' and discussions.creation_date < '".encode(AS_DB,$end)."')";
      $query .= " or (discussions.modification_date > '".encode(AS_DB,$start)."' and discussions.modification_date < '".encode(AS_DB,$end)."'))";
      $query .= " and discussions.item_id=discussionarticles.discussion_id";
      $query .= " and ((discussionarticles.creation_date > '".encode(AS_DB,$start)."' and discussionarticles.creation_date < '".encode(AS_DB,$end)."')";
      $query .= " or (discussionarticles.modification_date > '".encode(AS_DB,$start)."' and discussionarticles.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all discussions.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewDiscussions ($start, $end) {
      $retour = 0;

      $query = "SELECT count(discussions.item_id) as number FROM discussions WHERE discussions.context_id = '".encode(AS_DB,$this->_room_limit)."' and discussions.creation_date > '".encode(AS_DB,$start)."' and discussions.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting discussions.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModDiscussions ($start, $end) {
      $retour = 0;

      $query  = "SELECT count(DISTINCT discussions.item_id) as number FROM discussions, discussionarticles";
      $query .= " WHERE discussions.context_id = '".encode(AS_DB,$this->_room_limit)."'";
      $query .= " and discussions.modification_date > '".encode(AS_DB,$start)."' and discussions.modification_date < '".encode(AS_DB,$end)."'";
      $query .= " and discussions.modification_date != discussions.creation_date";
      $query .= " and discussions.item_id=discussionarticles.discussion_id";
      $query .= " and ((discussionarticles.creation_date > '".encode(AS_DB,$start)."' and discussionarticles.creation_date < '".encode(AS_DB,$end)."')";
      $query .= " or (discussionarticles.modification_date > '".encode(AS_DB,$start)."' and discussionarticles.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all discussions.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function deleteDiscussionsOfUser($uid) {
      $query  = 'SELECT discussions.* FROM discussions WHERE discussions.creator_id = "'.encode(AS_DB,$uid).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !empty($result) ) {
         foreach ($result as $rs) {
            $insert_query = 'UPDATE discussions SET';
            $insert_query .= ' title = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
            $insert_query .= ' public = "1"';
            $insert_query .=' WHERE item_id = "'.encode(AS_DB,$rs['item_id']).'"';
            $result2 = $this->_db_connector->performQuery($insert_query);
            if ( !isset($result2) or !$result2 ) {
               include_once('functions/error_functions.php');trigger_error('Problems automatic deleting discussions.',E_USER_WARNING);
            }
         }
      }
   }
}
?>