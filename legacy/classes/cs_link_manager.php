<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for database connection to the database table "links"
 * this class implements a database manager for the table "links". Links between commsy items.
 */
class cs_link_manager extends cs_manager
{
    /**
     * integer - containing the error number if an error occured.
     */
    public $_dberrno;

    /**
     * string - containing the error text if an error occured.
     */
    public $_dberror;

    /**
     * integer - containing the item id, if an item was created.
     */
    public $_create_id;

    /**
     * array - containing the data from the database -> cache data.
     */
    public $_data = [];

    public $_cache = [];

    /**
     * string - containing the order limit for the select statement.
     */
    public $_order;

    public $_discussion_type_limit;
    /**
     * limits for selecting link items.
     */
    public $_linked_item = null;
    public $_second_linked_item = null;
    public $_link_type_limit = null;
    public $_link_type_array_limit = null;

    public $_entry_limit = null;
    public $_sorting_place_limit = null;

    /** constructor: cs_links_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'link_items';
    }

    /******************* reset methods ************/

     /** reset limits
      * reset limits of this class: context limit, delete limit.
      */
     public function resetLimits()
     {
         $this->_order = null;
         $this->_room_limit = null;
         $this->_linked_item = null;
         $this->_second_linked_item = null;
         // $this->_version_id_limit = NULL;
         $this->_link_type_limit = null;
         $this->_link_type_array_limit = [];
         $this->_sorting_place_limit = null;
         $this->_entry_limit = null;
     }

     /** reset data
      * reset data of this class.
      */
     public function resetData()
     {
         $this->_data = [];
     }

     /** reset cache
      * reset cache of this class.
      */
     private function _resetCache()
     {
         $this->_cache = [];
     }

     public function setEntryLimit($count)
     {
         $this->_entry_limit = $count;
     }

     /** reset type_limit
      * reset type_limit of this class.
      */
     public function resetTypeLimit()
     {
         $this->_link_type_limit = null;
         $this->_link_type_array_limit = [];
     }

     /** reset order
      * reset order of this class.
      */
     public function resetOrder()
     {
         unset($this->_order);
     }

    /************** set methods ******************/

     /** sets the type limit.
      *
      * @param string
      */
     public function setTypeLimit($type)
     {
         $this->_link_type_limit = $type;
     }

     /** sets the type limit.
      *
      * @param string
      */
     public function setTypeArrayLimit($type)
     {
         $this->_link_type_array_limit = $type;
     }

     /** sets the rubric type limit.
      */
     public function setMaterialLimit()
     {
         $this->setTypeLimit(CS_MATERIAL_TYPE);
     }

     public function setTopicLimit()
     {
         $this->setTypeLimit(CS_TOPIC_TYPE);
     }

     public function setRoomLimit($limit)
     {
         $this->_room_limit = $limit;
     }

     public function sortbySortingPlace()
     {
         $this->_setOrderLimit('sorting_place');
     }

     public function setSortingPlaceLimit()
     {
         $this->_sorting_place_limit = true;
     }

     public function _setOrderLimit($value)
     {
         $this->_order = $value;
     }

     /** set linked_item
      * this method sets a linked-item as a limit.
      *
      * @param object of a linked-item
      */
     public function setLinkedItemLimit($object)
     {
         $this->_linked_item = $object;
     }

     /** set linked_item
      * this method sets a linked-item as a limit.
      *
      * @param object of a linked-item
      */
     public function setSecondLinkedItemLimit($object)
     {
         $this->_second_linked_item = $object;
     }

     /** build a new links item
      * this method returns a new EMTPY user item.
      *
      * @return object cs_item a new EMPTY user
      */
     public function getNewItem()
     {
         return new cs_link_item($this->_environment);
     }

     /** get all links
      * this method get all links.
      *
      * @param string  type       type of the link
      * @param string  mode       one of count, select, select_with_item_type_from
      */
     public function _performQuery($mode = 'select', $with_linked_items = true)
     {
         $result = null;
         $data = [];
         if ('count' == $mode) {
             $query = 'SELECT count( DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id) AS count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id';
         } else {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.*';
         }
         $query .= ' FROM '.$this->addDatabasePrefix('link_items').' ';

         $query .= ' WHERE 1';

         if (isset($this->_linked_item) or isset($this->_link_type_limit)) {
             $query .= ' AND ((';
         }
         if (isset($this->_linked_item)) {
             $query .= ' first_item_id ="'.encode(AS_DB, $this->_linked_item->getItemID()).'"';
         }
         if (isset($this->_second_linked_item)) {
             $query .= ' AND second_item_id ="'.encode(AS_DB, $this->_second_linked_item->getItemID()).'"';
         }
         if (!empty($this->_link_type_limit)) {
             if (!empty($this->_linked_item) or !empty($this->_second_linked_item)) {
                 $query .= ' AND';
             }
             $query .= ' second_item_type ="'.encode(AS_DB, $this->_link_type_limit).'"';
         } elseif (!empty($this->_link_type_array_limit)) {
             if (!empty($this->_linked_item) or !empty($this->_second_linked_item)) {
                 $query .= ' AND';
             }
             $query .= ' (';
             $first = true;
             foreach ($this->_link_type_array_limit as $limit) {
                 if ($first) {
                     $first = false;
                     $query .= ' second_item_type ="'.encode(AS_DB, $limit).'"';
                 } else {
                     $query .= ' OR second_item_type ="'.encode(AS_DB, $limit).'"';
                 }
             }
             $query .= ')';
         }
         if (isset($this->_linked_item) or isset($this->_link_type_limit)) {
             $query .= ')';
             $query .= ' OR (';
         }
         if (isset($this->_linked_item)) {
             $query .= ' second_item_id ="'.encode(AS_DB, $this->_linked_item->getItemID()).'"';
         }
         if (isset($this->_second_linked_item)) {
             $query .= ' AND first_item_id ="'.encode(AS_DB, $this->_second_linked_item->getItemID()).'"';
         }
         if (!empty($this->_link_type_limit)) {
             if (!empty($this->_linked_item) or !empty($this->_second_linked_item)) {
                 $query .= ' AND';
             }
             $query .= ' first_item_type ="'.encode(AS_DB, $this->_link_type_limit).'"';
         } if (!empty($this->_link_type_array_limit)) {
             if (!empty($this->_linked_item) or !empty($this->_second_linked_item)) {
                 $query .= ' AND';
             }
             $query .= ' (';
             $first = true;
             foreach ($this->_link_type_array_limit as $limit) {
                 if ($first) {
                     $first = false;
                     $query .= ' first_item_type ="'.encode(AS_DB, $limit).'"';
                 } else {
                     $query .= ' OR first_item_type ="'.encode(AS_DB, $limit).'"';
                 }
             }
             $query .= ' )';
         }
         if (isset($this->_linked_item) or isset($this->_link_type_limit)) {
             $query .= '))';
         }
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('link_items').'.deletion_date IS NULL';
         if (isset($this->_room_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         }
//       else {
//         $query .= ' AND link_items.context_id = "'.$this->_environment->getCurrentContextID().'"';
//      }

         if (isset($this->_sorting_place_limit)
              and !empty($this->_sorting_place_limit)
              and $this->_sorting_place_limit
         ) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.sorting_place IS NOT NULL';
         }

         // group to eliminate versions
         // there are no version_ids in this table ???????????
         // if ( isset($this->_linked_item) ) {
         //   $query .= ' GROUP BY link_items.item_id';
         // }

         // order
         if (!empty($this->_order)) {
             if ('sorting_place' == $this->_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.sorting_place ASC, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
             }
         } else {
             $query .= ' ORDER BY '.$this->addDatabasePrefix('link_items').'.creation_date DESC';
         }
         if (isset($this->_entry_limit)) {
             $query .= ' LIMIT 0, '.encode(AS_DB, $this->_entry_limit);
         }

         $cache_exists = false;
         if (!empty($this->_cache)) {
             foreach ($this->_cache as $cache_query) {
                 if ($cache_query['query'] == $query) {
                     $cache_exists = true;
                     $result = $cache_query['result'];
                 }
             }
         }

         if (!$cache_exists) {
             // perform query
             $r = $this->_db_connector->performQuery($query);
             if (!isset($r)) {
                 trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
             } else {
                 $result = $r;
                 if ($this->_cache_on) {
                     $temp = [];
                     $temp['query'] = $query;
                     $temp['result'] = $r;
                     $this->_cache[] = $temp;
                 }
             }
         }

         return $result;
     }

     /** get all links
      * this method get all links.
      *
      * @param string  type       type of the link
      * @param string  mode       one of count, select, select_with_item_type_from
      */
     public function _performQuery2($mode = 'select', $with_linked_items = true)
     {
         $result = null;
         $data = [];
         if ('count' == $mode) {
             $query = 'SELECT count( DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id) AS count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id';
         } else {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.*';
         }
         $query .= ' FROM '.$this->addDatabasePrefix('link_items').' ';

         $query .= ' WHERE 1';

         if (isset($this->_id_array_limit) or isset($this->_link_type_limit)) { // id-Array // user
             $query .= ' AND ((';
         }
         if (isset($this->_id_array_limit) and !empty($this->_id_array_limit)) { // id-Array
             $query .= ' first_item_id IN ('.implode(',', $this->_id_array_limit).')';
         }
         if (isset($this->_second_linked_item)) {
             $query .= ' AND second_item_id ="'.encode(AS_DB, $this->_second_linked_item->getItemID()).'"';
         }
         if (!empty($this->_link_type_limit)) { // user
             if (!empty($this->_id_array_limit) or !empty($this->_second_linked_item)) { // id-Array
                 $query .= ' AND';
             }
             $query .= ' second_item_type ="'.encode(AS_DB, $this->_link_type_limit).'"'; // user
         } elseif (!empty($this->_link_type_array_limit)) {
             if (!empty($this->_id_array_limit) or !empty($this->_second_linked_item)) { // id-Array
                 $query .= ' AND';
             }
             $query .= ' (';
             $first = true;
             foreach ($this->_link_type_array_limit as $limit) {
                 if ($first) {
                     $first = false;
                     $query .= ' second_item_type ="'.encode(AS_DB, $limit).'"';
                 } else {
                     $query .= ' OR second_item_type ="'.encode(AS_DB, $limit).'"';
                 }
             }
             $query .= ')';
         }
         if (isset($this->_id_array_limit) or isset($this->_link_type_limit)) {  // id-Array // user
             $query .= ')';
             $query .= ' OR (';
         }
         if (isset($this->_id_array_limit) and !empty($this->_id_array_limit)) { // id-Array
             $query .= ' second_item_id IN ('.implode(',', $this->_id_array_limit).')'; // id-Array
         }
         if (isset($this->_second_linked_item)) {
             $query .= ' AND first_item_id ="'.encode(AS_DB, $this->_second_linked_item->getItemID()).'"';
         }
         if (!empty($this->_link_type_limit)) { // user
             if (!empty($this->_id_array_limit) or !empty($this->_second_linked_item)) { // id-Array
                 $query .= ' AND';
             }
             $query .= ' first_item_type ="'.encode(AS_DB, $this->_link_type_limit).'"'; // user
         } if (!empty($this->_link_type_array_limit)) {
             if (!empty($this->_id_array_limit) or !empty($this->_second_linked_item)) { // id-Array
                 $query .= ' AND';
             }
             $query .= ' (';
             $first = true;
             foreach ($this->_link_type_array_limit as $limit) {
                 if ($first) {
                     $first = false;
                     $query .= ' first_item_type ="'.encode(AS_DB, $limit).'"';
                 } else {
                     $query .= ' OR first_item_type ="'.encode(AS_DB, $limit).'"';
                 }
             }
             $query .= ' )';
         }
         if (isset($this->_id_array_limit) or isset($this->_link_type_limit)) { // id-Array // user
             $query .= '))';
         }
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('link_items').'.deletion_date IS NULL';
         if (isset($this->_room_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         }
//       else {
//         $query .= ' AND link_items.context_id = "'.$this->_environment->getCurrentContextID().'"';
//      }

         if (isset($this->_sorting_place_limit)
              and !empty($this->_sorting_place_limit)
              and $this->_sorting_place_limit
         ) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.sorting_place IS NOT NULL';
         }

         // group to eliminate versions
         // there are no version_ids in this table ???????????
         // if ( isset($this->_linked_item) ) {
         //   $query .= ' GROUP BY link_items.item_id';
         // }

         // order
         if (!empty($this->_order)) {
             if ('sorting_place' == $this->_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.sorting_place ASC, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
             }
         } else {
             $query .= ' ORDER BY '.$this->addDatabasePrefix('link_items').'.creation_date DESC';
         }
         if (isset($this->_entry_limit)) {
             $query .= ' LIMIT 0, '.encode(AS_DB, $this->_entry_limit);
         }

         $cache_exists = false;
         if (!empty($this->_cache)) {
             foreach ($this->_cache as $cache_query) {
                 if ($cache_query['query'] == $query) {
                     $cache_exists = true;
                     $result = $cache_query['result'];
                 }
             }
         }

         if (!$cache_exists) {
             // perform query
             $r = $this->_db_connector->performQuery($query);
             if (!isset($r)) {
                 trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
             } else {
                 $result = $r;
                 if ($this->_cache_on) {
                     $temp = [];
                     $temp['query'] = $query;
                     $temp['result'] = $r;
                     $this->_cache[] = $temp;
                 }
             }
         }

         return $result;
     }

     /** select items limited by limits
      * this method returns a list (cs_list) of items within the database limited by the limits.
      * depends on _performQuery(), which must be overwritten.
      */
     public function select($with_linked_items = true)
     {
         if (isset($this->_linked_item)) {
             $result = $this->_performQuery('select', $with_linked_items);
             $this->_data = new cs_list();
             $this->_id_array = null;
             if (!$with_linked_items) {
                 foreach ($result as $query_result) {
                     $item = $this->_buildItem($query_result);
                     $this->_data->add($item);
                 }
             } else {
                 $link_list = new cs_list();
                 $item_id_array = [];
                 foreach ($result as $query_result) {
                     if ($this->_linked_item->getItemID() == $query_result['first_item_id']) {
                         $item_id_array[] = $query_result['second_item_id'];
                     } else {
                         $item_id_array[] = $query_result['first_item_id'];
                     }
                     $item = $this->_buildItem($query_result);
                     $link_list->add($item);
                 }
                 $this->_data = $link_list;
             }
             if (isset($this->_order)
                  and !empty($this->_order)
                  and 'sorting_place' == $this->_order
             ) {
                 $item = $this->_data->getFirst();
                 $link_list1 = new cs_list();
                 $link_list2 = new cs_list();
                 while ($item) {
                     if ($item->getSortingPlace()) {
                         $link_list1->add($item);
                     } else {
                         $link_list2->add($item);
                     }
                     $item = $this->_data->getNext();
                 }
                 $link_list1->addList($link_list2);
                 $this->_data = $link_list1;
                 unset($link_list1);
                 unset($link_list2);
             }
         } else {
             parent::select();
         }
     }

     /** select items limited by limits
      * this method returns a list (cs_list) of items within the database limited by the limits.
      * depends on _performQuery(), which must be overwritten.
      */
     public function select2($with_linked_items = true)
     {
         if (isset($this->_id_array_limit)) {
             $result = $this->_performQuery2('select', $with_linked_items);
             $this->_data = new cs_list();
             $this->_id_array = null;
             if (!$with_linked_items) {
                 foreach ($result as $query_result) {
                     $item = $this->_buildItem($query_result);
                     $this->_data->add($item);
                 }
             } else {
                 $link_list = new cs_list();
                 $item_id_array = [];
                 foreach ($result as $query_result) {
                     if ($this->_linked_item->getItemID() == $query_result['first_item_id']) {
                         $item_id_array[] = $query_result['second_item_id'];
                     } else {
                         $item_id_array[] = $query_result['first_item_id'];
                     }
                     $item = $this->_buildItem($query_result);
                     $link_list->add($item);
                 }
                 $this->_data = $link_list;
             }
             if (isset($this->_order)
                  and !empty($this->_order)
                  and 'sorting_place' == $this->_order
             ) {
                 $item = $this->_data->getFirst();
                 $link_list1 = new cs_list();
                 $link_list2 = new cs_list();
                 while ($item) {
                     if ($item->getSortingPlace()) {
                         $link_list1->add($item);
                     } else {
                         $link_list2->add($item);
                     }
                     $item = $this->_data->getNext();
                 }
                 $link_list1->addList($link_list2);
                 $this->_data = $link_list1;
                 unset($link_list1);
                 unset($link_list2);
             }
         } else {
             parent::select();
         }
     }

      public function _selectForExport()
      {
          $result = $this->_performQuery('select', false);
          $this->_id_array = null;
          $data = new cs_list();

          $result = is_array($result) ? $result : [];

          foreach ($result as $query_result) {
              $item = $this->_buildItem($query_result);
              $data->add($item);
          }

          $this->_data = $data;
      }

    /** update a link item - internal, do not use -> use method save
     * this method updates a link item.
     *
     * @param object cs_item link_item
     */
    public function _update($link_item)   // wird nicht benötigt???
    {
        parent::_update($link_item);
        $first_item = $link_item->getFirstLinkedItem();
        $second_item = $link_item->getSecondLinkedItem();
        $modificator = $link_item->getModificatorItem();
        $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'first_item_id="'.encode(AS_DB, $first_item->getItemID()).'",'.
                 'second_item_id="'.encode(AS_DB, $second_item->getItemID()).'",'.
                 'first_item_type="'.encode(AS_DB, $link_item->getFirstLinkedItemType()).'",'.
                 'second_item_type="'.encode(AS_DB, $link_item->getSecondLinkedItemType()).'",';

        if ('' != $link_item->getSortingPlace()) {
            $query .= 'sorting_place="'.encode(AS_DB, $link_item->getSortingPlace()).'",';
        }

        $query .= 'sorting_place="'.encode(AS_DB, $link_item->getSortingPlace()).'",'.
                  'extras="'.encode(AS_DB, serialize($link_item->getExtraInformation())).'"'.
                  ' WHERE item_id="'.encode(AS_DB, $link_item->getItemID()).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating link item from query: "'.$query.'"', E_USER_WARNING);
        }
    }

    public function getItemList(array $id_array)
    {
        return $this->_getItemList('link_items', $id_array);
    }

    /** create a link item - internal, do not use -> use method save
     * this method creates a link item.
     *
     * @param object cs_item link_item
     */
    public function _create($link_item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB, $link_item->getContextID()).'",'.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'type="link_item"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating link item from query: "'.$query.'"', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $link_item->setItemID($this->getCreateID());

            $creator = $link_item->getCreatorItem();
            $creator_id = $creator->getItemID();
            $current_datetime = getCurrentDateTimeInMySQL();
            $query = 'INSERT INTO '.$this->addDatabasePrefix('link_items').' SET '.
                      'item_id="'.encode(AS_DB, $link_item->getItemID()).'",';
            $query .= 'context_id="'.encode(AS_DB, $link_item->getContextID()).'",';
            $first_item = $link_item->getFirstLinkedItem();
            if (isset($first_item)) {
                $first_item_id = $first_item->getItemID();
                $first_item_type = $first_item->getItemType();
            } else {
                $first_item_id = $link_item->getFristLinkedItemID();
                $first_item_type = $link_item->getFirstLinkedItemID();
            }
            $second_item = $link_item->getSecondLinkedItem();
            if (isset($second_item)) {
                $second_item_id = $second_item->getItemID();
                $second_item_type = $second_item->getItemType();
            } else {
                $second_item_id = $link_item->getSecondLinkedItemID();
                $second_item_type = $link_item->getSecondLinkedItemID();
            }

            if (isset($creator_id) && !empty($creator_id)) {
                $query .= 'creator_id="'.encode(AS_DB, $creator_id).'",';
            }

            $query .= 'creation_date="'.$current_datetime.'",'.
                      'modification_date="'.$current_datetime.'",'.
                      'first_item_id="'.encode(AS_DB, $first_item_id).'",'.
                      'second_item_id="'.encode(AS_DB, $second_item_id).'",'.
                      'first_item_type="'.encode(AS_DB, $first_item_type).'",'.
                      'second_item_type="'.encode(AS_DB, $second_item_type).'",';

            if ('' != $link_item->getSortingPlace()) {
                $query .= 'sorting_place="'.encode(AS_DB, $link_item->getSortingPlace()).'",';
            }

            $query .= 'extras="'.encode(AS_DB, serialize($link_item->getExtraInformation())).'"';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems creating link item from query: "'.$query.'"', E_USER_WARNING);
                $query = 'DELETE FROM '.$this->addDatabasePrefix('items').' WHERE item_id="'.$this->getCreateID().'"';
                $result = $this->_db_connector->performQuery($query);
                $this->_create_id = null;
            }
            unset($creator);
            unset($first_item);
            unset($second_item);
        }
    }

      /** delete a link_item.
       *
       * @param int item_id the link_item
       */
      public function delete(int $itemId): void
      {
          $current_datetime = getCurrentDateTimeInMySQL();
          $current_user = $this->_environment->getCurrentUserItem();
          $user_id = $current_user->getItemID() ?: 0;
          $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB, $user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) or !$result) {
              trigger_error('Problems deleting link_items from query: "'.$query.'"', E_USER_WARNING);
          } else {
              // delete item from table 'items'
              parent::delete($itemId);
          }

          // reset cache
          $this->_resetCache();
      }

      public function deleteAllLinkItemsInCommunityRoom($item_id, $context_id)
      {
          $current_user = $this->_environment->getCurrentUserItem();
          $user_id = $current_user->getItemID() ?: 0;
          $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB, $user_id).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB, $item_id).'"';
          $query .= ' OR second_item_id="'.encode(AS_DB, $item_id).'"';
          $query .= ')';
          $query .= ' AND context_id ="'.encode(AS_DB, $context_id).'"';
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) or !$result) {
              trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"', E_USER_WARNING);
          }

          // reset cache
          $this->_resetCache();
      }

     public function getCountExistingLinkItemsOfUser($user_id)
     {
         $query = 'SELECT count('.$this->addDatabasePrefix('link_items').'.item_id) AS count';
         $query .= ' FROM '.$this->addDatabasePrefix('link_items');
         $query .= ' WHERE 1';

         if (isset($this->_room_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         } else {
             $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
         }
         $query .= ' AND NOT('.$this->addDatabasePrefix('link_items').'.first_item_type="user" AND '.$this->addDatabasePrefix('link_items').'.second_item_type="group")';
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.creator_id ="'.encode(AS_DB, $user_id).'"';

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or empty($result[0]['count'])) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             return $result[0]['count'];
         }
     }

      /** delete link , but it is just an update
       * this method deletes all links from an item, but only as an update to restore it later and for evaluation.
       *
       * @param int item_id       id of the item
       * @param int version_id    version id of the item
       */
      public function deleteLinksBecauseItemIsDeleted($item_id)
      {
          $user_id = $this->_current_user->getItemID() ?: 0;
          $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB, $user_id).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB, $item_id).'"';
          $query .= ') OR (second_item_id="'.encode(AS_DB, $item_id).'"';
          $query .= ')';
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) or !$result) {
              trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"', E_USER_WARNING);
          }

          // delete in items table too
          $query = '
            UPDATE '.
                  $this->addDatabasePrefix('link_items').'
            SET
               deletion_date = "'.getCurrentDateTimeInMySQL().'",
               deleter_id = "'.encode(AS_DB, $user_id).'"
            WHERE
               item_id = "'.encode(AS_DB, $item_id).'"
        ';
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) || !$result) {
              trigger_error('Problems deleting(updating) an item from query: "'.$query.'"', E_USER_WARNING);
          }

          // reset cache
          $this->_resetCache();
      }

      public function undeleteLinks($item)
      {
          $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'deletion_date=NULL,'.
              'deleter_id=NULL'.
              ' WHERE deletion_date>="'.encode(AS_DB, $item->getDeletionDate()).'"'.
              ' AND (first_item_id="'.encode(AS_DB, $item->getItemID()).'"'.
              ' OR second_item_id="'.encode(AS_DB, $item->getItemID()).'")';
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) or !$result) {
              trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"', E_USER_WARNING);
          }

          // reset cache
          $this->_resetCache();
      }

    public function getModiefiedItemIDArray($type, $creator_id)
    {
        $query = '';
        switch ($type) {
            case CS_MATERIAL_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.item_id FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('materials').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('materials').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC';
                break;
            case CS_PROJECT_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('room').'.item_id FROM '.$this->addDatabasePrefix('room').' WHERE '.$this->addDatabasePrefix('room').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('room').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('room').'.type="project" ORDER BY '.$this->addDatabasePrefix('room').'.modification_date DESC, '.$this->addDatabasePrefix('room').'.title ASC';
                break;
            case CS_ANNOUNCEMENT_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('announcement').'.item_id FROM '.$this->addDatabasePrefix('announcement').' WHERE '.$this->addDatabasePrefix('announcement').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('announcement').'.deleter_id IS NULL  AND '.$this->addDatabasePrefix('announcement').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('announcement').'.modification_date DESC';
                break;
            case CS_DISCUSSION_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('discussions').'.item_id FROM '.$this->addDatabasePrefix('discussions').' WHERE '.$this->addDatabasePrefix('discussions').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('discussions').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('discussions').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('discussions').'.modification_date DESC, '.$this->addDatabasePrefix('discussions').'.title DESC';
                break;
            case CS_TODO_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('todos').'.item_id FROM '.$this->addDatabasePrefix('todos').' WHERE '.$this->addDatabasePrefix('todos').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('todos').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('todos').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('todos').'.modification_date DESC';
                break;
            case CS_DATE_TYPE:
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('dates').'.item_id FROM '.$this->addDatabasePrefix('dates').' WHERE '.$this->addDatabasePrefix('dates').'.creator_id ="'.encode(AS_DB, $creator_id).'" AND '.$this->addDatabasePrefix('dates').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('dates').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
                break;
        }
        // perform query

        $result = $this->_db_connector->performQuery($query);
        $id_array = [];
        if (isset($result)) {
            foreach ($result as $query_result) {
                $id_array[] = $query_result['item_id'];
            }
        }
        if ('CS_DISCUSSION_TYPE' == $type) {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('discussionarticless').'.item_id FROM '.$this->addDatabasePrefix('discussionarticles').' WHERE '.$this->addDatabasePrefix('discussionarticles').'.creator_id ="'.encode(AS_DB, $creator_id).'" OR '.$this->addDatabasePrefix('discussionarticles').'.deleter_id IS NULL  ORDER BY '.$this->addDatabasePrefix('discussionarticle').'.modification_date DESC, '.$this->addDatabasePrefix('discussionarticle').'.subject DESC';
            $result = $this->_db_connector->performQuery($query);
            $id_array = [];
            if (isset($result)) {
                foreach ($result as $query_result) {
                    $id_array[] = $query_result['item_id'];
                }
            }
        }

        if (!isset($id_array[0])) {
            return [];
        } else {
            return $id_array;
        }
    }

    public function moveRoom($roomMover)
    {
        $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET ';
        $query .= " WHERE context_id = '".encode(AS_DB, $roomMover->getRoomId())."'";

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems creating links from query: "'.$query.'"', E_USER_WARNING);
        }
    }

     public function mergeAccounts($new_id, $old_id)
     {
         parent::mergeAccounts($new_id, $old_id);

         $query = 'SELECT * FROM '.$this->addDatabasePrefix('link_items').' WHERE creator_id = "'.encode(AS_DB, $new_id).'" AND first_item_id ="'.encode(AS_DB, $old_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if (isset($result)) {
             foreach ($result as $row) {
                 $update = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET ';
                 $update .= ' first_item_id = '.encode(AS_DB, $new_id);
                 $update .= ' WHERE item_id = '.$row['item_id'];

                 $result2 = $this->_db_connector->performQuery($update);
                 if (!isset($result2) or !$result2) {
                     trigger_error('Problems creating link_items: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
                 }
             }
         }

         $query = 'SELECT * FROM '.$this->addDatabasePrefix('link_items').' WHERE creator_id = "'.encode(AS_DB, $new_id).'" AND second_item_id ="'.encode(AS_DB, $old_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if (isset($result)) {
             foreach ($result as $row) {
                 $update = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET ';
                 $update .= ' second_item_id = '.encode(AS_DB, $new_id);
                 $update .= ' WHERE item_id = '.$row['item_id'];

                 $result2 = $this->_db_connector->performQuery($update);
                 if (!isset($result2) or !$result2) {
                     trigger_error('Problems creating link_items from query: "'.$query.'"', E_USER_WARNING);
                 }
             }
         }
     }

     public function cleanSortingPlaces($linked_item)
     {
         $item_id = $linked_item->getItemID();
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=NULL WHERE first_item_id="'.encode(AS_DB, $item_id).'" OR second_item_id="'.encode(AS_DB, $item_id).'";';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems cleaning sorting place at table '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
         }
     }

     public function saveSortingPlaces($value_array)
     {
         if (isset($value_array)
              and !empty($value_array)
              and is_array($value_array)
         ) {
             foreach ($value_array as $value) {
                 $item_id = $value['item_id'];
                 $place = $value['place'];

                 $query = '
               UPDATE '.
                       $this->addDatabasePrefix($this->_db_table).'
               SET
                  sorting_place="'.encode(AS_DB, $place).'",
                  modification_date = "'.getCurrentDateTimeinMySQL().'"
               WHERE
                  item_id="'.encode(AS_DB, $item_id).'";
            ';

                 // $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place="'.encode(AS_DB,$place).'" WHERE item_id="'.encode(AS_DB,$item_id).'";';
                 $result = $this->_db_connector->performQuery($query);
                 if (!isset($result) or !$result) {
                     trigger_error('Problems saveing sorting place at table '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
                 }
             }
         }
     }

     public function saveLinkItemsMaterialToItem($new_array, $item)
     {
         $type = CS_MATERIAL_TYPE;
         $this->setTypeLimit($type);
         $this->setLinkedItemLimit($item);
         $this->select(false);
         $result_list = $this->get();
         $insert_array = [];
         $nothing_array = [];
         $delete_array = [];
         if ($result_list->isNotEmpty()) {
             $link_item = $result_list->getFirst();
             while ($link_item) {
                 if ($link_item->getFirstLinkedItemType() == $type
                      and !in_array($link_item->getFirstLinkedItemID(), $new_array)
                 ) {
                     $delete_array[] = $link_item->getItemID();
                 } elseif ($link_item->getSecondLinkedItemType() == $type
                      and !in_array($link_item->getSecondeLinkedItemID(), $new_array)
                 ) {
                     $delete_array[] = $link_item->getItemID();
                 } else {
                     if ($link_item->getFirstLinkedItemType() == $type) {
                         $nothing_array[] = $link_item->getFirstLinkedItemID();
                     } else {
                         $nothing_array[] = $link_item->getSecondLinkedItemID();
                     }
                 }
                 $link_item = $result_list->getNext();
             }
         }
         unset($result_list);
         $insert_array = array_diff($new_array, $nothing_array);
         foreach ($delete_array as $item_id) {
             $this->delete($item_id);
         }
         foreach ($insert_array as $item_id) {
             $new_link_item = $this->getNewItem();
             $new_link_item->setFirstLinkedItemID($item_id);
             $new_link_item->setFirstLinkedItemType($type);
             $new_link_item->setSecondLinkedItemID($item->getItemID());
             $new_link_item->setSecondLinkedItemType($item->getType());
             $new_link_item->setContextID($this->_environment->getCurrentContextID());
             $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
             $new_link_item->save();
         }
     }

     public function saveLinkItemsRubricToItem($new_array, $item, $rubric)
     {
         $type = $rubric;
         $this->setTypeLimit($type);
         $this->setLinkedItemLimit($item);
         $this->select(false);
         $result_list = $this->get();
         $insert_array = [];
         $nothing_array = [];
         $delete_array = [];
         if ($result_list->isNotEmpty()) {
             $link_item = $result_list->getFirst();
             while ($link_item) {
                 if ($link_item->getFirstLinkedItemType() == $type
                      and !in_array($link_item->getFirstLinkedItemID(), $new_array)
                 ) {
                     $delete_array[] = $link_item->getItemID();
                 } elseif ($link_item->getSecondLinkedItemType() == $type
                      and !in_array($link_item->getSecondeLinkedItemID(), $new_array)
                 ) {
                     $delete_array[] = $link_item->getItemID();
                 } else {
                     if ($link_item->getFirstLinkedItemType() == $type) {
                         $nothing_array[] = $link_item->getFirstLinkedItemID();
                     } else {
                         $nothing_array[] = $link_item->getSecondLinkedItemID();
                     }
                 }
                 $link_item = $result_list->getNext();
             }
         }
         unset($result_list);
         $insert_array = array_diff($new_array, $nothing_array);
         foreach ($delete_array as $item_id) {
             $this->delete($item_id);
         }
         foreach ($insert_array as $item_id) {
             $new_link_item = $this->getNewItem();
             $new_link_item->setFirstLinkedItemID($item_id);
             $new_link_item->setFirstLinkedItemType($type);
             $new_link_item->setSecondLinkedItemID($item->getItemID());
             $new_link_item->setSecondLinkedItemType($item->getType());
             $new_link_item->setContextID($this->_environment->getCurrentContextID());
             $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
             $new_link_item->save();
         }
     }

    /** save a commsy item
     * this method saves a commsy item.
     *
     * @param cs_item
     */
    public function saveItem($item)
    {
        $item_id = $item->getItemID();

        $modifier = $item->getModificatorItem();
        if (!isset($modifier)) {
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

        // Add modifier to all users who ever edited this section
        if ($this->_update_with_changing_modification_information) {
            $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
            $link_modifier_item_manager->markEdited($item->getItemID());
        }

        // reset cache
        $this->_resetCache();
    }

     /** get a link item.
      *
      * @param int item_id id of the item
      *
      * @return object cs_link_item a link item
      */
     public function getItem(?int $item_id)
     {
         $item = null;
         if (!empty($item_id)
              and !empty($this->_cache_object[$item_id])
         ) {
             return $this->_cache_object[$item_id];
         } elseif (array_key_exists($item_id, $this->_cached_items)) {
             $item = $this->_buildItem($this->_cached_items[$item_id]);
         } elseif (!empty($item_id)) {
             $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id = "'.encode(AS_DB, $item_id).'";';
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result) or empty($result[0])) {
                 trigger_error('Problems selecting one '.$this->_db_table.' item ('.$item_id.').', E_USER_WARNING);
             } else {
                 $item = $this->_buildItem($result[0]);
                 if ($this->_cache_on) {
                     $this->_cached_items[$result[0]['item_id']] = $result[0];
                 }
             }
         }

         return $item;
     }

     /** Prepares the db_array for the item.
      *
      * @param $db_array Contains the data from the database
      *
      * @return array Contains prepared data ( textfunctions applied etc. )
      */
     public function _buildItem($db_array)
     {
         if (!empty($db_array['extras'])) {
             $db_array['extras'] = mb_unserialize($db_array['extras']);
         }

         return parent::_buildItem($db_array);
     }

      public function getItemByFirstAndSecondID($first_id, $second_id, $deletion_date = false)
      {
          $item = null;
          if (!empty($first_id)
               and !empty($second_id)
          ) {
              $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE';
              if ($deletion_date) {
                  $query .= ' deletion_date IS NULL AND deleter_id IS NULL AND';
              }
              $query .= ' ('.$this->addDatabasePrefix($this->_db_table).'.first_item_id = "'.encode(AS_DB, $first_id).'"';
              $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.second_item_id = "'.encode(AS_DB, $second_id).'")';
              $query .= ' OR ('.$this->addDatabasePrefix($this->_db_table).'.first_item_id = "'.encode(AS_DB, $second_id).'"';
              $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.second_item_id = "'.encode(AS_DB, $first_id).'")';

              $query .= ';';
              $result = $this->_db_connector->performQuery($query);
              if (!isset($result)) {
                  trigger_error('Problems selecting one '.$this->_db_table.' item ('.$first_id.','.$second_id.').', E_USER_WARNING);
              } elseif (!empty($result[0])) {
                  $item = $this->_buildItem($result[0]);
                  if ($this->_cache_on) {
                      $this->_cached_items[$result[0]['item_id']] = $result[0];
                  }
              }
          }

          return $item;
      }

     public function getALlLinksByTagIDArray($contextId, $idArray)
     {
         if (!empty($idArray)
         ) {
             $inString = "'".implode("', '", $idArray)."'";

             $query = '
   			SELECT
   				first_item_id,
   				first_item_type,
   				second_item_id,
   				second_item_type
   			FROM
   				'.$this->addDatabasePrefix($this->_db_table).'
   			WHERE
   				'.$this->addDatabasePrefix($this->_db_table).'.context_id = '.encode(AS_DB, $contextId).' AND
   				'.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL AND
   				(
   					'.$this->addDatabasePrefix($this->_db_table).'.first_item_id IN ('.$inString.') OR
   					'.$this->addDatabasePrefix($this->_db_table).'.second_item_id IN ('.$inString.')
   				);
		';
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result)) {
                 trigger_error('Problems selecting items', E_USER_WARNING);
             } else {
                 return $result;
             }
         }
     }
}
