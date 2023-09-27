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

/** class for database connection to the database table "discussionarticles"
 * this class implements a database manager for the table "discussionarticles".
 */
class cs_discussionarticles_manager extends cs_manager
{
    /**
     * integer - containing the age of the discussionarticle as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing the id of a discussion as a limit for the selected discussionarticles.
     */
    public $_discussion_limit = null;

    /**
     * integer - containing a start point for the selected discussionarticles.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many discussions the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - containing an order limit for the selected discussion.
     */
    public $_order = null;

    /**
     * integer - containing the item id of the current article.
     */
    public $_current_article_id = null;

    /**
     * string - containing the modification date of the current article.
     */
    public $_current_article_modification_date = null;

    public $_sort_position = false;

    public $_all_discarticle_list = null;
    public $_cached_discussion_item_ids = [];

    /*
     * Translation Object
     */
    private readonly cs_translator $_translator;

    /** constructor: cs_discussionarticles_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment cs_environment the environment
     */
    public function __construct(cs_environment $environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'discussionarticles';
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_age_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_order = null;
        $this->_sort_position = false;
    }

    /** set age limit
     * this method sets an age limit for discussionarticles.
     *
     * @param int limit age limit for discussionarticles
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set discussion limit
     * this method sets an discussion limit for discussionarticles.
     *
     * @param int limit discussion limit for discussionarticles
     */
    public function setDiscussionLimit($discussion)
    {
        $this->_discussion_limit = (int) $discussion;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected discussionarticles
     * @param int interval interval limit for selected discussionarticles
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected discussionarticles
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    public function setSortPosition()
    {
        $this->_sort_position = true;
    }

    public function setRoomLimit($limit)
    {
        $this->_room_limit = (string) $limit;
    }

    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix('discussionarticles').'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix('discussionarticles').'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix('discussionarticles').'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix('discussionarticles');
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('discussionarticles').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

        $query .= ' WHERE 1';

        // fifth, insert limits into the select statement
        if (isset($this->_room_limit) and 'clipboard_index' != $this->_environment->getCurrentFunction()) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.deleter_id IS NULL';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }
        if (isset($this->_typ_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.type = "'.encode(AS_DB, $this->_typ_limit).'"';
        }
        if (isset($this->_discussion_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.discussion_id = "'.encode(AS_DB, $this->_discussion_limit).'"';
        }
        if (isset($this->_group_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('links').'.to_item_id="'.encode(AS_DB, $this->_group_limit).'" AND '.$this->addDatabasePrefix('links').'.link_type="relevant_for"';
        }

        if ($this->_sort_position) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('discussionarticles').'.position DESC';
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('discussionarticles').'.creation_date ASC, '.$this->addDatabasePrefix('discussionarticles').'.item_id ASC';
        }
        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting discarticles.', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    /**
     * @param int item_id id of the item
     *
     * @return cs_discussionarticle_item|null cs_item a discussionarticle
     */
    public function getItem($itemId): ?cs_discussionarticle_item
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select('d.*')
            ->from($this->_db_table, 'd')
            ->where('d.item_id = :itemId')
            ->setParameter('itemId', $itemId);

        try {
            $result = $this->_db_connector->performQuery($queryBuilder->getSQL(),
                $queryBuilder->getParameters());

            if ($result) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $this->_buildItem($result[0]);
            }
        } catch (Exception) {
        }

        return null;
    }

    /** get a list of items (newest version)
     * this method returns a list of items.
     *
     * @param array id_array ids of the items items
     *
     * @return cs_list list of cs_items
     */
    public function getItemList(array $id_array)
    {
        return $this->_getItemList('discussionarticles', $id_array);
    }

    /** build a new discussionarticle item
     * this method returns a new EMTPY material item.
     *
     * @return object cs_item a new EMPTY material
     */
    public function getNewItem()
    {
        return new cs_discussionarticle_item($this->_environment);
    }

    public function getAllArticlesForItem($discussion_item, $show_all = false): ?cs_list
    {
        $item_id = $discussion_item->getItemID();
        if (in_array($item_id, $this->_cached_discussion_item_ids)) {
            $list = new cs_list();
            foreach ($this->_all_discarticle_list as $discArticleItem) {
                /** @var cs_discussionarticle_item $discArticleItem */
                if ($item_id == $discArticleItem->getDiscussionID()) {
                    $list->add($discArticleItem);
                }
            }

            return $list;
        } else {
            $this->reset();
            $this->setContextLimit($discussion_item->getContextID());
            $this->setDiscussionLimit($discussion_item->getItemID());
            $this->setSortPosition();
            if (true == $show_all) {
                $this->setDeleteLimit(false);
            }
            $this->select();

            return $this->get();
        }
    }

    /**
     * Returns the parent article for the given discussion article, i.e. the article to which the given article is an answer.
     *
     * @param cs_discussionarticle_item $item The discussion article whose parent article shall be returned
     *
     * @return cs_discussionarticle_item|null Parent discussion article for the given article, or null if it has no parent
     */
    public function getParentForDiscArticle(cs_discussionarticle_item $item): ?cs_discussionarticle_item
    {
        // to get the parent's position, remove the trailing position element from the given item's position
        $itemPosition = $item->getPosition();
        $parentPosition = implode('.', explode('.', (string) $itemPosition, -1));
        if (empty($parentPosition)) {
            return null;
        }

        /** @var cs_discussionarticle_item $parentArticle */
        $parentArticle = null;
        $dbPrefix = $this->addDatabasePrefix($this->_db_table);
        $dbPrefixItems = $this->addDatabasePrefix('items');

        $query = 'SELECT * FROM '.$dbPrefix;
        $query .= ' INNER JOIN '.$dbPrefixItems.' ON '.$dbPrefixItems.'.item_id = '.$dbPrefix.'.item_id AND '.$dbPrefixItems.'.draft != "1"';
        $query .= ' WHERE discussion_id="'.encode(AS_DB, $item->getDiscussionID()).'"';
        $query .= ' AND position="'.encode(AS_DB, $parentPosition).'"';
        $query .= ' AND '.$dbPrefix.'.deleter_id IS NULL';
        $query .= ' AND '.$dbPrefix.'.deletion_date IS NULL';

        $result = $this->_db_connector->performQuery($query);
        if (!$result) {
            trigger_error('Problems selecting parent of discarticle with ID '.$item->getItemID().'.', E_USER_WARNING);
        } else {
            $parentArticle = $this->_buildItem($result[0]);
        }

        return $parentArticle;
    }

    /**
     * Returns all child article(s) (aka "answers") for the given discussion article.
     *
     * @param cs_discussionarticle_item $item The discussion article whose children shall be returned

     */
    public function getChildrenForDiscArticle(cs_discussionarticle_item $item): cs_list
    {
        $childrenList = new cs_list();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select('d.*')
            ->from($this->_db_table, 'd')
            ->innerJoin('d', 'items', 'i', 'i.item_id = d.item_id')
            ->andWhere('i.draft != 1')
            ->andWhere('d.discussion_id = :discussionId')
            ->andWhere('d.position LIKE :position')
            ->andWhere('d.deleter_id IS NULL')
            ->andWhere('d.deletion_date IS NULL')
            ->setParameter('discussionId', $item->getDiscussionID())
            ->setParameter('position', "{$item->getPosition()}.%");

        try {
            $result = $this->_db_connector->performQuery($queryBuilder->getSQL(),
                $queryBuilder->getParameters());

            if ($result) {
                foreach ($result as $rs) {
                    $childrenList->add($this->_buildItem($rs));
                }
            }
        } catch (Exception) {
        }

        return $childrenList;
    }

    /**
     * update a discussion - internal, do not use -> use method save
     * this method updates a discussion.
     *
     * @param cs_discussionarticle_item $discussionarticle_item
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function _update($discussionarticle_item)
    {
        /* @var cs_discussionarticle_item $discussionarticle_item */
        if ($this->_update_with_changing_modification_information) {
            parent::_update($discussionarticle_item);
        }

        $this->_current_article_modification_date = getCurrentDateTimeInMySQL();
        $this->_current_article_id = $discussionarticle_item->getItemID();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update($this->addDatabasePrefix('discussionarticles'))
            ->set('position', ':position')
            ->set('description', ':description')
            ->set('public', ':public')
            ->where('item_id = :itemId')
            ->setParameter('position', $discussionarticle_item->getPosition())
            ->setParameter('description', $discussionarticle_item->getDescription())
            ->setParameter('public', 0)
            ->setParameter('itemId', $discussionarticle_item->getItemID());

        if ($this->_update_with_changing_modification_information) {
            $queryBuilder
                ->set('modifier_id', ':modifierId')
                ->set('modification_date', ':modificationDate')
                ->setParameter('modifierId', $this->_current_user->getItemID())
                ->setParameter('modificationDate', $this->_current_article_modification_date);
        }

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * create a discussionarticle - internal, do not use -> use method save
     * this method creates a discussionarticle.
     */
    public function _create(cs_discussionarticle_item $discussionarticle_item)
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('items'))
            ->setValue('context_id', ':contextId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('type', ':type')
            ->setValue('draft', ':draft')
            ->setParameter('contextId', $discussionarticle_item->getContextID())
            ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
            ->setParameter('type', CS_DISCARTICLE_TYPE)
            ->setParameter('draft', $discussionarticle_item->isDraft());

        try {
            $queryBuilder->executeStatement();

            $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
            $this->_current_article_id = $this->_create_id;
            $discussionarticle_item->setItemID($this->getCreateID());
            $this->_newDiscussionArticle($discussionarticle_item);
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems creating discussionarticle item.', E_USER_WARNING);
            $this->_create_id = null;
        }
    }

    /**
     * creates a new discarticlearticle - internal, do not use -> use method save
     * this method creates a new discarticlearticle.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function _newDiscussionArticle(cs_discussionarticle_item $discussionarticle_item)
    {
        $currentDateTime = getCurrentDateTimeInMySQL();
        $this->_current_article_modification_date = $currentDateTime;
        $modificator = $discussionarticle_item->getModificatorItem();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('discussionarticles'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('discussion_id', ':discussionId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('position', ':position')
            ->setValue('description', ':description')
            ->setValue('public', ':public')
            ->setParameter('itemId', $discussionarticle_item->getItemID())
            ->setParameter('contextId', $discussionarticle_item->getContextID())
            ->setParameter('discussionId', $discussionarticle_item->getDiscussionID())
            ->setParameter('creatorId', $this->_current_user->getItemID())
            ->setParameter('creationDate', $currentDateTime)
            ->setParameter('modifierId', $modificator->getItemID())
            ->setParameter('modificationDate', $currentDateTime)
            ->setParameter('position', $discussionarticle_item->getPosition())
            ->setParameter('description', $discussionarticle_item->getDescription())
            ->setParameter('public', 0);

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems creating discarticle.', E_USER_WARNING);
        }
    }

    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('discussionarticles').' SET ' .
            'deletion_date="'.$current_datetime.'",' .
            'deleter_id="'.encode(AS_DB, $user_id).'"' .
            ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting discarticle.', E_USER_WARNING);
        } else {
            $link_manager = $this->_environment->getLinkManager();
            $link_manager->deleteLinksBecauseItemIsDeleted($itemId);
            parent::delete($itemId);
        }
    }

    /**
     * Flags the discussion article with the given ID as having its content overwritten.
     * When an individual discussion article which has child article(s) (aka "answers") is to be deleted, we instead use
     * this method to indicate that its content should get overwritten instead. I.e., the article is kept in the discussion
     * hierarchy (which thus will not be altered by the deletion) but its content will be replaced with some placeholder text.
     *
     * @param int $itemId The ID of the discussion article whose content shall be overwritten
     */
    public function overwriteContent(int $itemId): void
    {
        $currentDatetime = getCurrentDateTimeInMySQL();

        $updateQuery = 'UPDATE '.$this->addDatabasePrefix('discussionarticles').' SET';
        $updateQuery .= ' public = "-2",';
        $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
        $updateQuery .= ' WHERE item_id="'.encode(AS_DB, $itemId).'"';

        $result = $this->_db_connector->performQuery($updateQuery);
        if (!$result) {
            trigger_error('Problems flagging discarticle for content overwrite.', E_USER_WARNING);
        }
    }

    public function deleteDiscarticlesOfUser($uid)
    {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query = 'SELECT '.$this->addDatabasePrefix('discussionarticles').'.* FROM '.$this->addDatabasePrefix('discussionarticles').' WHERE '.$this->addDatabasePrefix('discussionarticles').'.creator_id = "'.encode(AS_DB, $uid).'"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE '.$this->addDatabasePrefix('discussionarticles').' SET';

                    /* flag */
                    if ('FLAG' === $disableOverwrite) {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                    }

                    /* disabled */
                    if ('FALSE' === $disableOverwrite) {
                        $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                        $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                    }

                    $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        trigger_error('Problems automatic deleting discussionarticles.', E_USER_WARNING);
                    }
                }
            }
        }
    }
}
