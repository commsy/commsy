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

/** class for database connection to the database table "announcement"
 * this class implements a database manager for the table "announcement".
 */
class cs_announcement_manager extends cs_manager
{
    /**
     * integer - containing the age of announcement as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing the id of a institution as a limit for the selected announcement.
     */
    public $_institution_limit = null;

    /**
     * integer - containing the id of a topic as a limit for the selected announcement.
     */
    public $_topic_limit = null;

    /**
     * integer - containing a start point for the select announcements.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many announcements the select statement should get.
     */
    public $_interval_limit = null;

    public $_sort_order = null;

    /**
     * object manager - containing object to the select links for announcement.
     */
    public $_date_limit = null;

    /**
     * @var bool|mixed
     */
    private $hideExpiredLimit = false;

    public $_with_material = false;

    public $_group_limit = null;

    /*
     * Translator Object
     */
    private $_translator = null;

    /** constructor: cs_announcement_manager
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = CS_ANNOUNCEMENT_TYPE;
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_date_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_institution_limit = null;
        $this->_topic_limit = null;
        $this->_sort_order = null;
        $this->_group_limit = null;
        $this->hideExpiredLimit = false;
    }

    /** set date limit
     * this method sets an date limit for announcement.
     *
     * @param date limit date limit for announcement
     */
    public function setDateLimit($datetime)
    {
        $this->_date_limit = (string) $datetime;
    }

      public function setHideExpiredLimit($hideExpired)
      {
          $this->hideExpiredLimit = $hideExpired;
      }

     /** set age limit
      * this method sets an age limit for announcement.
      *
      * @param int limit age limit for announcement
      */
     public function setAgeLimit($limit)
     {
         $this->_age_limit = (int) $limit;
     }

     /** set interval limit
      * this method sets a interval limit.
      *
      * @param int from     from limit for selected announcement
      * @param int interval interval limit for selected announcement
      */
     public function setIntervalLimit($from, $interval)
     {
         $this->_interval_limit = (int) $interval;
         $this->_from_limit = (int) $from;
     }

     public function setTopicLimit($limit)
     {
         $this->_topic_limit = (int) $limit;
     }

     public function setSortOrder($order)
     {
         $this->_sort_order = (string) $order;
     }

     public function setOrder($order)
     {
         $this->_sort_order = (string) $order;
     }

     public function setGroupLimit($limit)
     {
         $this->_group_limit = (int) $limit;
     }

     public function _performQuery($mode = 'select')
     {
         // ------------------
         // --->UTF8 - OK<----
         // ------------------
         if ('count' == $mode) {
             $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
         } elseif ('distinct' == $mode) {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
         } else {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
         }

         if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
             $query .= ', AVG(assessments.assessment) AS assessments_avg';
         }

         $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('announcement').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

         // restrict material by annotations
         if (isset($this->_ref_id_limit)) {
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l5.second_item_id="'.$this->_ref_id_limit.'")
                     OR(l5.second_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l5.first_item_id="'.$this->_ref_id_limit.'") AND l5.deleter_id IS NULL)';
         }

         if (isset($this->_topic_limit)) {
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l31.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l32.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
         }
         if (isset($this->_group_limit)) {
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l41.second_item_type="'.CS_GROUP_TYPE.'"))) ';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('announcement').'.item_id AND l42.first_item_type="'.CS_GROUP_TYPE.'"))) ';
         }
         if (isset($this->_tag_limit)) {
             $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
         }

         // restrict '.$this->_db_table.' by buzzword (la4)
         if (isset($this->_buzzword_limit)) {
             if (-1 == $this->_buzzword_limit) {
                 $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l6.link_type="buzzword_for"';
                 $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
             } else {
                 $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l6.link_type="buzzword_for"';
                 $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
             }
         }

         if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('assessments').' ON '.$this->addDatabasePrefix('announcement').'.item_id=assessments.item_link_id AND assessments.deletion_date IS NULL';
         }

         $query .= ' WHERE 1';

         switch ($this->inactiveEntriesLimit) {
             case self::SHOW_ENTRIES_ONLY_ACTIVATED:
                 $query .= ' AND ('.$this->addDatabasePrefix('announcement').'.activation_date  IS NULL OR '.$this->addDatabasePrefix('announcement').'.activation_date  <= "'.getCurrentDateTimeInMySQL().'")';
                 break;
             case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
                 $query .= ' AND ('.$this->addDatabasePrefix('announcement').'.activation_date  IS NOT NULL AND '.$this->addDatabasePrefix('announcement').'.activation_date  > "'.getCurrentDateTimeInMySQL().'")';
                 break;
         }

         if (isset($this->_topic_limit)) {
             if (-1 == $this->_topic_limit) {
                 $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
                 $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
             } else {
                 $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'")';
                 $query .= ' OR (l32.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l32.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'"))';
             }
         }
         if (isset($this->_institution_limit)) {
             if (-1 == $this->_institution_limit) {
                 $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
                 $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
             } else {
                 $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'")';
                 $query .= ' OR (l22.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l22.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'"))';
             }
         }
         if (isset($this->_group_limit)) {
             if (-1 == $this->_group_limit) {
                 $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
                 $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
             } else {
                 $query .= ' AND ((l41.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l41.second_item_id = "'.encode(AS_DB, $this->_group_limit).'")';
                 $query .= ' OR (l42.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l42.second_item_id = "'.encode(AS_DB, $this->_group_limit).'"))';
             }
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

         if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.context_id IN ('.implode(', ', $this->_room_array_limit).')';
         } elseif (isset($this->_room_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         }

         if (true == $this->_delete_limit) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.deleter_id IS NULL';
         }
         if (isset($this->_ref_user_limit)) {
             $query .= ' AND ('.$this->addDatabasePrefix('announcement').'.creator_id = "'.encode(AS_DB, $this->_ref_user_limit).'" )';
         }
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
         }
         if (isset($this->_existence_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
         }
         if (isset($this->_date_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.creation_date <= '."'".encode(AS_DB, $this->_date_limit)."'".' AND '.$this->addDatabasePrefix('announcement').'.enddate >= '."'".encode(AS_DB, $this->_date_limit)."'".' ';
         }
         if (!empty($this->_id_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
         }

         if ($this->hideExpiredLimit) {
             $query .= ' AND '.$this->addDatabasePrefix('announcement').'.enddate < NOW()';
         }

         if ($this->modificationNewerThenLimit) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
         }

         if ($this->excludedIdsLimit) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
         }

         if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
             $query .= ' GROUP BY '.$this->addDatabasePrefix('announcement').'.item_id';
         }

         if (isset($this->_sort_order)) {
             if ('date' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix('announcement').'.modification_date DESC';
             } elseif ('date_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix('announcement').'.modification_date ASC';
             } elseif ('title' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix('announcement').'.title';
             } elseif ('title_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix('announcement').'.title DESC';
             } elseif ('assessment' == $this->_sort_order) {
                 $query .= ' ORDER BY assessments_avg';
             } elseif ('assessment_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY assessments_avg DESC';
             } elseif ('creator' == $this->_sort_order) {
                 $query .= ' ORDER BY creator.lastname';
             } elseif ('creator_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY creator.lastname DESC';
             } elseif ('modificator' == $this->_sort_order) {
                 $query .= ' ORDER BY modificator.lastname';
             } elseif ('modificator_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY modificator.lastname DESC';
             }
         } else {
             $query .= ' ORDER BY '.$this->addDatabasePrefix('announcement').'.modification_date DESC';
         }

         if ('select' == $mode) {
             if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                 $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
             }
         }
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting announcement.', E_USER_WARNING);
         } else {
             return $result;
         }
     }

     /** get an announcement in latest version.
      *
      * @param int item_id id of the item
      *
      * @return object cs_item a label
      */
     public function getItem(?int $item_id)
     {
         $announcement = null;

         if (!empty($item_id)) {
             $this->_with_material = true;
             if (!empty($this->_cache_object[$item_id])) {
                 return $this->_cache_object[$item_id];
             } elseif (array_key_exists($item_id, $this->_cached_items)) {
                 return $this->_buildItem($this->_cached_items[$item_id]);
             } else {
                 $query = 'SELECT * FROM '.$this->addDatabasePrefix('announcement').' WHERE '.$this->addDatabasePrefix('announcement').".item_id = '".encode(AS_DB, $item_id)."'";
                 $result = $this->_db_connector->performQuery($query);
                 if (!isset($result)) {
                     trigger_error('Problems selecting one announcement item.', E_USER_WARNING);
                 } elseif (!empty($result[0])) {
                     if ($this->_cache_on) {
                         $this->_cached_items[$result[0]['item_id']] = $result[0];
                     }
                     $announcement = $this->_buildItem($result[0]);
                     unset($result);
                 } else {
                     trigger_error('Problems selecting announcement item ['.$item_id.'].', E_USER_WARNING);
                 }
             }
         }

         return $announcement;
     }

     public function getItemList(array $id_array)
     {
         return $this->_getItemList(CS_ANNOUNCEMENT_TYPE, $id_array);
     }

     /** build a new announcement item
      * this method returns a new EMTPY material item.
      *
      * @return object cs_item a new EMPTY material
      *
      * @author CommSy Development Group
      */
     public function getNewItem()
     {
         return new cs_announcement_item($this->_environment);
     }

      /**
       * update an announcement - internal, do not use -> use method save
       * this method updates an announcement.
       *
       * @param cs_announcement_item $announcement_item
       *
       * @author CommSy Development Group
       */
      public function _update($announcement_item)
      {
          parent::_update($announcement_item);

          $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

          $queryBuilder
              ->update($this->addDatabasePrefix('announcement'))
              ->set('modifier_id', ':modifierId')
              ->set('modification_date', ':modificationDate')
              ->set('activation_date', ':activationDate')
              ->set('title', ':title')
              ->set('description', ':description')
              ->set('public', ':public')
              ->set('enddate', ':endDate')
              ->where('item_id = :itemId')
              ->setParameter('title', $announcement_item->getTitle())
              ->setParameter('modifierId', $announcement_item->getModificatorItem()->getItemID())
              ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
              ->setParameter('activationDate', $announcement_item->isNotActivated() ? $announcement_item->getActivatingDate() : null)
              ->setParameter('description', $announcement_item->getDescription())
              ->setParameter('public', $announcement_item->isPublic() ? 1 : 0)
              ->setParameter('endDate', $announcement_item->getSecondDateTime())
              ->setParameter('itemId', $announcement_item->getItemID());

          try {
              $queryBuilder->executeStatement();
          } catch (\Doctrine\DBAL\Exception $e) {
              trigger_error($e->getMessage(), E_USER_WARNING);
          }
      }

      /**
       * create an announcement - internal, do not use -> use method save
       * this method creates an announcement.
       *
       * @throws \Doctrine\DBAL\Exception
       */
      public function _create(cs_announcement_item $announcement_item)
      {
          $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

          $queryBuilder
              ->insert($this->addDatabasePrefix('items'))
              ->setValue('context_id', ':contextId')
              ->setValue('modification_date', ':modificationDate')
              ->setValue('activation_date', ':activationDate')
              ->setValue('type', ':type')
              ->setValue('draft', ':draft')
              ->setParameter('contextId', $announcement_item->getContextID())
              ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
              ->setParameter('activationDate', $announcement_item->isNotActivated() ? $announcement_item->getActivatingDate() : null)
              ->setParameter('type', 'announcement')
              ->setParameter('draft', $announcement_item->isDraft());

          try {
              $queryBuilder->executeStatement();

              $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
              $announcement_item->setItemID($this->getCreateID());
              $this->_newAnnouncement($announcement_item);
          } catch (\Doctrine\DBAL\Exception $e) {
              trigger_error($e->getMessage(), E_USER_WARNING);
              $this->_create_id = null;
          }
      }

    /** creates an new announcement - internal, do not use -> use method save
     * this method creates an new announcement.
     *
     * @param object cs_item announcement_item the announcement
     */
    public function _newAnnouncement(cs_announcement_item $announcement_item)
    {
        $currentDateTime = getCurrentDateTimeInMySQL();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('announcement'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('activation_date', ':activationDate')
            ->setValue('title', ':title')
            ->setValue('enddate', ':endDate')
            ->setValue('public', ':public')
            ->setValue('description', ':description')
            ->setParameter('itemId', $announcement_item->getItemID())
            ->setParameter('contextId', $announcement_item->getContextID())
            ->setParameter('creatorId', $announcement_item->getCreatorItem()->getItemID())
            ->setParameter('creationDate', $currentDateTime)
            ->setParameter('modifierId', $announcement_item->getModificatorItem()->getItemID())
            ->setParameter('modificationDate', $currentDateTime)
            ->setParameter('activationDate', $announcement_item->isNotActivated() ? $announcement_item->getActivatingDate() : null)
            ->setParameter('title', $announcement_item->getTitle())
            ->setParameter('endDate', $announcement_item->getSecondDateTime())
            ->setParameter('public', $announcement_item->isPublic() ? 1 : 0)
            ->setParameter('description', $announcement_item->getDescription());

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems creating announcement.', E_USER_WARNING);
        }
    }

    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user = $this->_environment->getCurrentUser();
        $user_id = $user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('announcement').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting announcement.', E_USER_WARNING);
        } else {
            unset($result);
            $link_manager = $this->_environment->getLinkManager();
            $link_manager->deleteLinks($itemId, 0, 'relevant_for');
            unset($link_manager);
            //  $link_manager->deleteLinksBecauseItemIsDeleted($itemId);  // so wäre es einheitlich
            parent::delete($itemId);
        }
    }

     // #######################################################
     // statistic functions
     // #######################################################

      public function deleteAnnouncementsofUser($uid)
      {
          global $symfonyContainer;
          $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

          if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
              $currentDatetime = getCurrentDateTimeInMySQL();
              $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.* FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.creator_id = "'.encode(AS_DB, $uid).'"';
              $result = $this->_db_connector->performQuery($query);

              if (!empty($result)) {
                  foreach ($result as $rs) {
                      $updateQuery = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET';

                      /* flag */
                      if ('FLAG' === $disableOverwrite) {
                          $updateQuery .= ' public = "-1",';
                      }

                      /* disabled */
                      if ('FALSE' === $disableOverwrite) {
                          $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                          $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                      }

                      $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                      $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                      $result2 = $this->_db_connector->performQuery($updateQuery);
                      if (!$result2) {
                          trigger_error('Problems automatic deleting '.$this->_db_table.'.', E_USER_WARNING);
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

          $this->setOrder('date');

          $this->select();

          return $this->get();
      }
}
