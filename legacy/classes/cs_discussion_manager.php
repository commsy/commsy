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

/** class for database connection to the database table "discussion"
 * this class implements a database manager for the table "discussion".
 */
class cs_discussion_manager extends cs_manager
{
    use DbalQueryBuilderTrait;

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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function _performQuery($mode = 'select')
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder->from('discussions', 'd');

        switch ($mode) {
            case 'count':
                $queryBuilder->select('COUNT(DISTINCT d.item_id) AS count');
                break;
            case 'id_array':
                $queryBuilder->select('d.item_id');
                $queryBuilder->distinct();
                break;
            case 'distinct':
                $queryBuilder->select('d.*');
                $queryBuilder->distinct();
                break;
            default:
                $queryBuilder->select('d.*');
                $queryBuilder->distinct();
        }

        if (isset($this->_sort_order) && in_array($this->_sort_order, ['assessment', 'assessment_rev'])) {
            $queryBuilder->addSelect('AVG(a.assessment) AS assessments_avg');
            $queryBuilder->leftJoin('d', 'assessments', 'a', 'd.item_id = a.item_link_id AND a.deletion_date IS NULL');
            $queryBuilder->addGroupBy('d.item_id');
        }

        $queryBuilder->innerJoin('d', 'items', 'i', 'i.item_id = d.item_id');
        $queryBuilder->andWhere('i.draft != 1');

        if (isset($this->_sort_order)) {
            $queryBuilder->leftJoin('d', 'user', 'people', 'people.item_id = d.creator_id');
        }

        $this->addTopicLimit($queryBuilder, 'd', $this->_topic_limit);
        $this->addGroupLimit($queryBuilder, 'd', $this->_group_limit);
        $this->addTagLimit($queryBuilder, 'd', $this->_getTagIDArrayByTagIDArray($this->_tag_limit));
        $this->addBuzzwordLimit($queryBuilder, 'd', $this->_buzzword_limit);
        $this->addRefIdLimit($queryBuilder, 'd', $this->_ref_id_limit);
        $this->addInactiveEntriesLimit($queryBuilder, 'd', $this->inactiveEntriesLimit);
        $this->addDeleteLimit($queryBuilder, 'd', $this->_delete_limit);
        $this->addCreatorLimit($queryBuilder, 'd', $this->_ref_user_limit);
        $this->addModifiedWithinLimit($queryBuilder, 'd', $this->_age_limit);
        $this->addModifiedAfterLimit($queryBuilder, 'd', $this->modificationNewerThenLimit);
        $this->addCreatedWithinLimit($queryBuilder, 'd', $this->_existence_limit);
        $this->addIdLimit($queryBuilder, 'd', $this->_id_array_limit);
        $this->addNotIdLimit($queryBuilder, 'd', $this->excludedIdsLimit);

        // room limit
        if (!empty($this->_room_array_limit) && is_array($this->_room_array_limit)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('d.context_id', ':roomArrayLimit'));
            $queryBuilder->setParameter('roomArrayLimit', $this->_room_array_limit, ArrayParameterType::INTEGER);
        } elseif (isset($this->_room_limit)) {
            $queryBuilder->andWhere('d.context_id = :roomLimit');
            $queryBuilder->setParameter('roomLimit', $this->_room_limit);
        } else {
            $queryBuilder->andWhere('d.context_id = :roomLimit');
            $queryBuilder->setParameter('roomLimit', $this->_environment->getCurrentContextID());
        }

        if (isset($this->_sort_order)) {
            if ('latest' == $this->_sort_order) {
                $queryBuilder->orderBy('d.modification_date', 'DESC');
            } elseif ('latest_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('d.latest_article_modification_date');
                $queryBuilder->addOrderBy('d.modification_date');
            } elseif ('title' == $this->_sort_order) {
                $queryBuilder->orderBy('d.title');
            } elseif ('title_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('d.title', 'DESC');
            } elseif ('assessment' == $this->_sort_order) {
                $queryBuilder->orderBy('assessments_avg', 'DESC');
            } elseif ('assessment_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('assessments_avg');
            } elseif ('creator' == $this->_sort_order) {
                $queryBuilder->orderBy('people.lastname');
            } elseif ('creator_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('people.lastname', 'DESC');
            }
        } else {
            $queryBuilder->orderBy('d.modification_date', 'DESC');
            $queryBuilder->addOrderBy('d.title', 'DESC');
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) && isset($this->_from_limit)) {
                $queryBuilder->setFirstResult($this->_from_limit);
                $queryBuilder->setMaxResults($this->_interval_limit);
            }
        }

        return $queryBuilder->fetchAllAssociative();
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

     /** get a discussion in newest version.
      *
      * @param int item_id id of the item
      *
      * @return cs_discussion_item|null cs_item a label
      *
      * @throws \Doctrine\DBAL\Exception
      */
     public function getItem(?int $item_id): ?cs_discussion_item
     {
         $discussion = null;
         if (!empty($item_id)
             and !empty($this->_cache_object[$item_id])
         ) {
             return $this->_cache_object[$item_id];
         } elseif (array_key_exists($item_id, $this->_cached_items)) {
             return $this->_buildItem($this->_cached_items[$item_id]);
         } elseif (!empty($item_id)) {
             $query = 'SELECT * FROM '.$this->addDatabasePrefix('discussions').' WHERE '.$this->addDatabasePrefix('discussions').".item_id = '".encode(AS_DB,
                 $item_id)."'";
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result)) {
                 trigger_error('Problems selecting one discussions item ('.$item_id.').', E_USER_WARNING);
             } elseif (!empty($result[0])) {
                 $discussion = $this->_buildItem($result[0]);
                 if ($this->_cache_on) {
                     $this->_cached_items[$item_id] = $result[0];
                 }
             }

             return $discussion;
         } else {
             return null;
         }
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
            parent::delete($itemId);
        }
    }

    public function deleteReallyOlderThan(int $days): void
    {
        $conn = $this->_db_connector->getConnection();

        // It's possible that there are discussion articles that are not yet deleted, even if the discussion itself
        // is already deleted. It would be preferred to enforce this on database level in the future.
        $qb = $conn->createQueryBuilder();
        $qb
            ->select('item_id')
            ->from($this->_db_table, 't')
            ->where('t.deletion_date < DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)')
            ->setParameter('days', $days)
            ->executeQuery();
        $results = $qb->fetchAllAssociative();
        $discussionIds = array_map(fn ($result) => $result['item_id'], $results);

        $conn->executeStatement('DELETE FROM discussionarticles WHERE discussion_id IN (?)',
            [$discussionIds],
            [ArrayParameterType::INTEGER]
        );

        // call parent implementation to delete discussions
        parent::deleteReallyOlderThan($days);
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
