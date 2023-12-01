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

/** class for database connection to the database table "dates"
 * this class implements a database manager for the table "dates".
 */
class cs_dates_manager extends cs_manager
{
    /**
     * integer - containing the age of dates as a limit.
     */
    public $_age_limit = null;

    public $_future_limit = false;

    /**
     * integer - containing a start point for the select dates.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many dates the select statement should get.
     */
    public $_interval_limit = null;

    public $_group_limit = null;
    public $_topic_limit = null;
    public $_sort_order = null;
    public $_color_limit = null;
    public $_calendar_limit = null;
    public $_uid_limit = null;
    public $_recurrence_limit = null;

    public $_month_limit = null;
    public $_month_limit2 = null;
    public $_day_limit = null;
    public $_day_limit2 = null;
    public $_year_limit = null;
    public $_date_mode_limit = 1;
    public $_assignment_limit = false;
    public $_related_user_limit = null;
    /**
     * @var mixed|null
     */
    private $_not_older_than_limit = null;
    /**
     * @var array<string, mixed>|null
     */
    private ?array $_between_limit = null;
    /**
     * @var mixed|null
     */
    private $_from_date_limit = null;
    /**
     * @var mixed|null
     */
    private $_until_date_limit = null;
    /**
     * @var mixed|null
     */
    private $_participant_limit = null;

    /**
     * @var bool Controls return of external dates
     */
    private bool $externalLimit = true;

    /**
     * @var bool Hides recurring entries
     */
    private bool $hideRecurringEntriesLimit = false;

    /** constructor
     * the only available constructor, initial values for internal variables
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'dates';
    }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
    */
   public function resetLimits()
   {
       parent::resetLimits();
       $this->_age_limit = null;
       $this->_future_limit = false;
       $this->_from_limit = null;
       $this->_interval_limit = null;
       $this->_order = null;
       $this->_group_limit = null;
       $this->_topic_limit = null;
       $this->_sort_order = null;
       $this->_month_limit = null;
       $this->_month_limit2 = null;
       $this->_day_limit = null;
       $this->_day_limit2 = null;
       $this->_year_limit = null;
       $this->_color_limit = null;
       $this->_calendar_limit = null;
       $this->_uid_limit = null;
       $this->_recurrence_limit = null;
       $this->_date_mode_limit = 1;
       $this->_assignment_limit = false;
       $this->_not_older_than_limit = null;
       $this->_between_limit = null;
       $this->_related_user_limit = null;
       $this->_from_date_limit = null;
       $this->_until_date_limit = null;
       $this->_participant_limit = null;
   }

   public function setNotOlderThanMonthLimit($month)
   {
       if (!empty($month)
            and is_numeric($month)
       ) {
           $this->_not_older_than_limit = getCurrentDateTimeMinusMonthsInMySQL($month);
       }
   }

   public function setBetweenLimit($startDate, $endDate)
   {
       if (!empty($startDate) && !empty($endDate)) {
           $this->_between_limit = ['start' => $startDate, 'end' => $endDate];
       } elseif (!empty($startDate)) {
           $this->_from_date_limit = $startDate;
       } elseif (!empty($endDate)) {
           $this->_until_date_limit = $endDate;
       }
   }

   /** set age limit
    * this method sets an age limit for dates.
    *
    * @param int limit age limit for dates
    */
   public function setAgeLimit($limit)
   {
       $this->_age_limit = (int) $limit;
   }

   public function setColorLimit($limit)
   {
       $this->_color_limit = $limit;
   }

   public function setCalendarArrayLimit($limit)
   {
       $this->_calendar_limit = $limit;
   }

    public function setUidArrayLimit($limit)
    {
        $this->_uid_limit = $limit;
    }

   public function setAssignmentLimit($array)
   {
       $this->_assignment_limit = true;
       if (isset($array[0])) {
           $this->_related_user_limit = $array;
       }
   }

   public function setRecurrenceLimit($limit)
   {
       $this->_recurrence_limit = $limit;
   }

   /** set future limit
    * Restricts selected dates to dates in the future.
    */
   public function setFutureLimit()
   {
       $this->_future_limit = true;
   }

   /** set interval limit
    * this method sets a interval limit.
    *
    * @param int from     from limit for selected dates
    * @param int interval interval limit for selected dates
    */
   public function setIntervalLimit($from, $interval)
   {
       $this->_interval_limit = (int) $interval;
       $this->_from_limit = (int) $from;
   }

   public function setGroupLimit($limit)
   {
       $this->_group_limit = (int) $limit;
   }

   public function setTopicLimit($limit)
   {
       $this->_topic_limit = (int) $limit;
   }

   public function setSortOrder($order)
   {
       $this->_sort_order = (string) $order;
   }

   public function setMonthLimit($month)
   {
       $this->_month_limit = $month;
   }

   public function setMonthLimit2($month)
   {
       $this->_month_limit2 = $month;
   }

   public function setDayLimit($day)
   {
       $this->_day_limit = $day;
   }

   public function setDayLimit2($day)
   {
       $this->_day_limit2 = $day;
   }

   public function setYearLimit($year)
   {
       $this->_year_limit = $year;
   }

   public function setDateModeLimit($value)
   {
       if (3 == $value) {
           $this->_date_mode_limit = 0;
       } elseif (2 == $value) {
           $this->_date_mode_limit = 2;
       } elseif (4 == $value) {
           $this->_date_mode_limit = 1;
       }
   }

   public function setWithoutDateModeLimit()
   {
       $this->setDateModeLimit(2);
   }

   public function setParticipantArrayLimit($limit)
   {
       $this->_participant_limit = $limit;
   }

   /**
    * @param bool $externalLimit limit external dates
    */
   public function setExternalLimit(bool $externalLimit)
   {
       $this->externalLimit = $externalLimit;
   }

   /**
    * @param bool $hideRecurringEntriesLimit limit recurring entries
    */
   public function setHideRecurringEntriesLimit($hideRecurringEntriesLimit)
   {
       $this->hideRecurringEntriesLimit = $hideRecurringEntriesLimit;
   }

   public function _performQuery($mode = 'select')
   {
       if ('count' == $mode) {
           $query = 'SELECT count('.$this->addDatabasePrefix('dates').'.item_id) AS count';
       } elseif ('id_array' == $mode) {
           $query = 'SELECT '.$this->addDatabasePrefix('dates').'.item_id';
       } elseif ('distinct' == $mode) {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
       } else {
           $query = 'SELECT '.$this->addDatabasePrefix('dates').'.*,
         				(
         					SELECT
         						COUNT('.$this->addDatabasePrefix('annotations').'.item_id)
         					FROM
         						'.$this->addDatabasePrefix('annotations').'
         					WHERE
         						'.$this->addDatabasePrefix('annotations').'.linked_item_id = '.$this->addDatabasePrefix('dates').'.item_id
         				) as count_annotations
         ';
       }

       $query .= ' FROM '.$this->addDatabasePrefix('dates');
       $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('dates').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

       // dates restricted by topics
       if (isset($this->_topic_limit)) {
           if (-1 == $this->_topic_limit) {
               $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON';
               $query .= ' l21.deletion_date IS NULL';
               if (isset($this->_room_limit)) {
                   $query .= ' AND l21.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
               }
               $query .= ' AND (l21.first_item_type = "'.CS_TOPIC_TYPE.'" OR l21.second_item_TYPE = "'.CS_TOPIC_TYPE.'")';
               $query .= ' AND (l21.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l21.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           // second part in where clause
           } else {
               $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON';
               $query .= ' (l21.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'")';
               $query .= ' AND l21.deletion_date IS NULL AND (l21.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l21.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           }
       }

       if (isset($this->_user_limit)) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit1 ON ( user_limit1.deletion_date IS NULL AND ((user_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS user_limit2 ON ( user_limit2.deletion_date IS NULL AND ((user_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
       }
       if (isset($this->_assignment_limit) and isset($this->_related_user_limit)) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit1 ON ( related_user_limit1.deletion_date IS NULL AND ((related_user_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND related_user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS related_user_limit2 ON ( related_user_limit2.deletion_date IS NULL AND ((related_user_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND related_user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
       }
       if (isset($this->_tag_limit)) {
           $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
       }
       if (isset($this->_participant_limit)) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS participant_limit1 ON ( participant_limit1.deletion_date IS NULL AND ((participant_limit1.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND participant_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS participant_limit2 ON ( participant_limit2.deletion_date IS NULL AND ((participant_limit2.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND participant_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
       }

       // restrict dates by buzzword (la4)
       if (isset($this->_buzzword_limit)) {
           if (-1 == $this->_buzzword_limit) {
               $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l6.link_type="buzzword_for"';
               $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
           } else {
               $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l6.link_type="buzzword_for"';
               $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
           }
       }

       // dates restricted by groups
       if (isset($this->_group_limit)) {
           if (-1 == $this->_group_limit) {
               $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON';
               $query .= ' l31.deletion_date IS NULL';
               if (isset($this->_room_limit)) {
                   $query .= ' AND l31.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
               }
               $query .= ' AND (l31.first_item_type = "'.CS_GROUP_TYPE.'" OR l31.second_item_TYPE = "'.CS_GROUP_TYPE.'")';
               $query .= ' AND (l31.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l31.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           // second part in where clause
           } else {
               $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON';
               $query .= ' (l31.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_group_limit).'")';
               $query .= ' AND l31.deletion_date IS NULL AND (l31.first_item_id='.$this->addDatabasePrefix('dates').'.item_id OR l31.second_item_id='.$this->addDatabasePrefix('dates').'.item_id)';
           }
       }

       if (isset($this->_ref_id_limit)) {
           $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l5.second_item_id="'.encode(AS_DB, $this->_ref_id_limit).'")
                     OR(l5.second_item_id='.$this->addDatabasePrefix('dates').'.item_id AND l5.first_item_id="'.encode(AS_DB, $this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
       }

       $query .= ' WHERE 1';

       switch ($this->inactiveEntriesLimit) {
           case self::SHOW_ENTRIES_ONLY_ACTIVATED:
               $query .= ' AND ('.$this->addDatabasePrefix('dates').'.activation_date IS NULL OR '.$this->addDatabasePrefix('dates').'.activation_date <= "'.getCurrentDateTimeInMySQL().'")';
               break;
           case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
               $query .= ' AND ('.$this->addDatabasePrefix('dates').'.activation_date IS NOT NULL AND '.$this->addDatabasePrefix('dates').'.activation_date > "'.getCurrentDateTimeInMySQL().'")';
               break;
       }

       // fifth, insert limits into the select statement
       if ($this->_future_limit) {
           // $query .= ' AND (dates.datetime_end > NOW() OR dates.datetime_start > NOW())'; // this will not get all dates today
           $date = date('Y-m-d').' 00:00:00';
           $query .= ' AND ('.$this->addDatabasePrefix('dates').'.datetime_end >= "'.encode(AS_DB, $date).'" OR ('.$this->addDatabasePrefix('dates').'.datetime_end="0000-00-00 00:00:00" AND '.$this->addDatabasePrefix('dates').'.datetime_start >= "'.encode(AS_DB, $date).'") )';
       }

       if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.context_id IN ('.implode(', ', $this->_room_array_limit).')';
       } elseif (isset($this->_room_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
       }

       if (true == $this->_delete_limit) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.deleter_id IS NULL';
       }
       if (isset($this->_ref_user_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.creator_id = "'.encode(AS_DB, $this->_ref_user_limit).'"';
       }
       if (isset($this->_age_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
       }
       if (isset($this->_color_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.color = "'.encode(AS_DB, $this->_color_limit).'"';
       }
       if (isset($this->_calendar_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.calendar_id IN ('.implode(', ', $this->_calendar_limit).')';
       }
       if (isset($this->_uid_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.uid IN ('.implode(', ', $this->_uid_limit).')';
       }
       if (isset($this->_recurrence_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.recurrence_id = "'.encode(AS_DB, $this->_recurrence_limit).'"';
       }
       if (isset($this->_existence_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
       }

       // dates restricted by topics, second part
       if (isset($this->_topic_limit) and -1 == $this->_topic_limit) {
           $query .= ' AND l21.first_item_id IS NULL AND l21.second_item_id IS NULL';
       }

       if (isset($this->_institution_limit)) {
           if (-1 == $this->_institution_limit) {
               $query .= ' AND (l121.first_item_id IS NULL AND l121.second_item_id IS NULL)';
               $query .= ' AND (l122.first_item_id IS NULL AND l122.second_item_id IS NULL)';
           } else {
               $query .= ' AND ((l121.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l121.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'")';
               $query .= ' OR (l122.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l122.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'"))';
           }
       }
       if (isset($this->_user_limit)) {
           if (-1 == $this->_user_limit) {
               $query .= ' AND (user_limit1.first_item_id IS NULL AND user_limit1.second_item_id IS NULL)';
               $query .= ' AND (user_limit2.first_item_id IS NULL AND user_limit2.second_item_id IS NULL)';
           } else {
               $query .= ' AND ((user_limit1.first_item_id = "'.encode(AS_DB, $this->_user_limit).'" OR user_limit1.second_item_id = "'.encode(AS_DB, $this->_user_limit).'")';
               $query .= ' OR (user_limit2.first_item_id = "'.encode(AS_DB, $this->_user_limit).'" OR user_limit2.second_item_id = "'.encode(AS_DB, $this->_user_limit).'"))';
           }
       }

       if (isset($this->_assignment_limit) and isset($this->_related_user_limit)) {
           $query .= ' AND ( (related_user_limit1.first_item_id IN ('.implode(', ', $this->_related_user_limit).') OR related_user_limit1.second_item_id IN ('.implode(', ', $this->_related_user_limit).') )';
           $query .= ' OR  (related_user_limit2.first_item_id IN ('.implode(', ', $this->_related_user_limit).') OR related_user_limit2.second_item_id IN ('.implode(', ', $this->_related_user_limit).') ))';
       }

       // dates restricted by groups, second part
       if (isset($this->_group_limit) and -1 == $this->_group_limit) {
           $query .= ' AND l31.first_item_id IS NULL AND l31.second_item_id IS NULL';
       }

       if (isset($this->_tag_limit)) {
           $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
           $id_string = implode(', ', $tag_id_array);
           if (isset($tag_id_array[0]) and -1 == $tag_id_array[0]) {
               $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
               $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
           } else {
               $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB, $id_string).') OR l41.second_item_id IN ('.encode(AS_DB, $id_string).') )';
               $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB, $id_string).') OR l42.second_item_id IN ('.encode(AS_DB, $id_string).') ))';
           }
       }

       if (isset($this->_buzzword_limit)) {
           if (-1 == $this->_buzzword_limit) {
               $query .= ' AND (l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL)';
           } else {
               $query .= ' AND buzzwords.item_id="'.encode(AS_DB, $this->_buzzword_limit).'"';
           }
       }

       if (isset($this->_participant_limit)) {
           $id_string = implode(', ', $this->_participant_limit);

           if (-1 == $this->_participant_limit) {
               $query .= ' AND (participant_limit1.first_item_id IS NULL AND participant_limit1.second_item_id IS NULL)';
               $query .= ' AND (participant_limit2.first_item_id IS NULL AND participant_limit2.second_item_id IS NULL)';
           } else {
               $query .= ' AND ( (participant_limit1.first_item_id IN ('.encode(AS_DB, $id_string).') OR participant_limit1.second_item_id IN ('.encode(AS_DB, $id_string).') )';
               $query .= ' OR (participant_limit2.first_item_id IN ('.encode(AS_DB, $id_string).') OR participant_limit2.second_item_id IN ('.encode(AS_DB, $id_string).') ))';
           }
       }

       if (isset($this->_day_limit)) {
           $query .= ' AND DAYOFMONTH('.$this->addDatabasePrefix('dates').'.start_day) = "'.encode(AS_DB, $this->_day_limit).'"';
       }

       if (isset($this->_month_limit) and isset($this->_year_limit)) {
           $string_start_day = $this->_year_limit.'-'.sprintf('%02d', $this->_month_limit).'-01';
           $string_end_day = $this->_year_limit.'-'.sprintf('%02d', $this->_month_limit).'-'.daysInMonth($this->_month_limit, $this->_year_limit);
           $query .= ' AND ( '.
                  ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                            ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                            ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                     ')';
       } elseif (isset($this->_month_limit2) and isset($this->_year_limit)) {
           $string_start_day = $this->_year_limit.'-'.sprintf('%02d', $this->_month_limit2).'-01';
           $string_end_day = $this->_year_limit.'-'.sprintf('%02d', $this->_month_limit2).'-'.daysInMonth($this->_month_limit2, $this->_year_limit);
           $query .= ' AND ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      '';
           if (1 == $this->_month_limit2) {
               $year = $this->_year_limit - 1;
               $string_start_day = $year.'-'.sprintf('%02d', 12).'-01';
               $string_end_day = $year.'-'.sprintf('%02d', 12).'-'.daysInMonth(12, $year);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
               $string_start_day = $this->_year_limit.'-'.sprintf('%02d', 2).'-01';
               $string_end_day = $this->_year_limit.'-'.sprintf('%02d', 2).'-'.daysInMonth(2, $this->_year_limit);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
           } elseif (12 == $this->_month_limit2) {
               $year = $this->_year_limit + 1;
               $string_start_day = $year.'-'.sprintf('%02d', 1).'-01';
               $string_end_day = $year.'-'.sprintf('%02d', 1).'-'.daysInMonth(1, $year);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
               $string_start_day = $this->_year_limit.'-'.sprintf('%02d', 11).'-01';
               $string_end_day = $this->_year_limit.'-'.sprintf('%02d', 11).'-'.daysInMonth(11, $this->_year_limit);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
           } else {
               $month = $this->_month_limit2 - 1;
               $string_start_day = $this->_year_limit.'-'.sprintf('%02d', $month).'-01';
               $string_end_day = $this->_year_limit.'-'.sprintf('%02d', $month).'-'.daysInMonth($this->_month_limit2, $this->_year_limit);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
               $month = $this->_month_limit2 + 1;
               $string_start_day = $this->_year_limit.'-'.sprintf('%02d', $month).'-01';
               $string_end_day = $this->_year_limit.'-'.sprintf('%02d', $month).'-'.daysInMonth($month, $this->_year_limit);
               $query .= ' OR ( '.
                   ' ('.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_start_day).'" AND "'.encode(AS_DB, $string_end_day).'" <= '.$this->addDatabasePrefix('dates').'.end_day AND ('.$this->addDatabasePrefix('dates').'.end_day IS NOT NULL OR '.$this->addDatabasePrefix('dates').'.end_day !=""))'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.start_day AND '.$this->addDatabasePrefix('dates').'.start_day <="'.encode(AS_DB, $string_end_day).'")'.
                             ' OR ("'.encode(AS_DB, $string_start_day).'"<= '.$this->addDatabasePrefix('dates').'.end_day AND '.$this->addDatabasePrefix('dates').'.end_day <="'.encode(AS_DB, $string_end_day).'")'.
                      ')';
           }
           $query .= ' )';
       }

       if (isset($this->_date_mode_limit)
            and 2 != $this->_date_mode_limit
            and empty($this->_id_array_limit)
       ) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.date_mode="'.encode(AS_DB, $this->_date_mode_limit).'"';
       }

       if (!empty($this->_id_array_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
       }

       // $this->_not_older_than_limit
       if (isset($this->_not_older_than_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.datetime_start > "'.$this->_not_older_than_limit.'"';
       }

       if (isset($this->_between_limit) && !empty($this->_between_limit)) {
           $query .= '
      			AND
      			(
      				(
      					'.$this->addDatabasePrefix($this->_db_table).".datetime_start <= '".$this->_between_limit['start']."' AND
      					".$this->addDatabasePrefix($this->_db_table).".datetime_end >= '".$this->_between_limit['end']."'
      				)
      				OR
      				(
      					".$this->addDatabasePrefix($this->_db_table).".datetime_start <= '".$this->_between_limit['end']."' AND
      					".$this->addDatabasePrefix($this->_db_table).".datetime_end >= '".$this->_between_limit['end']."'
      				)
      				OR
      				(
      					".$this->addDatabasePrefix($this->_db_table).".datetime_start <= '".$this->_between_limit['start']."' AND
      					".$this->addDatabasePrefix($this->_db_table).".datetime_end >= '".$this->_between_limit['start']."'
      				)
      				OR
      				(
      					".$this->addDatabasePrefix($this->_db_table).".datetime_start >= '".$this->_between_limit['start']."' AND
      					".$this->addDatabasePrefix($this->_db_table).".datetime_end <= '".$this->_between_limit['end']."'
      				)
      			)
      		";
       } elseif (isset($this->_from_date_limit) && !empty($this->_from_date_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).".datetime_start >= '".$this->_from_date_limit."'";
       } elseif (isset($this->_until_date_limit) && !empty($this->_until_date_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).".datetime_end <= '".$this->_until_date_limit."'";
       }

       if (!$this->externalLimit) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.external = "0"';
       }

       if ($this->hideRecurringEntriesLimit) {
           $databasePrefix = $this->addDatabasePrefix($this->_db_table);
           $query .= ' AND ('.$databasePrefix.'.recurrence_id IS NULL OR '.$databasePrefix.'.recurrence_id = '.$databasePrefix.'.item_id)';
       }

       if ($this->modificationNewerThenLimit) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
       }

       if ($this->excludedIdsLimit) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
       }

       if (isset($this->_sort_order)) {
           if ('place' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.place ASC';
           } elseif ('place_rev' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.place DESC';
           } elseif ('time' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
           } elseif ('time_rev' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start DESC';
           } elseif ('title' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.title ASC';
           } elseif ('title_rev' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.title DESC';
           } elseif ('date' == $this->_sort_order) {
               $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.modification_date DESC';
           }
       } elseif ($this->_future_limit) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
       } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start DESC';
       }

       if ('select' == $mode) {
           if (isset($this->_interval_limit) and isset($this->_from_limit)) {
               $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
           }
       }

       // perform query
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems selecting dates.', E_USER_WARNING);
       } else {
           return $result;
       }
   }

   /** get a dates in newest version.
    *
    * @param int item_id id of the item
    *
    * @return \cs_dates_item a label
    */
   public function getItem($item_id = null)
   {
       $dates = null;
       if (!is_null($item_id)) {
           if (!empty($this->_cache_object[$item_id])) {
               $dates = $this->_cache_object[$item_id];
           } else {
               $query = 'SELECT * FROM '.$this->addDatabasePrefix('dates').' WHERE '.$this->addDatabasePrefix('dates').".item_id = '".encode(AS_DB, $item_id)."'";
               $result = $this->_db_connector->performQuery($query);
               if (!isset($result)) {
                   trigger_error('Problems selecting one dates item.', E_USER_WARNING);
               } elseif (!empty($result[0])) {
                   $dates = $this->_buildItem($result[0]);
               } else {
                   trigger_error('Dates item ['.$item_id.'] does not exists.', E_USER_WARNING);
               }
           }
       } else {
           $dates = $this->getNewItem();
       }

       return $dates;
   }

   /** Prepares the db_array for the item.
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   public function _buildItem($db_array)
   {
       $db_array['recurrence_pattern'] = unserialize($db_array['recurrence_pattern']);

       return parent::_buildItem($db_array);
   }

   public function getColorArray()
   {
       $color_array = [];
       $query = 'SELECT DISTINCT color FROM '.$this->addDatabasePrefix('dates').' WHERE 1';
       if (isset($this->_room_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('dates').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
       }
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems selecting one dates item.', E_USER_WARNING);
       } else {
           foreach ($result as $rs) {
               if ('NULL' != $rs['color'] and !empty($rs['color'])) {
                   $color_array[] = $rs['color'];
               }
           }
       }

       return $color_array;
   }

   /** get a list of dates items.
    *
    * @param array of item_ids
    *
    * @return cs_list list of cs_dates_item
    *
    * @author CommSy Development Group
    */
   public function getItemList(array $id_array)
   {
       return $this->_getItemList('dates', $id_array);
   }

   /** build a new material item
    * this method returns a new EMTPY material item.
    *
    * @return object cs_item a new EMPTY material
    *
    * @author CommSy Development Group
    */
   public function getNewItem()
   {
       return new cs_dates_item($this->_environment);
   }

    /** update a dates - internal, do not use -> use method save
     * this method updates the database record for a given dates item.
     *
     * @param cs_dates_item the dates item for which an update should be made
     *
     * @author CommSy Development Group
     */
    public function _update($item)
    {
        /* @var cs_dates_item $item */
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
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems updating dates.', E_USER_WARNING);
        }
    }

    /**
     * create a new item in the items table - internal, do not use -> use method save
     * this method creates a new item of type 'ndates' in the database and sets the dates items item id.
     * it then calls the date_mode method _newNews to store the dates item itself.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function _create(cs_dates_item $item)
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
            trigger_error($e->getMessage(), E_USER_WARNING);
            $this->_create_id = null;
        }
    }

    /** store a new dates item to the database - internal, do not use -> use method save
     * this method stores a newly created dates item to the database.
     *
     * @param cs_dates_item the dates item to be stored
     *
     * @author CommSy Development Group
     */
    public function _newDate(cs_dates_item $item)
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
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems creating dates.', E_USER_WARNING);
        }
    }

   /**  delete a dates item.
    *
    * @param cs_dates_item the dates item to be deleted
    *
    * @author CommSy Development Group
    */
   public function delete(int $itemId): void
   {
       $current_datetime = getCurrentDateTimeInMySQL();
       $current_user = $this->_environment->getCurrentUserItem();
       $user_id = $current_user->getItemID() ?: 0;
       $query = 'UPDATE '.$this->addDatabasePrefix('dates').' SET '.
                'deletion_date="'.$current_datetime.'",'.
                'deleter_id="'.encode(AS_DB, $user_id).'"'.
                ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problems deleting dates.', E_USER_WARNING);
       } else {
           $link_manager = $this->_environment->getLinkManager();
           $link_manager->deleteLinksBecauseItemIsDeleted($itemId);
           parent::delete($itemId);
       }
   }

    public function deleteDatesOfUser($uid)
    {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query = 'SELECT '.$this->addDatabasePrefix('dates').'.* FROM '.$this->addDatabasePrefix('dates').' WHERE '.$this->addDatabasePrefix('dates').'.creator_id = "'.encode(AS_DB,
                $uid).'"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE '.$this->addDatabasePrefix('dates').' SET';

                    /* flag */
                    if ('FLAG' === $disableOverwrite) {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                    }

                    /* disabled */
                    if ('FALSE' === $disableOverwrite) {
                        $translator = $this->_environment->getTranslationObject();

                        $updateQuery .= ' title = "'.encode(AS_DB,
                            $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                        $updateQuery .= ' description = "'.encode(AS_DB,
                            $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                        $updateQuery .= ' place = " ",';
                        $updateQuery .= ' modification_date = "'.$currentDatetime.'",';
                        $updateQuery .= ' public = "1"';
                    }

                    $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        trigger_error('Problems automatic deleting dates.', E_USER_WARNING);
                    }
                }
            }
        }
    }

    /**
     * @param int[] $contextIds List of context ids
     * @param array Limits for buzzwords / categories
     * @param int       $size        Number of items to get
     * @param \DateTime $newerThen   The oldest modification date to consider
     * @param int[]     $excludedIds Ids to exclude
     *
     * @return \cs_list
     */
    public function getNewestItems($contextIds, $limits, $size, DateTime $newerThen = null, $excludedIds = [])
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
