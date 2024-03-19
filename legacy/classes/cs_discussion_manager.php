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

/** class for database connection to the database table "discussion"
 * this class implements a database manager for the table "discussion".
 */
class cs_discussion_manager extends cs_manager
{
    /**
     * integer - containing the age of discussion as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing a start point for the select discussion.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many discussion the select statement should get.
     */
    public $_interval_limit = null;

    /**
     *  array - containing an id-array as search limit.
     */
    public $_id_array_limit = [];

    public $_group_limit = null;
    public $_topic_limit = null;
    public $_institution_limit = null;
    public $_sort_order = null;

    /*
     * Translation Object
     */
    private cs_translator $_translator;

    /** constructor
     * the only available constructor, initial values for internal variables
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     *
     * @author CommSy Development Group
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'discussions';
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     *
     * @author CommSy Development Group
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_age_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_group_limit = null;
        $this->_topic_limit = null;
        $this->_institution_limit = null;
        $this->_sort_order = null;
    }

    /** set age limit
     * this method sets an age limit for discussion.
     *
     * @param int limit age limit for discussion
     *
     * @author CommSy Development Group
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected discussion
     * @param int interval interval limit for selected discussion
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

    public function getIDs()
    {
        return $this->getIDArray();
    }

    public function _buildItem(array $db_array)
    {
        if (isset($db_array['extras'])) {
            $db_array['extras'] = unserialize($db_array['extras']);
        }

        return parent::_buildItem($db_array);
    }

    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('discussions').'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('discussions').'.item_id';
        } elseif ('distinct' == $mode) {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        } else {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('discussions').'.*';
        }

        if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
            $query .= ', AVG(assessments.assessment) AS assessments_avg';
        }

        $query .= ' FROM '.$this->addDatabasePrefix('discussions');
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('discussions').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

        if (isset($this->_topic_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
        }

        if (isset($this->_group_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
        }

        if (isset($this->_tag_limit)) {
            $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
        }

        // restrict discussions by buzzword (la4)
        if (isset($this->_buzzword_limit)) {
            if (-1 == $this->_buzzword_limit) {
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l6.link_type="buzzword_for"';
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
            } else {
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l6.link_type="buzzword_for"';
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
            }
        }

        // restrict material by discusson
        if (isset($this->_ref_id_limit)) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l5.second_item_id="'.encode(AS_DB, $this->_ref_id_limit).'")
                     OR(l5.second_item_id='.$this->addDatabasePrefix('discussions').'.item_id AND l5.first_item_id="'.encode(AS_DB, $this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
        }

        if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('assessments').' ON '.$this->addDatabasePrefix('discussions').'.item_id=assessments.item_link_id AND assessments.deletion_date IS NULL';
        }

        $query .= ' WHERE 1';

        switch ($this->inactiveEntriesLimit) {
            case self::SHOW_ENTRIES_ONLY_ACTIVATED:
                $query .= ' AND ('.$this->addDatabasePrefix('discussions').'.activation_date  IS NULL OR '.$this->addDatabasePrefix('discussions').'.activation_date  <= "'.getCurrentDateTimeInMySQL().'")';
                break;
            case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
                $query .= ' AND ('.$this->addDatabasePrefix('discussions').'.activation_date  IS NOT NULL AND '.$this->addDatabasePrefix('discussions').'.activation_date  > "'.getCurrentDateTimeInMySQL().'")';
                break;
        }

        // fifth, insert limits into the select statement
        if (!empty($this->_room_array_limit)
             and is_array($this->_room_array_limit)
        ) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.context_id IN ('.encode(AS_DB, implode(',', $this->_room_array_limit)).')';
        } elseif (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        } else {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.deleter_id IS NULL';
        }
        if (isset($this->_ref_user_limit)) {
            $query .= ' AND ('.$this->addDatabasePrefix('discussions').'.creator_id = "'.encode(AS_DB, $this->_ref_user_limit).'" )';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }
        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussions').'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }

        if (isset($this->_topic_limit)) {
            if (-1 == $this->_topic_limit) {
                $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
                $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'")';
                $query .= ' OR (l22.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l22.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'"))';
            }
        }
        if (isset($this->_institution_limit)) {
            if (-1 == $this->_institution_limit) {
                $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
                $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l41.first_item_id = "_institution_limit" OR l41.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'")';
                $query .= ' OR (l42.first_item_id = "_institution_limit" OR l42.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'"))';
            }
        }
        if (isset($this->_group_limit)) {
            if (-1 == $this->_group_limit) {
                $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
                $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_group_limit).'")';
                $query .= ' OR (l32.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB, $this->_group_limit).'"))';
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

        if ($this->modificationNewerThenLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
        }

        if ($this->excludedIdsLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
        }

        if (isset($this->_sort_order) && ('assessment' == $this->_sort_order || 'assessment_rev' == $this->_sort_order)) {
            $query .= ' GROUP BY '.$this->addDatabasePrefix('discussions').'.item_id';
        }

        if (isset($this->_sort_order)) {
            if ('latest' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('discussions').'.modification_date DESC';
            } elseif ('latest_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('discussions').'.latest_article_modification_date, '.$this->addDatabasePrefix('discussions').'.modification_date';
            } elseif ('title' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('discussions').'.title';
            } elseif ('title_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('discussions').'.title DESC';
            } elseif ('assessment' == $this->_sort_order) {
                $query .= ' ORDER BY assessments_avg DESC';
            } elseif ('assessment_rev' == $this->_sort_order) {
                $query .= ' ORDER BY assessments_avg ASC';
            } elseif ('creator' == $this->_sort_order) {
                $query .= ' ORDER BY people.lastname';
            } elseif ('creator_rev' == $this->_sort_order) {
                $query .= ' ORDER BY people.lastname DESC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('discussions').'.modification_date DESC, '.$this->addDatabasePrefix('discussions').'.title DESC';
        }
        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting discussion.     ', E_USER_WARNING);
        } else {
            return $result;
        }
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
        return new cs_discussion_item($this->_environment);
    }

     /** Returns the discussion item of the given item ID.
      *
      * @param int|null itemId ID of the item
      */
     public function getItem(?int $itemId): ?cs_discussion_item
     {
         if (empty($itemId)) {
             return null;
         } elseif (!empty($this->_cache_object[$itemId])) {
             return $this->_cache_object[$itemId];
         } elseif (array_key_exists($itemId, $this->_cached_items)) {
             return $this->_buildItem($this->_cached_items[$itemId]);
         }

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
         $queryBuilder
             ->select('d.*', 'i.pinned')
             ->from($this->addDatabasePrefix($this->_db_table), 'd')
             ->innerJoin('d', 'items', 'i', 'i.item_id = d.item_id')
             ->where('d.item_id = :itemId')
             ->setParameter('itemId', $itemId);

         try {
             $result = $queryBuilder->executeQuery()->fetchAllAssociative();
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error('Problems selecting discussions item (' . $itemId . '): ' . $e->getMessage(), E_USER_WARNING);
         }

         $discussion = null;
         if (!empty($result[0])) {
             $discussion = $this->_buildItem($result[0]);
             if ($this->_cache_on) {
                 $this->_cached_items[$itemId] = $result[0];
             }
         }

         return $discussion;
     }

    public function getItemList(array $id_array)
    {
        return $this->_getItemList('discussion', $id_array);
    }

     /** update a discussion - internal, do not use -> use method save
      * this method updates the database record for a given discussion item.
      *
      * @param cs_discussion_item the discussion item for which an update should be made
      */
     public function _update($item)
     {
         parent::_update($item);
         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->update($this->addDatabasePrefix('discussions'))
             ->set('modifier_id', ':modifierId')
             ->set('modification_date', ':modificationDate')
             ->set('activation_date', ':activationDate')
             ->set('title', ':title')
             ->set('description', ':description')
             ->set('extras', ':extras')
             ->set('status', ':status')
             ->set('discussion_type', ':discussionType')
             ->set('public', ':public')
             ->where('item_id = :itemId')
             ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
             ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
             ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
             ->setParameter('title', $item->getTitle())
             ->setParameter('description', $item->getDescription())
             ->setParameter('extras', serialize($item->getExtraInformation()))
             ->setParameter('status', $item->getDiscussionStatus() ?: '1')
             ->setParameter('discussionType', $item->getDiscussionType() ?: 'simple')
             ->setParameter('public', $item->isPublic() ? 1 : 0)
             ->setParameter('itemId', $item->getItemID());

         $articleId = $item->getLatestArticleID();
         if (!empty($articleId)) {
             $queryBuilder
                 ->set('latest_article_item_id', ':latestArticleItemId')
                 ->setParameter('latestArticleItemId', $articleId);
         }

         $articleModificationDate = $item->getLatestArticleModificationDate();
         if (!empty($articleModificationDate)) {
             $queryBuilder
                 ->set('latest_article_modification_date', ':latestArticleModificationDate')
                 ->setParameter('latestArticleModificationDate', $articleModificationDate);
         }

         try {
             $queryBuilder->executeStatement();
         } catch (\Doctrine\DBAL\Exception) {
             trigger_error('Problems updating discussion.', E_USER_WARNING);
         }
     }

     /**
      * create a new item in the items table - internal, do not use -> use method save
      * this method creates a new item of type 'ndiscussion' in the database and sets the discussion items item id.
      * it then calls the private method _newNews to store the discussion item itself.
      *
      * @param cs_discussion_item the discussion item for which an entry should be made
      */
     public function _create(cs_discussion_item $item)
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
             ->setParameter('type', 'discussion')
             ->setParameter('draft', $item->isDraft());

         try {
             $queryBuilder->executeStatement();

             $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
             $item->setItemID($this->getCreateID());
             $this->_newDiscussion($item);
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
             $this->_create_id = null;
         }
     }

  /** store a new discussion item to the database - internal, do not use -> use method save
   * this method stores a newly created discussion item to the database.
   */
  public function _newDiscussion(cs_discussion_item $item)
  {
      $currentDateTime = getCurrentDateTimeInMySQL();

      $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

      $queryBuilder
          ->insert($this->addDatabasePrefix('discussions'))
          ->setValue('item_id', ':itemId')
          ->setValue('context_id', ':contextId')
          ->setValue('creator_id', ':creatorId')
          ->setValue('creation_date', ':creationDate')
          ->setValue('modifier_id', ':modifierId')
          ->setValue('modification_date', ':modificationDate')
          ->setValue('activation_date', ':activationDate')
          ->setValue('title', ':title')
          ->setValue('description', ':description')
          ->setValue('discussion_type', ':discussionType')
          ->setValue('public', ':public')
          ->setParameter('itemId', $item->getItemID())
          ->setParameter('contextId', $item->getContextID())
          ->setParameter('creatorId', $item->getCreatorItem()->getItemID())
          ->setParameter('creationDate', $currentDateTime)
          ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
          ->setParameter('modificationDate', $currentDateTime)
          ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
          ->setParameter('title', $item->getTitle())
          ->setParameter('description', $item->getDescription())
          ->setParameter('discussionType', $item->getDiscussionType() ?: 'simple')
          ->setParameter('public', $item->isPublic() ? 1 : 0);

      $articleId = $item->getLatestArticleID();
      if (!empty($articleId)) {
          $queryBuilder
              ->setValue('latest_article_item_id', ':latestArticleItemId')
              ->setParameter('latestArticleItemId', $articleId);
      }

      $articleModificationDate = $item->getLatestArticleModificationDate();
      if (!empty($articleModificationDate)) {
          $queryBuilder
              ->setValue('latest_article_modification_date', ':latestArticleModificationDate')
              ->setParameter('latestArticleModificationDate', $articleModificationDate);
      }

      try {
          $queryBuilder->executeStatement();
      } catch (\Doctrine\DBAL\Exception) {
          trigger_error('Problems creating dates.', E_USER_WARNING);
      }
  }

    /**  delete a discussion item.
     *
     * @param cs_discussion_item the discussion item to be deleted
     */
    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('discussions').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting discussion.', E_USER_WARNING);
        } else {
            $link_manager = $this->_environment->getLinkManager();
            $link_manager->deleteLinksBecauseItemIsDeleted($itemId);
            parent::delete($itemId);
        }
    }

    // #######################################################
    // statistic functions
    // #######################################################

     public function deleteDiscussionsOfUser($uid)
     {
         global $symfonyContainer;
         $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

         if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
             $currentDatetime = getCurrentDateTimeInMySQL();
             $query = 'SELECT '.$this->addDatabasePrefix('discussions').'.* FROM '.$this->addDatabasePrefix('discussions').' WHERE '.$this->addDatabasePrefix('discussions').'.creator_id = "'.encode(AS_DB, $uid).'"';
             $result = $this->_db_connector->performQuery($query);

             if (!empty($result)) {
                 foreach ($result as $rs) {
                     $updateQuery = 'UPDATE '.$this->addDatabasePrefix('discussions').' SET';

                     /* flag */
                     if ('FLAG' === $disableOverwrite) {
                         $updateQuery .= ' public = "-1",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                     }

                     /* disabled */
                     if ('FALSE' === $disableOverwrite) {
                         $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'",';
                         $updateQuery .= ' public = "1"';
                     }

                     $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                     $result2 = $this->_db_connector->performQuery($updateQuery);
                     if (!$result2) {
                         trigger_error('Problems automatic deleting discussions.', E_USER_WARNING);
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
     public function getNewestItems($contextIds, $limits, $size, DateTime $newerThen = null, $excludedIds = [])
     {
         parent::setGenericNewestItemsLimits($contextIds, $limits, $newerThen, $excludedIds);

         if ($size > 0) {
             $this->setIntervalLimit(0, $size);
         }

         $this->setSortOrder('latest');

         $this->select();

         return $this->get();
     }
}
