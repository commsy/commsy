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

/** class for database connection to the database table "announcement"
 * this class implements a database manager for the table "announcement".
 */
class cs_announcement_manager extends cs_manager
{
    use DbalQueryBuilderTrait;

    /**
     * integer - containing the age of announcement as a limit.
     */
    public $_age_limit = null;

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
        $this->_date_limit = (string)$datetime;
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
        $this->_age_limit = (int)$limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected announcement
     * @param int interval interval limit for selected announcement
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int)$interval;
        $this->_from_limit = (int)$from;
    }

    public function setTopicLimit($limit)
    {
        $this->_topic_limit = (int)$limit;
    }

    public function setSortOrder($order)
    {
        $this->_sort_order = (string)$order;
    }

    public function setOrder($order)
    {
        $this->_sort_order = (string)$order;
    }

    public function setGroupLimit($limit)
    {
        $this->_group_limit = (int)$limit;
    }

    public function _performQuery($mode = 'select')
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder->from('announcement', 'a');

        switch ($mode) {
            case 'count':
                $queryBuilder->select('COUNT(a.item_id) AS count');
                break;
            case 'id_array':
                $queryBuilder->select('a.item_id');
                break;
            case 'distinct':
                $queryBuilder->select('a.*');
                $queryBuilder->distinct();
                break;
            default:
                $queryBuilder->select('a.*');
        }

        $queryBuilder->innerJoin('a', 'items', 'i', 'i.item_id = a.item_id');
        $queryBuilder->andWhere('i.draft != 1');

        if (isset($this->_sort_order) && in_array($this->_sort_order, ['creator', 'creator_rev', 'modificator', 'modificator_rev'])) {
            $queryBuilder->innerJoin('a', 'user', 'creator', 'a.creator_id = creator.item_id');
            $queryBuilder->innerJoin('a', 'user', 'modificator', 'a.modifier_id = modificator.item_id');
        }

        if (isset($this->_sort_order) && in_array($this->_sort_order, ['assessment', 'assessment_rev'])) {
            $queryBuilder->addSelect('AVG(as.assessment) AS assessments_avg');
            $queryBuilder->leftJoin('a', 'assessments', 'as', 'a.item_id = as.item_link_id AND as.deletion_date IS NULL');
            $queryBuilder->addGroupBy('a.item_id');
        }

        $this->addTopicLimit($queryBuilder, 'a', $this->_topic_limit);
        $this->addGroupLimit($queryBuilder, 'a', $this->_group_limit);
        $this->addTagLimit($queryBuilder, 'a', $this->_getTagIDArrayByTagIDArray($this->_tag_limit));
        $this->addBuzzwordLimit($queryBuilder, 'a', $this->_buzzword_limit);
        $this->addRefIdLimit($queryBuilder, 'a', $this->_ref_id_limit);
        $this->addInactiveEntriesLimit($queryBuilder, 'a', $this->inactiveEntriesLimit);
        $this->addContextLimit($queryBuilder, 'a', $this->_room_array_limit ?? $this->_room_limit);
        $this->addDeleteLimit($queryBuilder, 'a', $this->_delete_limit);
        $this->addCreatorLimit($queryBuilder, 'a', $this->_ref_user_limit);
        $this->addModifiedWithinLimit($queryBuilder, 'a', $this->_age_limit);
        $this->addModifiedAfterLimit($queryBuilder, 'a', $this->modificationNewerThenLimit);
        $this->addCreatedWithinLimit($queryBuilder, 'a', $this->_existence_limit);
        $this->addIdLimit($queryBuilder, 'a', $this->_id_array_limit);
        $this->addNotIdLimit($queryBuilder, 'a', $this->excludedIdsLimit);

        if (isset($this->_date_limit)) {
            $queryBuilder->andWhere('a.creation_date <= :dateLimit');
            $queryBuilder->andWhere('a.enddate >= :dateLimit');
            $queryBuilder->setParameter('dateLimit', $this->_date_limit);
        }

        if ($this->hideExpiredLimit) {
            $queryBuilder->andWhere('a.enddate < NOW()');
        }

        if (isset($this->_sort_order)) {
            if ('date' == $this->_sort_order) {
                $queryBuilder->orderBy('a.modification_date', 'DESC');
            } elseif ('date_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('a.modification_date');
            } elseif ('title' == $this->_sort_order) {
                $queryBuilder->orderBy('a.title');
            } elseif ('title_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('a.title', 'DESC');
            } elseif ('assessment' == $this->_sort_order) {
                $queryBuilder->orderBy('assessments_avg');
            } elseif ('assessment_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('assessments_avg', 'DESC');
            } elseif ('creator' == $this->_sort_order) {
                $queryBuilder->orderBy('creator.lastname');
            } elseif ('creator_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('creator.lastname', 'DESC');
            } elseif ('modificator' == $this->_sort_order) {
                $queryBuilder->orderBy('modificator.lastname');
            } elseif ('modificator_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('modificator.lastname', 'DESC');
            }
        } else {
            $queryBuilder->orderBy('a.modification_date', 'DESC');
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) && isset($this->_from_limit)) {
                $queryBuilder->setFirstResult($this->_from_limit);
                $queryBuilder->setMaxResults($this->_interval_limit);
            }
        }

        return $queryBuilder->fetchAllAssociative();
    }

     /** Returns the announcement item of the given item ID.
      *
      * @param int|null itemId ID of the item
     */
     public function getItem(?int $itemId): ?cs_announcement_item
    {
         if (empty($itemId)) {
             return null;
         } else {
             $this->_with_material = true;
             if (!empty($this->_cache_object[$itemId])) {
                 return $this->_cache_object[$itemId];
             } elseif (array_key_exists($itemId, $this->_cached_items)) {
                 return $this->_buildItem($this->_cached_items[$itemId]);
             }
         }

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
         $queryBuilder
             ->select('a.*', 'i.pinned')
             ->from($this->addDatabasePrefix($this->_db_table), 'a')
             ->innerJoin('a', 'items', 'i', 'i.item_id = a.item_id')
             ->where('a.item_id = :itemId')
             ->setParameter('itemId', $itemId);

         try {
             $result = $queryBuilder->executeQuery()->fetchAllAssociative();
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error('Problems selecting announcement item (' . $itemId . '): ' . $e->getMessage(), E_USER_WARNING);
         }

        $announcement = null;
         if (!empty($result[0])) {
             $announcement = $this->_buildItem($result[0]);
                    if ($this->_cache_on) {
                        $this->_cached_items[$result[0]['item_id']] = $result[0];
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
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems creating announcement.', E_USER_WARNING);
        }
    }

    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user = $this->_environment->getCurrentUser();
        $user_id = $user->getItemID() ?: 0;
        $query = 'UPDATE ' . $this->addDatabasePrefix('announcement') . ' SET ' .
            'deletion_date="' . $current_datetime . '",' .
            'deleter_id="' . encode(AS_DB, $user_id) . '"' .
            ' WHERE item_id="' . encode(AS_DB, $itemId) . '"';
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
            $query = 'SELECT ' . $this->addDatabasePrefix($this->_db_table) . '.* FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE ' . $this->addDatabasePrefix($this->_db_table) . '.creator_id = "' . encode(AS_DB, $uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET';

                    /* flag */
                    if ('FLAG' === $disableOverwrite) {
                        $updateQuery .= ' public = "-1",';
                    }

                    /* disabled */
                    if ('FALSE' === $disableOverwrite) {
                        $updateQuery .= ' title = "' . encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                    }

                    $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB, $rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        trigger_error('Problems automatic deleting ' . $this->_db_table . '.', E_USER_WARNING);
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

        $this->setOrder('date');

        $this->select();

        return $this->get();
    }
}
