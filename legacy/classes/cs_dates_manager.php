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

use App\Utils\DbalQueryBuilderTrait;
use Doctrine\DBAL\ArrayParameterType;

/** class for database connection to the database table "dates"
 * this class implements a database manager for the table "dates".
 */
class cs_dates_manager extends cs_manager
{
    use DbalQueryBuilderTrait;

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
       $this->_group_limit = null;
       $this->_topic_limit = null;
       $this->_sort_order = null;
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function _performQuery($mode = 'select')
   {
       $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
       $queryBuilder->from('dates', 'd');

       switch ($mode) {
           case 'count':
               $queryBuilder->select('COUNT(d.item_id) AS count');
               break;
           case 'id_array':
               $queryBuilder->select('d.item_id');
               break;
           case 'distinct':
               $queryBuilder->select('d.*');
               $queryBuilder->distinct();
               break;
           default:
               $queryBuilder->select('d.*');
       }

       $queryBuilder->innerJoin('d', 'items', 'i', 'i.item_id = d.item_id');
       $queryBuilder->andWhere('i.draft != 1');

       $this->addTopicLimit($queryBuilder, 'd', $this->_topic_limit);
       $this->addGroupLimit($queryBuilder, 'd', $this->_group_limit);
       $this->addTagLimit($queryBuilder, 'd', $this->_tag_limit);
       $this->addBuzzwordLimit($queryBuilder, 'd', $this->_buzzword_limit);
       $this->addRefIdLimit($queryBuilder, 'd', $this->_ref_id_limit);
       $this->addInactiveEntriesLimit($queryBuilder, 'd', $this->inactiveEntriesLimit);
       $this->addContextLimit($queryBuilder, 'd', $this->_room_array_limit ?? $this->_room_limit);
       $this->addDeleteLimit($queryBuilder, 'd', $this->_delete_limit);
       $this->addCreatorLimit($queryBuilder, 'd', $this->_ref_user_limit);
       $this->addModifiedWithinLimit($queryBuilder, 'd', $this->_age_limit);
       $this->addModifiedAfterLimit($queryBuilder, 'd', $this->modificationNewerThenLimit);
       $this->addCreatedWithinLimit($queryBuilder, 'd', $this->_existence_limit);
       $this->addIdLimit($queryBuilder, 'd', $this->_id_array_limit);
       $this->addNotIdLimit($queryBuilder, 'd', $this->excludedIdsLimit);

       if (isset($this->_user_limit)) {
           $queryBuilder->leftJoin('d', 'link_items', 'user_limit1', 'user_limit1.deletion_date IS NULL AND user_limit1.first_item_id = d.item_id AND user_limit1.second_item_type = "user"');
           $queryBuilder->leftJoin('d', 'link_items', 'user_limit2', 'user_limit2.deletion_date IS NULL AND user_limit2.second_item_id = d.item_id AND user_limit2.first_item_type = "user"');

           if (-1 == $this->_user_limit) {
               $queryBuilder->andWhere('user_limit1.first_item_id IS NULL AND user_limit1.second_item_id IS NULL');
               $queryBuilder->andWhere('user_limit2.first_item_id IS NULL AND user_limit2.second_item_id IS NULL');
           } else {
               $queryBuilder->andWhere(
                   $queryBuilder->expr()->or(
                       'user_limit1.first_item_id = :userLimit OR user_limit1.second_item_id = :userLimit',
                       'user_limit2.first_item_id = :userLimit OR user_limit2.second_item_id = :userLimit'
                   )
               );
               $queryBuilder->setParameter('userLimit', $this->_user_limit);
           }
       }

       if (isset($this->_assignment_limit) && isset($this->_related_user_limit)) {
           $queryBuilder->leftJoin('d', 'link_items', 'related_user_limit1', 'related_user_limit1.deletion_date IS NULL AND related_user_limit1.first_item_id = d.item_id AND related_user_limit1.second_item_type = "user"');
           $queryBuilder->leftJoin('d', 'link_items', 'related_user_limit2', 'related_user_limit2.deletion_date IS NULL AND related_user_limit2.second_item_id = d.item_id AND related_user_limit2.first_item_type = "user"');

           $queryBuilder->andWhere(
               $queryBuilder->expr()->or(
                   $queryBuilder->expr()->or(
                       $queryBuilder->expr()->in('related_user_limit1.first_item_id', ':relatedUserLimit'),
                       $queryBuilder->expr()->in('related_user_limit1.second_item_id', ':relatedUserLimit')
                   ),
                   $queryBuilder->expr()->or(
                       $queryBuilder->expr()->in('related_user_limit2.first_item_id', ':relatedUserLimit'),
                       $queryBuilder->expr()->in('related_user_limit2.second_item_id', ':relatedUserLimit')
                   )
               )
           );
           $queryBuilder->setParameter('relatedUserLimit', $this->_related_user_limit, ArrayParameterType::INTEGER);
       }

       if (isset($this->_participant_limit)) {
           $queryBuilder->leftJoin('d', 'link_items', 'participant_limit1', 'participant_limit1.deletion_date IS NULL AND participant_limit1.first_item_id = d.item_id AND participant_limit1.second_item_type = "user"');
           $queryBuilder->leftJoin('d', 'link_items', 'participant_limit2', 'participant_limit2.deletion_date IS NULL AND participant_limit2.second_item_id = d.item_id AND participant_limit2.first_item_type = "user"');

           if (-1 == $this->_participant_limit) {
               $queryBuilder->andWhere('participant_limit1.first_item_id IS NULL AND participant_limit1.second_item_id IS NULL');
               $queryBuilder->andWhere('participant_limit2.first_item_id IS NULL AND participant_limit2.second_item_id IS NULL');
           } else {
               $queryBuilder->andWhere(
                   $queryBuilder->expr()->or(
                       $queryBuilder->expr()->in('participant_limit1.first_item_id', ':participantLimit'),
                       $queryBuilder->expr()->in('participant_limit1.second_item_id', ':participantLimit'),
                       $queryBuilder->expr()->in('participant_limit2.first_item_id', ':participantLimit'),
                       $queryBuilder->expr()->in('participant_limit2.second_item_id', ':participantLimit'),
                   )
               );
               $queryBuilder->setParameter('participantLimit', $this->_participant_limit, ArrayParameterType::INTEGER);
           }
       }

       if ($this->_future_limit) {
           $queryBuilder->andWhere(
               $queryBuilder->expr()->or(
                   $queryBuilder->expr()->gte('d.datetime_end', 'CURRENT_DATE()'),
                   $queryBuilder->expr()->and(
                       $queryBuilder->expr()->eq('d.datetime_end', ':zeroDate'),
                       $queryBuilder->expr()->gte('d.datetime_start', 'CURRENT_DATE()')
                   )
               )
           );
           $queryBuilder->setParameter('zeroDate', '0000-00-00 00:00:00');
       }

       if (isset($this->_color_limit)) {
           $queryBuilder->andWhere('d.color = :colorLimit');
           $queryBuilder->setParameter('colorLimit', $this->_color_limit);
       }

       if (isset($this->_calendar_limit)) {
           $queryBuilder->andWhere($queryBuilder->expr()->in('d.calendar_id', ':calendarLimit'));
           $queryBuilder->setParameter('calendarLimit', $this->_calendar_limit, ArrayParameterType::INTEGER);
       }

       if (isset($this->_uid_limit)) {
           $queryBuilder->andWhere($queryBuilder->expr()->in('d.uid', ':uidLimit'));
           $queryBuilder->setParameter('uidLimit', $this->_uid_limit, ArrayParameterType::STRING);
       }

       if (isset($this->_recurrence_limit)) {
           $queryBuilder->andWhere('d.recurrence_id = :recurrenceLimit');
           $queryBuilder->setParameter('recurrenceLimit', $this->_recurrence_limit);
       }

       if (isset($this->_day_limit)) {
           $queryBuilder->andWhere('DAYOFMONTH(d.start_day) = :dayLimit');
           $queryBuilder->setParameter('dayLimit', $this->_day_limit);
       }

       if (isset($this->_date_mode_limit) && $this->_date_mode_limit != 2 && empty($this->_id_array_limit)) {
           $queryBuilder->andWhere('d.date_mode = :dateModeLimit');
           $queryBuilder->setParameter('dateModeLimit', $this->_date_mode_limit);
       }

       if (isset($this->_not_older_than_limit)) {
           $queryBuilder->andWhere('d.datetime_start > :startLimit');
           $queryBuilder->setParameter('startLimit', $this->_not_older_than_limit);
       }

       if (!empty($this->_between_limit)) {
           $queryBuilder->andWhere(
               $queryBuilder->expr()->or(
                   $queryBuilder->expr()->and(
                       $queryBuilder->expr()->lte('d.datetime_start', ':startLimit'),
                       $queryBuilder->expr()->gte('d.datetime_end', ':endLimit')
                   ),
                   $queryBuilder->expr()->and(
                       $queryBuilder->expr()->lte('d.datetime_start', ':endLimit'),
                       $queryBuilder->expr()->gte('d.datetime_end', ':endLimit')
                   ),
                   $queryBuilder->expr()->and(
                       $queryBuilder->expr()->lte('d.datetime_start', ':startLimit'),
                       $queryBuilder->expr()->gte('d.datetime_end', ':startLimit')
                   ),
                   $queryBuilder->expr()->and(
                       $queryBuilder->expr()->gte('d.datetime_start', ':startLimit'),
                       $queryBuilder->expr()->lte('d.datetime_end', ':endLimit')
                   )
               )
           );
           $queryBuilder->setParameter('startLimit', $this->_between_limit['start']);
           $queryBuilder->setParameter('endLimit', $this->_between_limit['end']);
       } elseif (!empty($this->_from_date_limit)) {
           $queryBuilder->andWhere('d.datetime_start >= :dateLimit');
           $queryBuilder->setParameter('dateLimit', $this->_from_date_limit);
       } elseif (!empty($this->_until_date_limit)) {
           $queryBuilder->andWhere('d.datetime_end <= :dateLimit');
           $queryBuilder->setParameter('dateLimit', $this->_until_date_limit);
       }

       if (!$this->externalLimit) {
           $queryBuilder->andWhere('d.external = 0');
       }

       if ($this->hideRecurringEntriesLimit) {
           $queryBuilder->andWhere('d.recurrence_id IS NULL OR d.recurrence_id = d.item_id');
       }

       if (isset($this->_sort_order)) {
           if ('place' == $this->_sort_order) {
               $queryBuilder->orderBy('d.place');
           } elseif ('place_rev' == $this->_sort_order) {
               $queryBuilder->orderBy('d.place', 'DESC');
           } elseif ('time' == $this->_sort_order) {
               $queryBuilder->orderBy('d.datetime_start');
           } elseif ('time_rev' == $this->_sort_order) {
               $queryBuilder->orderBy('d.datetime_start', 'DESC');
           } elseif ('title' == $this->_sort_order) {
               $queryBuilder->orderBy('d.title');
           } elseif ('title_rev' == $this->_sort_order) {
               $queryBuilder->orderBy('d.title', 'DESC');
           } elseif ('date' == $this->_sort_order) {
               $queryBuilder->orderBy('d.modification_date', 'DESC');
           }
       } elseif ($this->_future_limit) {
           $queryBuilder->orderBy('d.datetime_start');
       } else {
           $queryBuilder->orderBy('d.datetime_start', 'DESC');
       }

       if (isset($this->_date_limit)) {
           $queryBuilder->andWhere('a.creation_date <= :dateLimit');
           $queryBuilder->andWhere('a.enddate >= :dateLimit');
           $queryBuilder->setParameter('dateLimit', $this->_date_limit);
       }

       return $queryBuilder->fetchAllAssociative();
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
    * @param array $db_array Contains the data from the database
    */
   public function _buildItem(array $db_array)
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
