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
   var $_color_limit = NULL;
   var $_calendar_limit = NULL;
   var $_uid_limit = NULL;
   var $_recurrence_limit = NULL;

   var $_month_limit = NULL;
   var $_month_limit2 = NULL;
   var $_day_limit = NULL;
   var $_day_limit2 = NULL;
   var $_year_limit = NULL;
   var $_date_mode_limit = 1;
   var $_assignment_limit = false;
   var $_related_user_limit = NULL;
   private $_not_older_than_limit = NULL;
   private $_between_limit = null;
   private $_from_date_limit = null;
   private $_until_date_limit = null;
   private $_participant_limit = null;

    /**
     * @var bool Controls return of external dates
     */
   private $externalLimit = true;

    /**
     * @var bool Hides recurring entries
     */
   private $hideRecurringEntriesLimit = false;

    /** constructor
     * the only available constructor, initial values for internal variables
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
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
      $this->_day_limit2 = NULL;
      $this->_year_limit = NULL;
      $this->_color_limit = NULL;
      $this->_calendar_limit = NULL;
      $this->_uid_limit = NULL;
      $this->_recurrence_limit = NULL;
      $this->_date_mode_limit = 1;
      $this->_assignment_limit = false;
      $this->_not_older_than_limit = NULL;
      $this->_between_limit = null;
      $this->_related_user_limit = NULL;
      $this->_from_date_limit = null;
      $this->_until_date_limit = null;
      $this->_participant_limit = null;
   }

   public function setNotOlderThanMonthLimit ( $month ) {
      if ( !empty($month)
           and is_numeric($month)
         ) {
         include_once('functions/date_functions.php');
         $this->_not_older_than_limit = getCurrentDateTimeMinusMonthsInMySQL($month);
      }
   }

   public function setBetweenLimit( $startDate, $endDate )
   {
   		if (!empty($startDate) && !empty($endDate)) {
   			$this->_between_limit = array(
   				"start"		=> $startDate,
   				"end"		=> $endDate
   			);
   		} else if (!empty($startDate)) {
       		$this->_from_date_limit = $startDate;
   		} else if (!empty($endDate)) {
       		$this->_until_date_limit = $endDate;
   		}
   }

   /** set age limit
    * this method sets an age limit for dates
    *
    * @param integer limit age limit for dates
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }


   function setColorLimit ($limit) {
      $this->_color_limit = $limit;
   }

   function setCalendarArrayLimit ($limit) {
      $this->_calendar_limit = $limit;
   }

    function setUidArrayLimit ($limit) {
        $this->_uid_limit = $limit;
    }

   function setAssignmentLimit ($array) {
      $this->_assignment_limit = true;
      if (isset($array[0])){
         $this->_related_user_limit = $array;
      }
   }

   function setRecurrenceLimit ($limit) {
      $this->_recurrence_limit = $limit;
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

   function setDayLimit2 ($day) {
      $this->_day_limit2 = $day;
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

   function setParticipantArrayLimit ($limit) {
       $this->_participant_limit = $limit;
   }

    /**
     * @param bool $externalLimit limit external dates
     */
   public function setExternalLimit($externalLimit) {
       $this->externalLimit = $externalLimit;
   }

    /**
     * @param bool $hideRecurringEntriesLimit limit recurring entries
     */
   public function setHideRecurringEntriesLimit($hideRecurringEntriesLimit) {
       $this->hideRecurringEntriesLimit = $hideRecurringEntriesLimit;
   }

   function _performQuery ($mode = 'select') {
      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix('dates').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('dates').'.item_id';
      } elseif ($mode == 'distinct') {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix('dates').'.*,
         				(
         					SELECT
         						COUNT('. $this->addDatabasePrefix('annotations') .'.item_id)
         					FROM
         						'. $this->addDatabasePrefix('annotations') .'
         					WHERE
         						'. $this->addDatabasePrefix('annotations') .'.linked_item_id = '.$this->addDatabasePrefix('dates').'.item_id
         				) as count_annotations
         ';
      }

      $query .= ' FROM '.$this->addDatabasePrefix('dates');
      $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('dates').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

      if ( !empty($this->_search_array) ||
           (isset($this->_sort_order) and
           ($this->_sort_order == 'modificator' || $this->_sort_order == 'modificator_rev')) ) {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS people ON (people.item_id='.$this->addDatabasePrefix('dates').'.creator_id)'; // modificator_id (TBD)

         //look in filenames of linked files for the search_limit
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('item_link_file').' ON '.$this->addDatabasePrefix('dates').'.item_id = '.$this->addDatabasePrefix('item_link_file').'.item_iid'.
                   ' LEFT JOIN '.$this->addDatabasePrefix('files').' ON '.$this->addDatabasePrefix('item_link_file').'.file_id = '.$this->addDatabasePrefix('files').'.files_id';
         //look in filenames of linked files for the search_limit
      }

     // dates restricted by topics
     if ( isset($this->_topic_limit) ) {
        if ( $this->_topic_limit == -1 ) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON';
           $query .= ' l21.deletion_date IS NULL';
           if ( isset($this->_room_limit) ) {
              $query .= ' AND l21.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
           }
           $query .= ' AND (l21.first_item_type = "'.CS_TOPIC_TYPE.'" OR l21.second_item_TYPE = "'.CS_TOPIC_TYPE.'")';
           $query .= ' AND (l21.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l21.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           // second part in where clause
        } else {
           $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON';
           $query .= ' (l21.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
           $query .= ' AND l21.deletion_date IS NULL AND (l21.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l21.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
        }
     }

     if ( isset($this->_user_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit1 ON ( user_limit1.deletion_date IS NULL AND ((user_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit2 ON ( user_limit2.deletion_date IS NULL AND ((user_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }
     if ( isset($this->_assignment_limit)  AND isset($this->_related_user_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit1 ON ( related_user_limit1.deletion_date IS NULL AND ((related_user_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND related_user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit2 ON ( related_user_limit2.deletion_date IS NULL AND ((related_user_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND related_user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }
     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
     }
     if ( isset($this->_participant_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS participant_limit1 ON ( participant_limit1.deletion_date IS NULL AND ((participant_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND participant_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS participant_limit2 ON ( participant_limit2.deletion_date IS NULL AND ((participant_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND participant_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }

      // restrict dates by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l6.link_type="buzzword_for"';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l6.link_type="buzzword_for"';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }


     // dates restricted by groups
     if ( isset($this->_group_limit) ) {
        if ( $this->_group_limit == -1 ) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON';
           $query .= ' l31.deletion_date IS NULL';
           if ( isset($this->_room_limit) ) {
              $query .= ' AND l31.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
           }
           $query .= ' AND (l31.first_item_type = "'.CS_GROUP_TYPE.'" OR l31.second_item_TYPE = "'.CS_GROUP_TYPE.'")';
           $query .= ' AND (l31.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l31.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           // second part in where clause
        } else {
           $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON';
           $query .= ' (l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
           $query .= ' AND l31.deletion_date IS NULL AND (l31.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l31.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
        }
     }

      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR(l5.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf.item_iid';
      }

      $query .= ' WHERE 1';

       switch ($this->inactiveEntriesLimit) {
           case self::SHOW_ENTRIES_ONLY_ACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('dates') . '.activation_date IS NULL OR ' . $this->addDatabasePrefix('dates') . '.activation_date <= "' . getCurrentDateTimeInMySQL() . '")';
               break;
           case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('dates') . '.activation_date IS NOT NULL AND ' . $this->addDatabasePrefix('dates') . '.activation_date > "' . getCurrentDateTimeInMySQL() . '")';
               break;
       }

      // fifth, insert limits into the select statement
      if ( $this->_future_limit ) {
         #$query .= ' AND (dates.datetime_end > NOW() OR dates.datetime_start > NOW())'; // this will not get all dates today
         $date = date("Y-m-d").' 00:00:00';
         $query .= ' AND ('.$this->addDatabasePrefix('dates').'.datetime_end >= "'.encode(AS_DB,$date).'" OR ('.$this->addDatabasePrefix('dates').'.datetime_end="0000-00-00 00:00:00" AND '.$this->addDatabasePrefix('dates').'.datetime_start >= "'.encode(AS_DB,$date).'") )';
      }

       if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
           $query .= ' AND ' . $this->addDatabasePrefix('dates') . '.context_id IN (' . implode(", ", $this->_room_array_limit) . ')';
       } else if (isset($this->_room_limit)) {
           $query .= ' AND ' . $this->addDatabasePrefix('dates') . '.context_id = "' . encode(AS_DB, $this->_room_limit) . '"';
       }

      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.deleter_id IS NULL';
      }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'"';
      }
      if (isset($this->_age_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
      if (isset($this->_color_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.color = "'.encode(AS_DB,$this->_color_limit).'"';
      }
      if (isset($this->_calendar_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.calendar_id IN ('.implode(", ", $this->_calendar_limit).')';
      }
      if (isset($this->_uid_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.uid IN ('.implode(", ", $this->_uid_limit).')';
      }
      if (isset($this->_recurrence_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.recurrence_id = "'.encode(AS_DB,$this->_recurrence_limit).'"';
      }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }

      // dates restricted by topics, second part
      if ( isset($this->_topic_limit) and $this->_topic_limit == -1 ) {
         $query .= ' AND l21.first_item_id IS NULL AND l21.second_item_id IS NULL';
      }

      if ( isset($this->_institution_limit) ){
         if ($this->_institution_limit == -1){
            $query .= ' AND (l121.first_item_id IS NULL AND l121.second_item_id IS NULL)';
            $query .= ' AND (l122.first_item_id IS NULL AND l122.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l121.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l121.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'")';
            $query .= ' OR (l122.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l122.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'"))';
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

      // dates restricted by groups, second part
      if ( isset($this->_group_limit) and $this->_group_limit == -1 ) {
         $query .= ' AND l31.first_item_id IS NULL AND l31.second_item_id IS NULL';
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

      if ( isset($this->_participant_limit) ) {
         $id_string = implode(', ',$this->_participant_limit);
         
         if( $this->_participant_limit == -1 ){
            $query .= ' AND (participant_limit1.first_item_id IS NULL AND participant_limit1.second_item_id IS NULL)';
            $query .= ' AND (participant_limit2.first_item_id IS NULL AND participant_limit2.second_item_id IS NULL)';
         }else{
            $query .= ' AND ( (participant_limit1.first_item_id IN ('.encode(AS_DB,$id_string).') OR participant_limit1.second_item_id IN ('.encode(AS_DB,$id_string).') )';
            $query .= ' OR (participant_limit2.first_item_id IN ('.encode(AS_DB,$id_string).') OR participant_limit2.second_item_id IN ('.encode(AS_DB,$id_string).') ))';
         }
      }

      if (isset($this->_day_limit)) {
         $query .= ' AND DAYOFMONTH('.$this->addDatabasePrefix('dates').'.start_day) = "'.encode(AS_DB,$this->_day_limit).'"';
      }

      if (isset($this->_month_limit) AND isset($this->_year_limit)) {
         $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",$this->_month_limit).'-'.'01';
         $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",$this->_month_limit).'-'.daysInMonth($this->_month_limit, $this->_year_limit);
         $query .= ' AND ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
      }

      elseif (isset($this->_month_limit2) AND isset($this->_year_limit)) {
        $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",$this->_month_limit2).'-'.'01';
        $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",$this->_month_limit2).'-'.daysInMonth($this->_month_limit2, $this->_year_limit);
        $query .= ' AND ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   '';
         if ($this->_month_limit2 == 1 ){
            $year = $this->_year_limit-1;
            $string_start_day = $year.'-'.mb_sprintf("%02d",12).'-'.'01';
            $string_end_day = $year.'-'.mb_sprintf("%02d",12).'-'.daysInMonth(12, $year);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",2).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",2).'-'.daysInMonth(2, $this->_year_limit);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }elseif ($this->_month_limit2 == 12 ){
            $year = $this->_year_limit+1;
            $string_start_day = $year.'-'.mb_sprintf("%02d",1).'-'.'01';
            $string_end_day = $year.'-'.mb_sprintf("%02d",1).'-'.daysInMonth(1, $year);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",11).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",11).'-'.daysInMonth(11, $this->_year_limit);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }else{
            $month = $this->_month_limit2-1;
            $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",$month).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",$month).'-'.daysInMonth($this->_month_limit2, $this->_year_limit);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
            $month = $this->_month_limit2+1;
            $string_start_day = $this->_year_limit.'-'.mb_sprintf("%02d",$month).'-'.'01';
            $string_end_day = $this->_year_limit.'-'.mb_sprintf("%02d",$month).'-'.daysInMonth($month, $this->_year_limit);
            $query .= ' OR ( '.
                ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_start_day).'" AND "'.encode(AS_DB,$string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB,$string_end_day).'")'.
                          ' OR ("'.encode(AS_DB,$string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB,$string_end_day).'")'.
                   ')';
         }
         $query .= ' )';
      }

      if ( isset($this->_date_mode_limit)
           and $this->_date_mode_limit !=2
           and empty($this->_id_array_limit)
         ) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.date_mode="'.encode(AS_DB,$this->_date_mode_limit).'"';
      }

      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         $field_array = array('TRIM(CONCAT(people.firstname," ",people.lastname))',$this->addDatabasePrefix('dates').'.end_day',$this->addDatabasePrefix('dates').'.start_day',$this->addDatabasePrefix('dates').'.end_time',$this->addDatabasePrefix('dates').'.start_time',$this->addDatabasePrefix('dates').'.title',$this->addDatabasePrefix('dates').'.description',$this->addDatabasePrefix('dates').'.place',$this->addDatabasePrefix('files').'.filename');
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

      // $this->_not_older_than_limit
      if ( isset($this->_not_older_than_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.datetime_start > "'.$this->_not_older_than_limit.'"';
      }

      if ( isset($this->_between_limit) && !empty($this->_between_limit) )
      {
      		$query .= "
      			AND
      			(
      				(
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_start <= '" . $this->_between_limit["start"] . "' AND
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_end >= '" . $this->_between_limit["end"] . "'
      				)
      				OR
      				(
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_start <= '" . $this->_between_limit["end"] . "' AND
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_end >= '" . $this->_between_limit["end"] . "'
      				)
      				OR
      				(
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_start <= '" . $this->_between_limit["start"] . "' AND
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_end >= '" . $this->_between_limit["start"] . "'
      				)
      				OR
      				(
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_start >= '" . $this->_between_limit["start"] . "' AND
      					" . $this->addDatabasePrefix($this->_db_table) . ".datetime_end <= '" . $this->_between_limit["end"] . "'
      				)
      			)
      		";
      }
      else if ( isset($this->_from_date_limit) && !empty($this->_from_date_limit) )
      {
      		$query .= " AND " . $this->addDatabasePrefix($this->_db_table) . ".datetime_start >= '" . $this->_from_date_limit . "'";
      }
      else if ( isset($this->_until_date_limit) && !empty($this->_until_date_limit) )
      {
      		$query .= " AND " . $this->addDatabasePrefix($this->_db_table) . ".datetime_end <= '" . $this->_until_date_limit . "'";
      }

      if (!$this->externalLimit) {
          $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.external = "0"';
      }

      if ($this->hideRecurringEntriesLimit) {
          $databasePrefix = $this->addDatabasePrefix($this->_db_table);
          $query .= ' AND (' . $databasePrefix . '.recurrence_id IS NULL OR ' . $databasePrefix . '.recurrence_id = ' . $databasePrefix . '.item_id)';
      }

       if ($this->modificationNewerThenLimit) {
           $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date >= "' . $this->modificationNewerThenLimit->format('Y-m-d H:i:s') . '"';
       }

       if ($this->excludedIdsLimit) {
           $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.item_id NOT IN (' . implode(", ", encode(AS_DB, $this->excludedIdsLimit)) . ')';
       }

      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'place' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.place ASC';
         } elseif ( $this->_sort_order == 'place_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.place DESC';
         } elseif ( $this->_sort_order == 'time' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
         } elseif ( $this->_sort_order == 'time_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start DESC';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.title ASC';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.title DESC';
         } elseif ($this->_sort_order == 'date') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.modification_date DESC';
         }
      } elseif ($this->_future_limit) {
         $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
      } else {
         $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start DESC';
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
   * @return \cs_dates_item a label
   */
   function getItem ($item_id = NULL) {
     $dates = NULL;
     if ( !is_null($item_id) ) {
        if ( !empty($this->_cache_object[$item_id]) ) {
           $dates = $this->_cache_object[$item_id];
        } else {
           $query = "SELECT * FROM ".$this->addDatabasePrefix("dates")." WHERE ".$this->addDatabasePrefix("dates").".item_id = '".encode(AS_DB,$item_id)."'";
           $result = $this->_db_connector->performQuery($query);
           if ( !isset($result) ) {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting one dates item.',E_USER_WARNING);
           } elseif ( !empty($result[0]) ) {
              $dates = $this->_buildItem($result[0]);
           } else {
              include_once('functions/error_functions.php');
              trigger_error('Dates item ['.$item_id.'] does not exists.',E_USER_WARNING);
           }
        }
     } else {
        $dates = $this->getNewItem();
     }
     return $dates;
   }

   /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      include_once('functions/text_functions.php');
      $db_array['recurrence_pattern'] = mb_unserialize($db_array['recurrence_pattern']);
      return parent::_buildItem($db_array);
   }

   function getColorArray () {
     $color_array = array();
     $query = 'SELECT DISTINCT color FROM '.$this->addDatabasePrefix('dates').' WHERE 1';
     if (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('dates').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting one dates item.',E_USER_WARNING);
     } else {
        foreach ($result as $rs ) {
           if ($rs['color'] != 'NULL' and !empty($rs['color'])){
              $color_array[] = $rs['color'];
           }
        }
     }
     return $color_array;
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
    function _update($item)
    {
        /** @var cs_dates_item $item */
        parent::_update($item);

        $modificator = $item->getModificatorItem();
        $current_datetime = getCurrentDateTimeInMySQL();

        if ($item->isPublic()) {
            $public = '1';
        } else {
            $public = '0';
        }

        $modificationDate = !$item->isChangeModificationOnSave() ? $item->getModificationDate() : getCurrentDateTimeInMySQL();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $color = $item->getColor();
        $color = empty($color) ? null : $color;

        $calendarId = $item->getCalendarId();
        $calendarId = empty($calendarId) ? $item->getContextItem()->getdefaultCalendarId() : $calendarId;

        $recurrenceId = $item->getRecurrenceId();
        $recurrenceId = empty($recurrenceId) ? null : $recurrenceId;

        $recurrencePattern = $item->getRecurrencePattern();
        $recurrencePattern = empty($recurrencePattern) ? null : serialize($recurrencePattern);

        $datetimeRecurrence = $item->getDateTime_recurrence();
        $datetimeRecurrence = $datetimeRecurrence ?: null;

        $queryBuilder
            ->update($this->addDatabasePrefix('dates'))
            ->set('modifier_id', ':modifierId')
            ->set('modification_date', ':modificationDate')
            ->set('activation_date', ':activationDate')
            ->set('title', ':title')
            ->set('public', ':public')
            ->set('description', ':description')
            ->set('start_time', ':startTime')
            ->set('start_day', ':startDay')
            ->set('end_time', ':endTime')
            ->set('end_day', ':endDay')
            ->set('datetime_start', ':datetimeStart')
            ->set('datetime_end', ':datetimeEnd')
            ->set('place', ':place')
            ->set('date_mode', ':dateMode')
            ->set('color', ':color')
            ->set('calendar_id', ':calendarId')
            ->set('recurrence_id', ':recurrenceId')
            ->set('recurrence_pattern', ':recurrencePattern')
            ->set('external', ':external')
            ->set('uid', ':uid')
            ->set('whole_day', ':wholeDay')
            ->set('datetime_recurrence', ':datetimeRecurrence')
            ->where('item_id = :itemId')
            ->setParameter('modifierId', $modificator->getItemID())
            ->setParameter('modificationDate', $modificationDate)
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('title', $item->getTitle())
            ->setParameter('public', $public)
            ->setParameter('description', $item->getDescription())
            ->setParameter('startTime', $item->getStartingTime())
            ->setParameter('startDay', $item->getStartingDay())
            ->setParameter('endTime', $item->getEndingTime())
            ->setParameter('endDay', $item->getEndingDay())
            ->setParameter('datetimeStart', $item->getDateTime_start())
            ->setParameter('datetimeEnd', $item->getDateTime_end())
            ->setParameter('place', $item->getPlace())
            ->setParameter('dateMode', $item->getDateMode() ? 1 : 0)
            ->setParameter('color', $color)
            ->setParameter('calendarId', $calendarId)
            ->setParameter('recurrenceId', $recurrenceId)
            ->setParameter('recurrencePattern', $recurrencePattern)
            ->setParameter('external', $item->isExternal() ? 1 : 0)
            ->setParameter('uid', $item->getUid())
            ->setParameter('wholeDay', $item->isWholeDay() ? 1 : 0)
            ->setParameter('datetimeRecurrence', $datetimeRecurrence)
            ->setParameter('itemId', $item->getItemID());

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error('Problems updating dates.', E_USER_WARNING);
        }
    }

    /**
     * create a new item in the items table - internal, do not use -> use method save
     * this method creates a new item of type 'ndates' in the database and sets the dates items item id.
     * it then calls the date_mode method _newNews to store the dates item itself.
     *
     * @param cs_dates_item $item
     * @throws \Doctrine\DBAL\Exception
     */
    function _create(cs_dates_item $item)
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
            ->setParameter('modificationDate', $item->isExternal() ? $item->getCreationDate() : getCurrentDateTimeInMySQL())
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('type', 'date')
            ->setParameter('draft', $item->isDraft());

        try {
            $queryBuilder->executeStatement();

            $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
            $item->setItemID($this->getCreateID());
            $this->_newDate($item);

        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error($e->getMessage(), E_USER_WARNING);
            $this->_create_id = null;
        }
    }

    /** store a new dates item to the database - internal, do not use -> use method save
     * this method stores a newly created dates item to the database
     *
     * @param cs_dates_item the dates item to be stored
     *
     * @author CommSy Development Group
     */
    function _newDate(cs_dates_item $item)
    {
        /** @var cs_dates_item $item */
        $user = $item->getCreatorItem();
        $modificator = $item->getModificatorItem();
        $current_datetime = getCurrentDateTimeInMySQL();
        if ($item->isExternal()) {
            $current_datetime = $item->getCreationDate();
        }

        if ($item->isPublic()) {
            $public = '1';
        } else {
            $public = '0';
        }
        $modification_date = getCurrentDateTimeInMySQL();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $color = $item->getColor();
        $color = empty($color) ? null : $color;

        $calendarId = $item->getCalendarId();
        $calendarId = empty($calendarId) ? $item->getContextItem()->getdefaultCalendarId() : $calendarId;

        $recurrenceId = $item->getRecurrenceId();
        $recurrenceId = empty($recurrenceId) ? null : $recurrenceId;

        $recurrencePattern = $item->getRecurrencePattern();
        $recurrencePattern = empty($recurrencePattern) ? null : serialize($recurrencePattern);

        $datetimeRecurrence = $item->getDateTime_recurrence();
        $datetimeRecurrence = $datetimeRecurrence ?: null;

        $queryBuilder
            ->insert($this->addDatabasePrefix('dates'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('activation_date', ':activationDate')
            ->setValue('title', ':title')
            ->setValue('public', ':public')
            ->setValue('description', ':description')
            ->setValue('start_time', ':startTime')
            ->setValue('start_day', ':startDay')
            ->setValue('end_time', ':endTime')
            ->setValue('end_day', ':endDay')
            ->setValue('datetime_start', ':datetimeStart')
            ->setValue('datetime_end', ':datetimeEnd')
            ->setValue('place', ':place')
            ->setValue('date_mode', ':dateMode')
            ->setValue('color', ':color')
            ->setValue('calendar_id', ':calendarId')
            ->setValue('recurrence_id', ':recurrenceId')
            ->setValue('recurrence_pattern', ':recurrencePattern')
            ->setValue('external', ':external')
            ->setValue('uid', ':uid')
            ->setValue('whole_day', ':wholeDay')
            ->setValue('datetime_recurrence', ':datetimeRecurrence')
            ->setParameter('itemId', $item->getItemId())
            ->setParameter('contextId', $item->getContextID())
            ->setParameter('creatorId', $user->getItemID())
            ->setParameter('creationDate', $current_datetime)
            ->setParameter('modifierId', $modificator->getItemID())
            ->setParameter('modificationDate', $modification_date)
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('title', $item->getTitle())
            ->setParameter('public', $public)
            ->setParameter('description', $item->getDescription())
            ->setParameter('startTime', $item->getStartingTime())
            ->setParameter('startDay', $item->getStartingDay())
            ->setParameter('endTime', $item->getEndingTime())
            ->setParameter('endDay', $item->getEndingDay())
            ->setParameter('datetimeStart', $item->getDateTime_start())
            ->setParameter('datetimeEnd', $item->getDateTime_end())
            ->setParameter('place', $item->getPlace())
            ->setParameter('dateMode', $item->getDateMode() ? 1 : 0)
            ->setParameter('color', $color)
            ->setParameter('calendarId', $calendarId)
            ->setParameter('recurrenceId', $recurrenceId)
            ->setParameter('recurrencePattern', $recurrencePattern)
            ->setParameter('external', $item->isExternal() ? 1 : 0)
            ->setParameter('uid', $item->getUid())
            ->setParameter('wholeDay', $item->isWholeDay() ? 1 : 0)
            ->setParameter('datetimeRecurrence', $datetimeRecurrence);

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error('Problems creating dates.', E_USER_WARNING);
        }
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
      $user_id = $current_user->getItemID() ?: 0;
      $query = 'UPDATE '.$this->addDatabasePrefix('dates').' SET '.
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

      $query = "SELECT count(".$this->addDatabasePrefix("dates").".item_id) as number FROM ".$this->addDatabasePrefix("dates")." WHERE ".$this->addDatabasePrefix("dates").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ((".$this->addDatabasePrefix("dates").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("dates").".creation_date < '".encode(AS_DB,$end)."') or (".$this->addDatabasePrefix("dates").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("dates").".modification_date < '".encode(AS_DB,$end)."'))";
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

      $query = "SELECT count(".$this->addDatabasePrefix("dates").".item_id) as number FROM ".$this->addDatabasePrefix("dates")." WHERE ".$this->addDatabasePrefix("dates").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("dates").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("dates").".creation_date < '".encode(AS_DB,$end)."'";
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

      $query = "SELECT count(".$this->addDatabasePrefix("dates").".item_id) as number FROM ".$this->addDatabasePrefix("dates")." WHERE ".$this->addDatabasePrefix("dates").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("dates").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("dates").".modification_date < '".encode(AS_DB,$end)."' and ".$this->addDatabasePrefix("dates").".modification_date != ".$this->addDatabasePrefix("dates").".creation_date";
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

    /**
     * @param $uid
     */
    public function deleteDatesOfUser($uid)
    {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query = 'SELECT ' . $this->addDatabasePrefix('dates') . '.* FROM ' . $this->addDatabasePrefix('dates') . ' WHERE ' . $this->addDatabasePrefix('dates') . '.creator_id = "' . encode(AS_DB,
                    $uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('dates') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'FLAG') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === 'FALSE') {
                        $translator = $this->_environment->getTranslationObject();

                        $updateQuery .= ' title = "' . encode(AS_DB,
                                $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,
                                $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' place = " ",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '",';
                        $updateQuery .= ' public = "1"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB, $rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        trigger_error('Problems automatic deleting dates.', E_USER_WARNING);
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

        $this->setExternalLimit(false);
        $this->setDateModeLimit(3);
        $this->setHideRecurringEntriesLimit(true);
        $this->setSortOrder('date');

        $this->select();
        return $this->get();
    }
}