<?PHP
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

/** upper class of the totos manager
 */
include_once('classes/cs_manager.php');

include_once('functions/text_functions.php');

/** class for database connection to the database table "todo"
 * this class implements a database manager for the table "todo"
 */

class cs_todos_manager extends cs_manager {

   var $_age_limit = NULL;
   var $_future_limit = NULL;
   var $_from_limit = NULL;
   var $_interval_limit = NULL;
   var $_search_limit = NULL;
   var $_id_array_limit = array();
   var $_group_limit = NULL;
   var $_topic_limit = NULL;
   var $_sort_order = NULL;
   private $_assignment_limit = false;

   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor: cs_todo_manager
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'todos';
      $this->_translator = $environment->getTranslationObject();
   }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
   */
   function resetLimits () {
      parent::resetLimits();
      $this->_age_limit = NULL;
      $this->_future_limit = NULL;
      $this->_status_limit = NULL;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_search_limit = NULL;
      $this->_group_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_user_limit = NULL;
      $this->_sort_order = NULL;
      $this->_assignment_limit = false;
   }

   /** set age limit
    * this method sets an age limit for todo
    *
    * @param integer limit age limit for todo
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   function setAssignmentLimit ($array) {
      $this->_assignment_limit = true;
      if (isset($array[0])){
         $this->_related_user_limit = $array;
      }
   }

   function setStatusLimit ($limit) {
      $this->_status_limit = (int)$limit;
   }
   /** set future limit
    * Restricts selected dates to dates in the future
    */
   function setFutureLimit () {
      $this->_future_limit = TRUE;
   }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected todo
    * @param integer interval interval limit for selected todo
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

   function setGroupLimit ($limit) {
      $this->_group_limit = (int)$limit;
   }

   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function _performQuery ( $mode = 'select' ) {
      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix('todos').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('todos').'.item_id';
      } elseif ($mode == 'distinct') {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix('todos').'.*';
      }
      $query .= ' FROM '.$this->addDatabasePrefix('todos');
      $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('todos').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

     if ( ( isset($this->_search_array) AND !empty($this->_search_array) )
        ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('step').' ON ('.$this->addDatabasePrefix('step').'.todo_item_id = '.$this->addDatabasePrefix('todos').'.item_id AND '.$this->addDatabasePrefix('step').'.context_id = "'.encode(AS_DB,$this->_room_limit).'" AND '.$this->addDatabasePrefix('step').'.deletion_date IS NULL)';
     }
     if ( !empty($this->_search_array) ||
           (isset($this->_sort_order) and
           ($this->_sort_order == 'creator' || $this->_sort_order == 'creator_rev' || $this->_sort_order == 'modificator' || $this->_sort_order == 'modificator_rev')) ) {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS creator ON (creator.item_id='.$this->addDatabasePrefix('todos').'.creator_id )';
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS modificator ON (modificator.item_id='.$this->addDatabasePrefix('todos').'.modifier_id )';

         //look in filenames of linked files for the search_limit
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('item_link_file').' ON '.$this->addDatabasePrefix('todos').'.item_id = '.$this->addDatabasePrefix('item_link_file').'.item_iid'.
                   ' LEFT JOIN '.$this->addDatabasePrefix('files').' ON '.$this->addDatabasePrefix('item_link_file').'.file_id = '.$this->addDatabasePrefix('files').'.files_id';
         //look in filenames of linked files for the search_limit
     }
     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if ( isset($this->_user_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit1 ON ( user_limit1.deletion_date IS NULL AND ((user_limit1.first_item_id='.$this->addDatabasePrefix('todos').'.item_id AND user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit2 ON ( user_limit2.deletion_date IS NULL AND ((user_limit2.second_item_id='.$this->addDatabasePrefix('todos').'.item_id AND user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }

     if ( isset($this->_assignment_limit)  AND isset($this->_related_user_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit1 ON ( related_user_limit1.deletion_date IS NULL AND ((related_user_limit1.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND related_user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit2 ON ( related_user_limit2.deletion_date IS NULL AND ((related_user_limit2.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND related_user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }

     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
     }

      // restrict todos by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l6.link_type="buzzword_for"';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l6.link_type="buzzword_for"';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }

      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR(l5.second_item_id='.$this->addDatabasePrefix('todos').'.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf.item_iid';
      }

      $query .= ' WHERE 1';
      if (isset($this->_room_array_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.context_id IN ('.implode(", ", $this->_room_array_limit).')';
      }elseif (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }

       switch ($this->inactiveEntriesLimit) {
           case self::SHOW_ENTRIES_ONLY_ACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('todos') . '.activation_date  IS NULL OR ' . $this->addDatabasePrefix('todos') . '.activation_date  <= "' . getCurrentDateTimeInMySQL() . '")';
               break;
           case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('todos') . '.activation_date  IS NOT NULL AND ' . $this->addDatabasePrefix('todos') . '.activation_date  > "' . getCurrentDateTimeInMySQL() . '")';
               break;
       }

#      if ( $this->_future_limit ) {
#         $date = date("Y-m-d").' 00:00:00';
#         $query .= ' AND todos.date >= "'.$date.'"';
#      }
      if ( $this->_delete_limit == true ) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.deleter_id IS NULL';
      }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND ('.$this->addDatabasePrefix('todos').'.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'" )';
      }
      if (isset($this->_status_limit)) {
          if ($this->_status_limit == 4){
            $query .= ' AND ('.$this->addDatabasePrefix('todos').'.status != "3")';
         }else{
            $query .= ' AND ('.$this->addDatabasePrefix('todos').'.status = "'.encode(AS_DB,$this->_status_limit).'" )';
         }
      }
      if ( isset($this->_age_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('todos').'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
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
      if ( isset($this->_group_limit) ){
         if($this->_group_limit == -1){
            $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
            $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
            $query .= ' OR (l32.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$this->_group_limit).'"))';
         }
      }
      if ( isset($this->_user_limit) ){
         if($this->_user_limit == -1){
            $query .= ' AND (user_limit1.first_item_id IS NULL AND user_limit1.second_item_id IS NULL)';
            $query .= ' AND (user_limit2.first_item_id IS NULL AND user_limit2.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((user_limit1.first_item_id = "'.encode(AS_DB,$this->_user_limit).'" OR user_limit1.second_item_id = "'.encode(AS_DB,$this->_user_limit).'")';
            $query .= ' OR (user_limit2.first_item_id = "'.encode(AS_DB,$this->_user_limit).'" OR user_limit2.second_item_id = "'.encode(AS_DB,$this->_user_limit).'"))';
         }
      }

      if ( isset($this->_assignment_limit) AND isset($this->_related_user_limit) ){
         $query .= ' AND ( (related_user_limit1.first_item_id IN ('.implode(", ", $this->_related_user_limit).') OR related_user_limit1.second_item_id IN ('.implode(", ", $this->_related_user_limit).') )';
         $query .= ' OR  (related_user_limit2.first_item_id IN ('.implode(", ", $this->_related_user_limit).') OR related_user_limit2.second_item_id IN ('.implode(", ", $this->_related_user_limit).') ))';
      }

      if ( isset($this->_tag_limit) ) {
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
            $query .= ' AND (l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL)';
         }else{
            $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_buzzword_limit).'"';
         }
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         $field_array = array('TRIM(CONCAT(creator.firstname," ",creator.lastname))',$this->addDatabasePrefix('todos').'.description',$this->addDatabasePrefix('todos').'.title',$this->addDatabasePrefix('step').'.title',$this->addDatabasePrefix('step').'.description',$this->addDatabasePrefix('files').'.filename');
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

       if ($this->modificationNewerThenLimit) {
           $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date >= "' . $this->modificationNewerThenLimit->format('Y-m-d H:i:s') . '"';
       }

       if ($this->excludedIdsLimit) {
           $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.item_id NOT IN (' . implode(", ", encode(AS_DB, $this->excludedIdsLimit)) . ')';
       }

      // order
      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'date' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.modification_date DESC';
         } elseif ( $this->_sort_order == 'date_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.modification_date';
         } elseif ( $this->_sort_order == 'duedate' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.date DESC';
         } elseif ( $this->_sort_order == 'duedate_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.date';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.title';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.title DESC';
         } elseif ( $this->_sort_order == 'status' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.status';
         } elseif ( $this->_sort_order == 'status_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.status DESC';
         } elseif ( $this->_sort_order == 'creator' ) {
            $query .= ' ORDER BY creator.lastname';
         } elseif ( $this->_sort_order == 'creator_rev' ) {
            $query .= ' ORDER BY creator.lastname DESC';
         } elseif ( $this->_sort_order == 'modificator' ) {
            $query .= ' ORDER BY modificator.lastname';
         } elseif ( $this->_sort_order == 'modificator_rev' ) {
            $query .= ' ORDER BY modificator.lastname DESC';
         }
      }else{
         $query .= ' ORDER BY '.$this->addDatabasePrefix('todos').'.date DESC';
      }

      if ( $mode == 'select' ) {
         if ( isset($this->_interval_limit) and isset($this->_from_limit) ) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $i=0;
         while( isset($result[$i]) ){
            if ( isset($result[$i]['date']) ){
               $result[$i]['end_date'] = $result[$i]['date'];
               unset($result[$i]['date']);
            }
            $i++;
         }
         return $result;
      }
   }

   /** build a new todo item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    */
   function getNewItem () {
      include_once('classes/cs_todo_item.php');
      return new cs_todo_item($this->_environment);
   }

  /** get a todo
    *
    * @param integer item_id id of the item
    *
    * @return \cs_todo_item a todo
    */
   function getItem ($item_id) {
      $todo = NULL;
      if ( !empty($item_id)
           and !empty($this->_cache_object[$item_id])
         ) {
         return $this->_cache_object[$item_id];
      } elseif (array_key_exists($item_id,$this->_cached_items)){
         return $this->_buildItem($this->_cached_items[$item_id]);
      }else{
         $query = "SELECT * FROM ".$this->addDatabasePrefix("todos")." WHERE ".$this->addDatabasePrefix("todos").".item_id = '".encode(AS_DB,$item_id)."'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or empty($result[0])) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one todos item from query: "'.$query.'"',E_USER_WARNING);
         } else {
            if ( isset($result[0]['date']) ){
               $result[0]['end_date'] = $result[0]['date'];
               unset($result[0]['date']);
            }
            $todo = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
         }
         return $todo;
      }
   }

  /** get a list of todo in newest version
    *
    * @param array id_array ids of the items
    *
    * @return object cs_list of cs_todo_items
    */
   function getItemList ($id_array) {
      return $this->_getItemList("todo", $id_array);
   }

    /** update a todo - internal, do not use -> use method save
     * this method updates the database record for a given todo item
     *
     * @param cs_todo_item the todo item for which an update should be made
     */
    function _update($item)
    {
        /** @var cs_todo_item $item */
        parent::_update($item);

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update($this->addDatabasePrefix('todos'), 't')
            ->set('modifier_id', ':modifierId')
            ->set('modification_date', ':modificationDate')
            ->set('activation_date', ':activationDate')
            ->set('title', ':title')
            ->set('status', ':status')
            ->set('minutes', ':minutes')
            ->set('time_type', ':timeType')
            ->set('public', ':public')
            ->set('description', ':description')
            ->where('item_id = :itemId')
            ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
            ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('title', $item->getTitle())
            ->setParameter('status', $item->getInternalStatus())
            ->setParameter('minutes', $item->getPlannedTime())
            ->setParameter('timeType', $item->getTimeType())
            ->setParameter('public', $item->isPublic() ? 1 : 0)
            ->setParameter('description', $item->getDescription())
            ->setParameter('itemId', $item->getItemID());

        if ($item->getDate()) {
            $queryBuilder
                ->set('date', ':date')
                ->setParameter('date', $item->getDate());
        }

        try {
            $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

  /**
   * create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'todo' in the database and sets the todo items item id.
   * it then calls the private method _newNews to store the todo item itself.
   *
   * @param cs_todo_item $item
   */
  function _create (cs_todo_item $item)
  {
      $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

      $queryBuilder
          ->insert($this->addDatabasePrefix('items'))
          ->setValue('context_id', ':contextId')
          ->setValue('modification_date', ':modificationDate')
          ->setValue('activation_date', ':activationDate')
          ->setValue('type', ':type')
          ->setValue('draft', ':draft')
          ->setParameter('contextId', $item->getContextID())
          ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
          ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
          ->setParameter('type', 'todo')
          ->setParameter('draft', $item->isDraft());

      try {
          $queryBuilder->executeStatement();

          $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
          $item->setItemID($this->getCreateID());
          $this->_newNews($item);

      } catch (\Doctrine\DBAL\Exception $e) {
          include_once('functions/error_functions.php');
          trigger_error($e->getMessage(), E_USER_WARNING);
          $this->_create_id = null;
      }
  }

    /** store a new todo item to the database - internal, do not use -> use method save
     * this method stores a newly created todo item to the database
     *
     * @param cs_todo_item the todo item to be stored
     */
    function _newNews(cs_todo_item $item)
    {
        $currentDateTime = getCurrentDateTimeInMySQL();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('todos'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('activation_date', ':activationDate')
            ->setValue('title', ':title')
            ->setValue('date', ':date')
            ->setValue('minutes', ':minutes')
            ->setValue('time_type', ':timeType')
            ->setValue('public', ':public')
            ->setValue('description', ':description')
            ->setParameter('itemId', $item->getItemID())
            ->setParameter('contextId', $item->getContextID())
            ->setParameter('creatorId', $item->getCreatorItem()->getItemID())
            ->setParameter('creationDate', $currentDateTime)
            ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
            ->setParameter('modificationDate', $currentDateTime)
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('title', $item->getTitle())
            ->setParameter('date', empty($item->getDate()) ? null : $item->getDate())
            ->setParameter('minutes', $item->getPlannedTime())
            ->setParameter('timeType', $item->getTimeType())
            ->setParameter('public', $item->isPublic() ? 1 : 0)
            ->setParameter('description', $item->getDescription());

        $status = $item->getInternalStatus();
        if ($status) {
            $queryBuilder
                ->setValue('status', ':status')
                ->setParameter('status', $status);
        }

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

  /**  delete a todo item
   *
   * @param cs_todo_item the todo item to be deleted
   *
   * @access public
   */
   function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID() ?: 0;
      $query = 'UPDATE '.$this->addDatabasePrefix('todos').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         unset($link_manager);
         parent::delete($item_id);
      }
      unset($current_user);
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("todos").".item_id) as number FROM ".$this->addDatabasePrefix("todos")." WHERE ".$this->addDatabasePrefix("todos").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ((".$this->addDatabasePrefix("todos").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("todos").".creation_date < '".encode(AS_DB,$end)."') or (".$this->addDatabasePrefix("todos").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("todos").".modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("todos").".item_id) as number FROM ".$this->addDatabasePrefix("todos")." WHERE ".$this->addDatabasePrefix("todos").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("todos").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("todos").".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting todos from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountModTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("todos").".item_id) as number FROM ".$this->addDatabasePrefix("todos")." WHERE ".$this->addDatabasePrefix("todos").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("todos").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("todos").".modification_date < '".encode(AS_DB,$end)."' and ".$this->addDatabasePrefix("todos").".modification_date != ".$this->addDatabasePrefix("todos").".creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting todos from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

    function deleteTodosOfUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('todos').'.* FROM ' . $this->addDatabasePrefix('todos').' WHERE ' . $this->addDatabasePrefix('todos') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('todos') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'FLAG') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === 'FALSE') {
                        $updateQuery .= ' title = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '",';
                        $updateQuery .= ' public = "1"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB,$rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        include_once('functions/error_functions.php');trigger_error('Problems automatic deleting todos from query: "' . $updateQuery . '"', E_USER_WARNING);
                    }
                }
            }
        }
    }

    /**
     * @param int[] $contextIds List of context ids
     * @param array Limits for buzzwords / categories
     * @param int $size Number of items to get
     * @param \DateTime $newerThen The oldest modification date to consider
     * @param int[] $excludedIds Ids to exclude
     *
     * @return \cs_list
     */
    public function getNewestItems($contextIds, $limits, $size, \DateTime $newerThen = null, $excludedIds = [])
    {
        parent::setGenericNewestItemsLimits($contextIds, $limits, $newerThen, $excludedIds);

        if ($size > 0) {
            $this->setIntervalLimit(0, $size);
        }

        $this->setSortOrder('date');

        $this->select();
        return $this->get();
    }
}