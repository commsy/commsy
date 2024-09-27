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
use App\Utils\DbalQueryBuilderTrait;
use Doctrine\DBAL\ArrayParameterType;

/** class for database connection to the database table "todo"
 * this class implements a database manager for the table "todo".
 */
class cs_todos_manager extends cs_manager
{
    use DbalQueryBuilderTrait;

    public $_age_limit = null;
    public $_future_limit = null;
    public $_from_limit = null;
    public $_interval_limit = null;
    public $_search_limit = null;
    public $_id_array_limit = [];
    public $_group_limit = null;
    public $_topic_limit = null;
    public $_sort_order = null;
    private bool $_assignment_limit = false;

    private ?int $_status_limit = null;

    /*
     * Translation Object
     */
    private $_translator = null;

    /** constructor: cs_todo_manager
     * the only available constructor, initial values for internal variables<br />
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'todos';
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_age_limit = null;
        $this->_future_limit = null;
        $this->_status_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_search_limit = null;
        $this->_group_limit = null;
        $this->_topic_limit = null;
        $this->_user_limit = null;
        $this->_sort_order = null;
        $this->_assignment_limit = false;
    }

    /** set age limit
     * this method sets an age limit for todo.
     *
     * @param int limit age limit for todo
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    public function setAssignmentLimit($array)
    {
        $this->_assignment_limit = true;
        if (isset($array[0])) {
            $this->_related_user_limit = $array;
        }
    }

    public function setStatusLimit($limit)
    {
        $this->_status_limit = (int) $limit;
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
     * @param int from     from limit for selected todo
     * @param int interval interval limit for selected todo
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function _performQuery($mode = 'select')
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder->from('todos', 't');

        switch ($mode) {
            case 'count':
                $queryBuilder->select('COUNT(t.item_id) AS count');
                break;
            case 'id_array':
                $queryBuilder->select('t.item_id');
                break;
            case 'distinct':
                $queryBuilder->select('t.*');
                $queryBuilder->distinct();
                break;
            default:
                $queryBuilder->select('t.*');
        }

        $queryBuilder->innerJoin('t', 'items', 'i', 'i.item_id = t.item_id');
        $queryBuilder->andWhere('i.draft != 1');

        if (isset($this->_sort_order) && in_array($this->_sort_order, ['creator', 'creator_rev', 'modificator', 'modificator_rev'])) {
            $queryBuilder->innerJoin('t', 'user', 'creator', 't.creator_id = creator.item_id');
            $queryBuilder->innerJoin('t', 'user', 'modificator', 't.modifier_id = modificator.item_id');
        }

        $this->addTopicLimit($queryBuilder, 't', $this->_topic_limit);
        $this->addGroupLimit($queryBuilder, 't', $this->_group_limit);
        $this->addTagLimit($queryBuilder, 't', $this->_getTagIDArrayByTagIDArray($this->_tag_limit));
        $this->addBuzzwordLimit($queryBuilder, 't', $this->_buzzword_limit);
        $this->addRefIdLimit($queryBuilder, 't', $this->_ref_id_limit);
        $this->addInactiveEntriesLimit($queryBuilder, 't', $this->inactiveEntriesLimit);
        $this->addContextLimit($queryBuilder, 't', $this->_room_array_limit ?? $this->_room_limit);
        $this->addDeleteLimit($queryBuilder, 't', $this->_delete_limit);
        $this->addCreatorLimit($queryBuilder, 't', $this->_ref_user_limit);
        $this->addModifiedWithinLimit($queryBuilder, 't', $this->_age_limit);
        $this->addModifiedAfterLimit($queryBuilder, 't', $this->modificationNewerThenLimit);
        $this->addCreatedWithinLimit($queryBuilder, 't', $this->_existence_limit);
        $this->addIdLimit($queryBuilder, 't', $this->_id_array_limit);
        $this->addNotIdLimit($queryBuilder, 't', $this->excludedIdsLimit);

        if (isset($this->_user_limit)) {
            $queryBuilder->leftJoin('t', 'link_items', 'user_limit1', 'user_limit1.deletion_date IS NULL AND user_limit1.first_item_id = t.item_id AND user_limit1.second_item_type = "user"');
            $queryBuilder->leftJoin('t', 'link_items', 'user_limit2', 'user_limit2.deletion_date IS NULL AND user_limit2.second_item_id = t.item_id AND user_limit2.first_item_type = "user"');

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
            $queryBuilder->leftJoin('t', 'link_items', 'related_user_limit1', 'related_user_limit1.deletion_date IS NULL AND related_user_limit1.first_item_id = t.item_id AND related_user_limit1.second_item_type = "user"');
            $queryBuilder->leftJoin('t', 'link_items', 'related_user_limit2', 'related_user_limit2.deletion_date IS NULL AND related_user_limit2.second_item_id = t.item_id AND related_user_limit2.first_item_type = "user"');

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

        if (isset($this->_status_limit)) {
            if (4 == $this->_status_limit) {
                $queryBuilder->andWhere('t.status != 3');
            } else {
                $queryBuilder->andWhere('t.status = :statusLimit');
                $queryBuilder->setParameter('statusLimit', $this->_status_limit);
            }
        }

        // order
        if (isset($this->_sort_order)) {
            if ('date' == $this->_sort_order) {
                $queryBuilder->orderBy('t.modification_date', 'DESC');
            } elseif ('date_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('t.modification_date');
            } elseif ('duedate' == $this->_sort_order) {
                $queryBuilder->orderBy('t.date', 'DESC');
            } elseif ('duedate_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('t.date');
            } elseif ('title' == $this->_sort_order) {
                $queryBuilder->orderBy('t.title');
            } elseif ('title_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('t.title', 'DESC');
            } elseif ('status' == $this->_sort_order) {
                $queryBuilder->orderBy('t.status');
            } elseif ('status_rev' == $this->_sort_order) {
                $queryBuilder->orderBy('t.status', 'DESC');
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
            $queryBuilder->orderBy('t.date', 'DESC');
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) && isset($this->_from_limit)) {
                $queryBuilder->setFirstResult($this->_from_limit);
                $queryBuilder->setMaxResults($this->_interval_limit);
            }
        }

        $result = $queryBuilder->fetchAllAssociative();

        // TODO: ???
        // This looks like a former 'date' column now expected to be 'end_date'
        array_walk($result, function(&$item) {
            if (isset($item['date'])) {
                $item['end_date'] = $item['date'];
                unset($item['date']);
            }
        });

        return $result;
    }

    /** build a new todo item
     * this method returns a new EMTPY material item.
     *
     * @return object cs_item a new EMPTY material
     */
    public function getNewItem()
    {
        return new cs_todo_item($this->_environment);
    }

    /** Returns the todo item of the given item ID.
     *
     * @param int|null itemId ID of the item
     */
    public function getItem(?int $itemId): ?cs_todo_item
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
            ->select('t.*', 'i.pinned')
            ->from($this->addDatabasePrefix($this->_db_table), 't')
            ->innerJoin('t', 'items', 'i', 'i.item_id = t.item_id')
            ->where('t.item_id = :itemId')
            ->setParameter('itemId', $itemId);

        try {
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems selecting todos item (' . $itemId . '): ' . $e->getMessage(), E_USER_WARNING);
        }

        $todo = null;
        if (!empty($result[0])) {
            if (isset($result[0]['date'])) {
                $result[0]['end_date'] = $result[0]['date'];
                unset($result[0]['date']);
            }
            $todo = $this->_buildItem($result[0]);
            if ($this->_cache_on) {
                $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
        }

        return $todo;
    }

    /** get a list of todo in newest version.
     *
     * @param array id_array ids of the items
     *
     * @return object cs_list of cs_todo_items
     */
    public function getItemList(array $id_array): cs_list
    {
        return $this->_getItemList('todo', $id_array);
    }

     /** update a todo - internal, do not use -> use method save
      * this method updates the database record for a given todo item.
      *
      * @param cs_todo_item the todo item for which an update should be made
      */
     public function _update($item)
     {
         /* @var cs_todo_item $item */
         parent::_update($item);

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->update($this->addDatabasePrefix('todos'), 't')
             ->set('modifier_id', ':modifierId')
             ->set('modification_date', ':modificationDate')
             ->set('activation_date', ':activationDate')
             ->set('title', ':title')
             ->set('status', ':status')
             ->set('minutes', ':minutes')
             ->set('time_type', ':timeType')
             ->set('public', ':public')
             ->set('description', ':description')
             ->where('item_id = :itemId')
             ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
             ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
             ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
             ->setParameter('title', $item->getTitle())
             ->setParameter('status', $item->getInternalStatus())
             ->setParameter('minutes', $item->getPlannedTime())
             ->setParameter('timeType', $item->getTimeType())
             ->setParameter('public', $item->isPublic() ? 1 : 0)
             ->setParameter('description', $item->getDescription())
             ->setParameter('itemId', $item->getItemID());

         if ($item->getDate()) {
             $queryBuilder
                 ->set('date', ':date')
                 ->setParameter('date', $item->getDate());
         }

         try {
             $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
         }
     }

  /**
   * create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'todo' in the database and sets the todo items item id.
   * it then calls the private method _newNews to store the todo item itself.
   */
  public function _create(cs_todo_item $item)
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
          ->setParameter('type', 'todo')
          ->setParameter('draft', $item->isDraft());

      try {
          $queryBuilder->executeStatement();

          $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
          $item->setItemID($this->getCreateID());
          $this->_newNews($item);
      } catch (\Doctrine\DBAL\Exception $e) {
          trigger_error($e->getMessage(), E_USER_WARNING);
          $this->_create_id = null;
      }
  }

     /** store a new todo item to the database - internal, do not use -> use method save
      * this method stores a newly created todo item to the database.
      *
      * @param cs_todo_item the todo item to be stored
      */
     public function _newNews(cs_todo_item $item)
     {
         $currentDateTime = getCurrentDateTimeInMySQL();

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->insert($this->addDatabasePrefix('todos'))
             ->setValue('item_id', ':itemId')
             ->setValue('context_id', ':contextId')
             ->setValue('creator_id', ':creatorId')
             ->setValue('creation_date', ':creationDate')
             ->setValue('modifier_id', ':modifierId')
             ->setValue('modification_date', ':modificationDate')
             ->setValue('activation_date', ':activationDate')
             ->setValue('title', ':title')
             ->setValue('date', ':date')
             ->setValue('minutes', ':minutes')
             ->setValue('time_type', ':timeType')
             ->setValue('public', ':public')
             ->setValue('description', ':description')
             ->setParameter('itemId', $item->getItemID())
             ->setParameter('contextId', $item->getContextID())
             ->setParameter('creatorId', $item->getCreatorItem()->getItemID())
             ->setParameter('creationDate', $currentDateTime)
             ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
             ->setParameter('modificationDate', $currentDateTime)
             ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
             ->setParameter('title', $item->getTitle())
             ->setParameter('date', empty($item->getDate()) ? null : $item->getDate())
             ->setParameter('minutes', $item->getPlannedTime())
             ->setParameter('timeType', $item->getTimeType())
             ->setParameter('public', $item->isPublic() ? 1 : 0)
             ->setParameter('description', $item->getDescription());

         $status = $item->getInternalStatus();
         if ($status) {
             $queryBuilder
                 ->setValue('status', ':status')
                 ->setParameter('status', $status);
         }

         try {
             $queryBuilder->executeStatement();
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
         }
     }

    /**  delete a todo item.
     *
     * @param cs_todo_item the todo item to be deleted
     */
    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('todos').' SET '.
                'deletion_date="'.$current_datetime.'",'.
                'deleter_id="'.encode(AS_DB, $user_id).'"'.
                ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting todos from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $link_manager = $this->_environment->getLinkManager();
            $link_manager->deleteLinksBecauseItemIsDeleted($itemId);
            parent::delete($itemId);
        }
    }

     public function deleteTodosOfUser($uid)
     {
         global $symfonyContainer;
         $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

         if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
             $currentDatetime = getCurrentDateTimeInMySQL();
             $query = 'SELECT '.$this->addDatabasePrefix('todos').'.* FROM '.$this->addDatabasePrefix('todos').' WHERE '.$this->addDatabasePrefix('todos').'.creator_id = "'.encode(AS_DB, $uid).'"';
             $result = $this->_db_connector->performQuery($query);

             if (!empty($result)) {
                 foreach ($result as $rs) {
                     $updateQuery = 'UPDATE '.$this->addDatabasePrefix('todos').' SET';

                     /* flag */
                     if ('FLAG' === $disableOverwrite) {
                         $updateQuery .= ' public = "-1",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                     }

                     /* disabled */
                     if ('FALSE' === $disableOverwrite) {
                         $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                         $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'",';
                         $updateQuery .= ' public = "1"';
                     }

                     $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                     $result2 = $this->_db_connector->performQuery($updateQuery);
                     if (!$result2) {
                         trigger_error('Problems automatic deleting todos from query: "'.$updateQuery.'"', E_USER_WARNING);
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

         $this->setSortOrder('date');

         $this->select();

         return $this->get();
     }
}
