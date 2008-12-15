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

/** cs_dates_item is needed to create dates items
 */
include_once('functions/text_functions.php');
include_once('functions/date_functions.php');


/** class for database connection to the database table "dates"
 * this class implements a database manager for the table "dates"
 */
class cs_dates_manager extends cs_manager {

   /**
   * integer - containing the age of dates as a limit
   */
   var $_age_limit = NULL;

   var $_future_limit = FALSE;

   /**
   * integer - containing a start point for the select dates
   */
   var $_from_limit = NULL;

   /**
   * integer - containing how many dates the select statement should get
   */
   var $_interval_limit = NULL;

   var $_group_limit = NULL;
   var $_topic_limit = NULL;
   var $_sort_order = NULL;

   var $_month_limit = NULL;
   var $_month_limit2 = NULL;
   var $_day_limit = NULL;
   var $_year_limit = NULL;
   var $_date_mode_limit = 1;

   /** constructor
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    */
   function cs_dates_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'dates';
   }

    /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_age_limit = NULL;
      $this->_future_limit = FALSE;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_order = NULL;
      $this->_group_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_sort_order = NULL;
      $this->_month_limit = NULL;
      $this->_month_limit2 = NULL;
      $this->_day_limit = NULL;
      $this->_year_limit = NULL;
      $this->_date_mode_limit = 1;
   }

   /** set age limit
    * this method sets an age limit for dates
    *
    * @param integer limit age limit for dates
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
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
    * @param integer from     from limit for selected dates
    * @param integer interval interval limit for selected dates
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

   function setMonthLimit ($month) {
      $this->_month_limit = $month;
   }

   function setMonthLimit2 ($month) {
      $this->_month_limit2 = $month;
   }

   function setDayLimit ($day) {
      $this->_day_limit = $day;
   }

   function setYearLimit ($year) {
      $this->_year_limit = $year;
   }

   function setDateModeLimit ($value) {
      if ( $value == 3 ) {
         $this->_date_mode_limit = 0;
      } elseif ( $value == 2 ) {
         $this->_date_mode_limit = 2;
      } elseif ( $value == 4 ) {
         $this->_date_mode_limit = 1;
      }
   }

   function setWithoutDateModeLimit () {
      $this->setDateModeLimit(2);
   }

   function _performQuery ($mode = 'select') {
      if ($mode == 'count') {
         $query = 'SELECT count(dates.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT dates.item_id';
      } elseif ($mode == 'distinct') {
         $query = 'SELECT DISTINCT '.$this->_db_table.'.*';
      } else {
         $query = 'SELECT dates.*';
      }

      $query .= ' FROM dates';

      if ( !empty($this->_search_array) ||
           (isset($this->_sort_order) and
           ($this->_sort_order == 'modificator' || $this->_sort_order == 'modificator_rev')) ) {
         $query .= ' LEFT JOIN user AS people ON (people.item_id=dates.creator_id)'; // modificator_id (TBD)
      }

     // dates restricted by topics
     if ( isset($this->_topic_limit) ) {
        if ( $this->_topic_limit == -1 ) {
           $query .= ' LEFT JOIN link_items AS l21 ON';
           $query .= ' l21.deletion_date IS NULL';
           if ( isset($this->_room_limit) ) {
              $query .= ' AND l21.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
           }
           $query .= ' AND (l21.first_item_type = "'.CS_TOPIC_TYPE.'" OR l21.second_item_TYPE = "'.CS_TOPIC_TYPE.'")';
           $query .= ' AND (l21.first_item_id=dates.item_id OR l21.second_item_id=dates.item_id)';
           // second part in where clause
        } else {
           $query .= ' INNER JOIN link_items AS l21 ON';
           $query .= ' (l21.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
           $query .= ' AND l21.deletion_date IS NULL AND (l21.first_item_id=dates.item_id OR l21.second_item_id=dates.item_id)';
        }
     }
     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagID($this->_tag_limit);
        $query .= ' LEFT JOIN link_items AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id=dates.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id=dates.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
     }

      // restrict dates by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN links AS l6 ON l6.from_item_id=dates.item_id AND l6.link_type="buzzword_for"';
            $query .= ' LEFT JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN links AS l6 ON l6.from_item_id=dates.item_id AND l6.link_type="buzzword_for"';
            $query .= ' INNER JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }


     // dates restricted by groups
     if ( isset($this->_group_limit) ) {
        if ( $this->_group_limit == -1 ) {
           $query .= ' LEFT JOIN link_items AS l31 ON';
           $query .= ' l31.deletion_date IS NULL';
           if ( isset($this->_room_limit) ) {
              $query .= ' AND l31.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
           }
           $query .= ' AND (l31.first_item_type = "'.CS_GROUP_TYPE.'" OR l31.second_item_TYPE = "'.CS_GROUP_TYPE.'")';
           $query .= ' AND (l31.first_item_id=dates.item_id OR l31.second_item_id=dates.item_id)';
           // second part in where clause
        } else {
           $query .= ' INNER JOIN link_items AS l31 ON';
           $query .= ' (l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
           $query .= ' AND l31.deletion_date IS NULL AND (l31.first_item_id=dates.item_id OR l31.second_item_id=dates.item_id)';
        }
     }

      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN link_items AS l5 ON ( (l5.first_item_id=dates.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR(l5.second_item_id=dates.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' INNER JOIN item_link_file AS lf ON '.$this->_db_table.'.item_id = lf.item_iid';
      }

      $query .= ' WHERE 1';

      if (!$this->_show_not_activated_entries_limit) {
         $query .= ' AND (dates.modification_date IS NULL OR dates.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
      }
      // fifth, insert limits into the select statement
      if ( $this->_future_limit ) {
         #$query .= ' AND (dates.datetime_end > NOW() OR dates.datetime_start > NOW())'; // this will not get all dates today
         $date = date("Y-m-d").' 00:00:00';
         $query .= ' AND (dates.datetime_end >= "'.encode(AS_DB,$date).'" OR (dates.datetime_end="0000-00-00 00:00:00" AND dates.datetime_start >= "'.encode(AS_DB,$date).'") )';
      }
      if (isset($this->_room_limit)) {
         $query .= ' AND dates.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
      if ($this->_delete_limit == true) {
         $query .= ' AND dates.deleter_id IS NULL';
      }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND dates.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'"';
      }
      if (isset($this->_age_limit)) {
         $query .= ' AND dates.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND dates.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }

      // dates restricted by topics, second part
      if ( isset($this->_topic_limit) and $this->_topic_limit == -1 ) {
         $query .= ' AND l21.first_item_id IS NULL AND l21.second_item_id IS NULL';
      }

      // dates restricted by groups, second part
      if ( isset($this->_group_limit) and $this->_group_limit == -1 ) {
         $query .= ' AND l31.first_item_id IS NULL AND l31.second_item_id IS NULL';
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

      if (isset($this->_day_limit)) {
         $query .= ' AND DAYOFMONTH(dates.start_day) = "'.encode(AS_DB,$this->_day_limit).'"';
      }

      if (isset($this->_month_limit) AND isset($this->_year_limit)) {
         $string_start_day = $this->_year_limit.'-'.sprintf("%02d",$this->_month_limit).'-'.'01';
         $string_end_day = $this->_year_limit.'-'.sprintf("%02d",$this->_month_limit).'-'.daysInMonth($this->_month_limit, $this->_year_limit);
         $query .= ' AND ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
      }

      elseif (isset($this->_month_limit2) AND isset($this->_year_limit)) {
         $query .= ' AND (';
         $string_start_day = $this->_year_limit.'-'.sprintf("%02d",$this->_month_limit2).'-'.'01';
         $string_end_day = $this->_year_limit.'-'.sprintf("%02d",$this->_month_limit2).'-'.daysInMonth($this->_month_limit2, $this->_year_limit);
         $query .= ' ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         if ($this->_month_limit2 == 1 ){
            $year = $this->_year_limit-1;
            $string_start_day = $year.'-'.sprintf("%02d",12).'-'.'01';
            $string_end_day = $year.'-'.sprintf("%02d",12).'-'.daysInMonth(12, $year);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $string_start_day = $this->_year_limit.'-'.sprintf("%02d",2).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.sprintf("%02d",2).'-'.daysInMonth(2, $this->_year_limit);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }elseif ($this->_month_limit2 == 12 ){
            $year = $this->_year_limit+1;
            $string_start_day = $year.'-'.sprintf("%02d",1).'-'.'01';
            $string_end_day = $year.'-'.sprintf("%02d",1).'-'.daysInMonth(1, $year);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $string_start_day = $this->_year_limit.'-'.sprintf("%02d",11).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.sprintf("%02d",11).'-'.daysInMonth(11, $this->_year_limit);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }else{
            $month = $this->_month_limit2-1;
            $string_start_day = $this->_year_limit.'-'.sprintf("%02d",$month).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.sprintf("%02d",$month).'-'.daysInMonth($month, $this->_year_limit);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $month = $this->_month_limit2+1;
            $string_start_day = $this->_year_limit.'-'.sprintf("%02d",$month).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.sprintf("%02d",$month).'-'.daysInMonth($month, $this->_year_limit);
            $query .= ' OR ( '.
                ' (dates.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= dates.end_day AND (dates.end_day IS NOT NULL OR dates.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.start_day AND dates.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= dates.end_day AND dates.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }
         $query .= ' )';
      }

      if ( isset($this->_date_mode_limit) and $this->_date_mode_limit !=2) {
         $query .= ' AND dates.date_mode="'.encode(AS_DB,$this->_date_mode_limit).'"';
      }

      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->_db_table.'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         $field_array = array('TRIM(CONCAT(people.firstname," ",people.lastname))','dates.end_day','dates.start_day','dates.end_time','dates.start_time','dates.title','dates.description','dates.place');
         $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
         $query .= $search_limit_query_code;
         $query .= ' )';
      }

      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' AND lf.deleter_id IS NULL AND lf.deletion_date IS NULL';
      }

      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'place' ) {
            $query .= ' ORDER BY dates.place ASC';
         } elseif ( $this->_sort_order == 'place_rev' ) {
            $query .= ' ORDER BY dates.place DESC';
         } elseif ( $this->_sort_order == 'time' ) {
            $query .= ' ORDER BY dates.datetime_start ASC';
         } elseif ( $this->_sort_order == 'time_rev' ) {
            $query .= ' ORDER BY dates.datetime_start DESC';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY dates.title ASC';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY dates.title DESC';
         }
      } elseif ($this->_future_limit) {
         $query .= ' ORDER BY dates.datetime_start ASC';
      } else {
         $query .= ' ORDER BY dates.datetime_start DESC';
      }

      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
          include_once('functions/error_functions.php');
          trigger_error('Problems selecting dates.',E_USER_WARNING);
      } else {
          return $result;
      }
   }

  /** get a dates in newest version
   *
   * @param integer item_id id of the item
   *
   * @return object cs_item a label
   */
   function getItem ($item_id = NULL) {
     $dates = NULL;
     if ( !is_null($item_id) ) {
        $query = "SELECT * FROM dates WHERE dates.item_id = '".encode(AS_DB,$item_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting one dates item.',E_USER_WARNING);
        } elseif ( !empty($result[0]) ) {
           $dates = $this->_buildItem($result[0]);
        } else {
           include_once('functions/error_functions.php');trigger_error('Dates item ['.$item_id.'] does not exists.',E_USER_WARNING);
        }
     } else {
        $dates = $this->getNewItem();
     }
     return $dates;
   }

  /** get a list of dates items
   *
   * @param array of item_ids
   *
   * @return cs_list list of cs_dates_item
   *
   * @author CommSy Development Group
   */
   function getItemList ($id_array) {
      return $this->_getItemList('dates', $id_array);
   }

   /** build a new material item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    *
    * @author CommSy Development Group
    */
   function getNewItem () {
      include_once('classes/cs_dates_item.php');
      return new cs_dates_item($this->_environment);
   }

  /** update a dates - internal, do not use -> use method save
   * this method updates the database record for a given dates item
   *
   * @param cs_dates_item the dates item for which an update should be made
   *
   * @author CommSy Development Group
   */
   function _update ($item) {
      parent::_update($item);

      $modificator = $item->getModificatorItem();
      $current_datetime = getCurrentDateTimeInMySQL();

      if ($item->isPublic()) {
         $public = '1';
      } else {
         $public = '0';
      }
      $modification_date = getCurrentDateTimeInMySQL();
      if ($item->isNotActivated()){
         $modification_date = $item->getModificationDate();
      }

      $query = 'UPDATE dates SET '.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$modification_date.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'description="'.encode(AS_DB,$item->getDescription()).'",'.
               'start_time="'.encode(AS_DB,$item->getStartingTime()).'",'.
               'start_day="'.encode(AS_DB,$item->getStartingDay()).'",'.
               'end_time="'.encode(AS_DB,$item->getEndingTime()).'",'.
               'end_day="'.encode(AS_DB,$item->getEndingDay()).'",'.
               'datetime_start="'.encode(AS_DB,$item->getDateTime_start()).'",'.
               'datetime_end="'.encode(AS_DB,$item->getDateTime_end()).'",'.
               'place="'.encode(AS_DB,$item->getPlace()).'",'.
               'date_mode="'.encode(AS_DB,$item->getDateMode()).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems updating dates.',E_USER_WARNING);
      }
      unset($modificator);
      unset($item);
   }

  /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'ndates' in the database and sets the dates items item id.
   * it then calls the date_mode method _newNews to store the dates item itself.
   *
   * @param cs_dates_item the dates item for which an entry should be made
   */
  function _create ($item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="date"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating dates.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newDate($item);
        unset($result);
     }
     unset($item);
  }

  /** store a new dates item to the database - internal, do not use -> use method save
    * this method stores a newly created dates item to the database
    *
    * @param cs_dates_item the dates item to be stored
    *
    * @author CommSy Development Group
    */
  function _newDate ($item) {
     $user = $item->getCreatorItem();
     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

      if ($item->isPublic()) {
         $public = '1';
      } else {
         $public = '0';
      }
      $modification_date = getCurrentDateTimeInMySQL();
      if ($item->isNotActivated()){
         $modification_date = $item->getModificationDate();
      }

     $query = 'INSERT INTO dates SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'", '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'", '.
              'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$modification_date.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'", '.
              'public="'.encode(AS_DB,$public).'",'.
              'description="'.encode(AS_DB,$item->getDescription()).'", '.
              'start_time="'.encode(AS_DB,$item->getStartingTime()).'", '.
              'end_time="'.encode(AS_DB,$item->getEndingTime()).'", '.
              'start_day="'.encode(AS_DB,$item->getStartingDay()).'", '.
              'end_day="'.encode(AS_DB,$item->getEndingDay()).'", '.
              'datetime_start="'.encode(AS_DB,$item->getDateTime_start()).'", '.
              'datetime_end="'.encode(AS_DB,$item->getDateTime_end()).'", '.
              'place="'.encode(AS_DB,$item->getPlace()).'", '.
              'date_mode="'.encode(AS_DB,$item->getDateMode()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating dates.',E_USER_WARNING);
     }
     unset($item);
  }

  /**  delete a dates item
   *
   * @param cs_dates_item the dates item to be deleted
   *
   * @access public
   * @author CommSy Development Group
   */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE dates SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems deleting dates.',E_USER_WARNING);
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         parent::delete($item_id);
         unset($result);
      }
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountDates ($start, $end) {
      $retour = 0;

      $query = "SELECT count(dates.item_id) as number FROM dates WHERE dates.context_id = '".encode(AS_DB,$this->_room_limit)."' and ((dates.creation_date > '".encode(AS_DB,$start)."' and dates.creation_date < '".encode(AS_DB,$end)."') or (dates.modification_date > '".encode(AS_DB,$start)."' and dates.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all dates.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewDates ($start, $end) {
      $retour = 0;

      $query = "SELECT count(dates.item_id) as number FROM dates WHERE dates.context_id = '".encode(AS_DB,$this->_room_limit)."' and dates.creation_date > '".encode(AS_DB,$start)."' and dates.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting dates.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModDates ($start, $end) {
      $retour = 0;

      $query = "SELECT count(dates.item_id) as number FROM dates WHERE dates.context_id = '".encode(AS_DB,$this->_room_limit)."' and dates.modification_date > '".encode(AS_DB,$start)."' and dates.modification_date < '".encode(AS_DB,$end)."' and dates.modification_date != dates.creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting dates.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function deleteDatesOfUser($uid) {
      $query  = 'SELECT dates.* FROM dates WHERE dates.creator_id = "'.encode(AS_DB,$uid).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !empty($result) ) {
         foreach ( $result as $rs ) {
            $insert_query = 'UPDATE dates SET';
            $insert_query .= ' title = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
            $insert_query .= ' description = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
            $insert_query .= ' place = " ",';
            $insert_query .= ' public = "1"';
            $insert_query .='WHERE item_id = "'.encode(AS_DB,$rs['item_id']).'"';
            $result2 = $this->_db_connector->performQuery($insert_query);
            if ( !isset($result2) or !$result2 ) {
               include_once('functions/error_functions.php');trigger_error('Problems automatic deleting dates.',E_USER_WARNING);
            }
         }
      }
   }
}
?>