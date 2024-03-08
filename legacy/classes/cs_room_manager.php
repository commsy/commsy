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

use App\Hash\HashManager;
use Doctrine\ORM\EntityManagerInterface;

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community".
 */
class cs_room_manager extends cs_context_manager
{
    /**
     * integer - containing a start point for the select community.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many communities the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - containing USERID of an user.
     */
    public $_user_id_limit = null;

    public $_all_room_limit = false;

    public $_time_limit = null;

    public $_continuous_limit = null;

    public $_template_limit = null;

    /**
     * string - containing an order limit for the select community.
     */
    public $_order = null;

    public $_deleted_limit = null;

    private bool $_limit_with_grouproom = false;

    private bool $_limit_only_grouproom = false;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = CS_ROOM_TYPE;
        $this->_room_type = '';
    }

    /** reset limits
     * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_user_id_limit = null;
        $this->_all_room_limit = false;
        $this->_order = null;
        $this->_deleted_limit = null;
        $this->_time_limit = null;
        $this->_continuous_limit = null;
        $this->_template_limit = null;
        $this->_limit_with_grouproom = false;
        $this->_limit_only_grouproom = false;
    }

    public function setWithGrouproom()
    {
        $this->_limit_with_grouproom = true;
    }

    /**
     * Select only grouprooms.
     */
    public function setOnlyGrouproom()
    {
        $this->_limit_only_grouproom = true;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected communities
     * @param int interval interval limit for selected communities
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    public function setRoomTypeLimit($value)
    {
        $this->_room_type = $value;
    }

    /** set user id limit.
     *
     * @param string limit userid limit for selected rooms
     */
    public function setUserIDLimit($limit)
    {
        $this->_user_id_limit = (string) $limit;
    }

    public function setAuthSourceLimit($limit)
    {
        $this->_auth_source_limit = (int) $limit;
    }

    public function setGetAllRoomLimit()
    {
        $this->_all_room_limit = true;
    }

    public function setDeletedLimit()
    {
        $this->_deleted_limit = true;
    }

    /** set time limit
     * this method sets an clock pulses limit for rooms.
     *
     * @param int limit time limit for rooms (item id of clock pulses)
     */
    public function setTimeLimit($limit)
    {
        $this->_time_limit = $limit;
    }

    public function setContinuousLimit()
    {
        $this->_continuous_limit = 1;
    }

    public function setNotContinuousLimit()
    {
        $this->_continuous_limit = -1;
    }

    public function unsetContinuousLimit()
    {
        $this->_continuous_limit = null;
    }

    public function setTemplateLimit()
    {
        $this->_template_limit = 1;
    }

    public function setNotTemplateLimit()
    {
        $this->_template_limit = -1;
    }

    public function unsetTemplateLimit()
    {
        $this->_template_limit = null;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected communities
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    /** select rooms limited by limits
     * this method returns a list (cs_list) of rooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function _performQuery($mode = 'select')
    {
        $query = '';
        if ('count' == $mode) {
            $query .= 'SELECT count(DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
        } elseif ('id_array' == $mode) {
            $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        } else {
            $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }

        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);

        // user id limit
        if (isset($this->_user_id_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            if (!$this->_all_room_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            }
        }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if (-1 != $this->_time_limit) {
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS time_label ON room_time.to_item_id=time_label.item_id AND time_label.type="time"';
            } else {
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
            }
        }

        $query .= ' WHERE 1';

        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(',', $this->_id_array_limit).')';
        }

        if (!empty($this->_room_type)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB, $this->_room_type).'"';
        }

        // ##################################
        // FLAG: group room
        // ##################################
        if ((empty($this->_room_type) or CS_GROUPROOM_TYPE != $this->_room_type) && !$this->_limit_only_grouproom) {
            if (!isset($this->_id_array_limit) && !$this->_limit_with_grouproom) {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type != "'.CS_GROUPROOM_TYPE.'"';
            }
        } elseif ($this->_limit_only_grouproom) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.CS_GROUPROOM_TYPE.'"';
        }
        // ##################################
        // FLAG: group room
        // ##################################

        // insert limits into the select statement
        if (isset($this->_deleted_limit) and $this->_deleted_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NOT NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NOT NULL';
        } elseif (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL';
        }
        if (isset($this->_status_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB, $this->_status_limit).'"';
        }

        if (isset($this->_room_limit)
            and !empty($this->_room_limit)
            and !isset($this->_id_array_limit)
        ) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if (isset($this->_continuous_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.continuous = "'.encode(AS_DB, $this->_continuous_limit).'"';
        }

        if (!empty($this->_user_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB, $this->_user_id_limit).'"';
        }
        if (!empty($this->_auth_source_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB, $this->_auth_source_limit).'"';
        }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if (-1 != $this->_time_limit) {
                $query .= ' AND time_label.item_id = "'.encode(AS_DB, $this->_time_limit).'"';
            } else {
                $query .= ' AND room_time.to_item_id IS NULL';
            }
        }

        // template
        if (isset($this->_template_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.template = "'.encode(AS_DB, $this->_template_limit).'"';
        }

        if ('count' != $mode) {
            if (isset($this->_order)) {
                if ('date' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
                } elseif ('creation_date' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
                } elseif ('activity' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
                } elseif ('activity_rev' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
                } elseif ('title' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
                } elseif ('title_rev' == $this->_order) {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
                } else {
                    $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
                }
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
            }
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        $this->_last_query = $query;

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"', E_USER_ERROR);
        } else {
            if (!empty($this->_id_array_limit)
                and 'id_array' == $this->_order
            ) {
                // sort result
                $result2 = [];
                foreach ($result as $value) {
                    $result2[$value['item_id']] = $value;
                }
                $result = [];
                foreach ($this->_id_array_limit as $item_id) {
                    if (isset($result2[$item_id])) {
                        $result[] = $result2[$item_id];
                    } else {
                        // separator
                        $temp_array = [];
                        $temp_array['item_id'] = -1;
                        $temp_array['title'] = '----------------------------';
                        $temp_array['type'] = CS_PROJECT_TYPE;
                        $result[] = $temp_array;
                        unset($temp_array);
                    }
                }
            } elseif (!empty($this->_id_array_limit)
                and $this->_cache_on
            ) {
                foreach ($result as $row) {
                    if (!empty($row)
                        and !empty($row['item_id'])
                        and empty($this->_cache_row[$row['item_id']])) {
                        $this->_cache_row[$row['item_id']] = $row;
                    }
                }
            }

            return $result;
        }
    }

    // #########################################################
    // statistic functions
    // #########################################################

    public function getActiveRooms($start, $end)
    {
        $list = $this->getUsedRooms($start, $end);

        // delete rooms that are not really active
        $retour_list = new cs_list();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if ($item->isActive($start, $end)) {
                    $retour_list->add($item);
                }
                $item = $list->getNext();
            }
        }

        return $retour_list;
    }

    public function getUsedRooms($start, $end)
    {
        $list = new cs_list();

        $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.* FROM '.$this->addDatabasePrefix($this->_db_table).', '.$this->addDatabasePrefix('user');
        $query .= ' WHERE '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').".lastlogin > '".encode(AS_DB, $start)."' and ".$this->addDatabasePrefix('user').".creation_date < '".encode(AS_DB, $end)."'";
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB, $this->_room_limit)."' AND ".$this->addDatabasePrefix($this->_db_table).".status != '4' and ".$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL and '.$this->addDatabasePrefix($this->_db_table).".creation_date < '".encode(AS_DB, $end)."' and (type = 'project' or type = 'community')";
        $query .= ' GROUP BY '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.type';
        $query .= ', '.$this->addDatabasePrefix($this->_db_table).'.title';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting used rooms '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $list->add($this->_buildItem($rs));
            }
            unset($result);
        }

        return $list;
    }

    public function getRelatedRoomListForUser($user_item)
    {
        return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID());
    }

    public function getAllRelatedRoomListForUser($user_item)
    {
        $this->setRoomTypeLimit('');

        return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), true);
    }

    public function getAllMaxActivityPoints()
    {
        $retour = 0;
        $query = 'SELECT MAX(activity) AS max FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deleter_id IS NULL AND deletion_date is NULL and (type = "project" or type = "community");';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' max activity from query: "'.$query.'"', E_USER_WARNING);
        } else {
            if (!empty($result[0]['max'])) {
                $retour = $result[0]['max'];
            }
        }

        return $retour;
    }

    public function getMaxActivityPoints()
    {
        $retour = 0;
        $query = 'SELECT MAX(activity) AS max FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deleter_id IS NULL AND deletion_date is NULL';
        if (!empty($this->_room_limit)) {
            $query .= ' and context_id = '.encode(AS_DB, $this->_room_limit);
        }
        $query .= ';';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' max activity from query: "'.$query.'"', E_USER_WARNING);
        } else {
            if (!empty($result[0]['max'])) {
                $retour = $result[0]['max'];
            }
        }

        return $retour;
    }

    public function getLastQuery()
    {
        return $this->_last_query;
    }

    // #########################################################
    // statistic functions - BEGIN
    // #########################################################

    public function getCountAllTypeRooms($type, $start, $end)
    {
        $retour = 0;

        $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as number FROM '.$this->addDatabasePrefix($this->_db_table)." WHERE context_id = '".encode(AS_DB, $this->_room_limit)."' and creation_date < '".encode(AS_DB, $end)."' and status != '4' AND deletion_date IS NULL AND deletion_date IS NULL";
        if (!empty($type)) {
            $query .= ' AND type="'.$type.'"';
        }
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting all rooms '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
        }

        return $retour;
    }

    public function getCountUsedTypeRooms($type, $start, $end)
    {
        return $this->_getUsedTypeRooms($type, $start, $end, 'COUNT');
    }

    public function getCountActiveTypeRooms($type, $start, $end)
    {
        $list = $this->getActiveTypeRooms($type, $start, $end);
        if ($list->isEmpty()) {
            return 0;
        } else {
            return $list->getCount();
        }
    }

    public function getActiveTypeRooms($type, $start, $end)
    {
        $list = $this->getUsedTypeRooms($type, $start, $end);

        // delete rooms that are not really active
        $retour_list = new cs_list();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if ($item->isActive($start, $end)) {
                    $retour_list->add($item);
                }
                $item = $list->getNext();
            }
        }

        return $retour_list;
    }

    public function getUsedTypeRooms($type, $start, $end)
    {
        return $this->_getUsedTypeRooms($type, $start, $end, 'SELECT');
    }

    public function _getUsedTypeRooms($type, $start, $end, $mode = 'SELECT')
    {
        if ('COUNT' == $mode) {
            $retour = 0;
            $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id) as number';
        } else {
            $retour = new cs_list();
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table).', '.$this->addDatabasePrefix('user');
        $query .= ' WHERE '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').".lastlogin > '".encode(AS_DB, $start)."' and ".$this->addDatabasePrefix('user').".creation_date < '".encode(AS_DB, $end)."'";
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB, $this->_room_limit)."' AND ".$this->addDatabasePrefix($this->_db_table).".status != '4' AND ".$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL and '.$this->addDatabasePrefix($this->_db_table).".creation_date < '".encode(AS_DB, $end)."'";
        if (!empty($type)) {
            $query .= ' AND type="'.$type.'"';
        }
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting used rooms '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                if ('COUNT' == $mode) {
                    $retour = $rs['number'];
                } else {
                    $retour->add($this->_buildItem($rs));
                }
            }
        }

        return $retour;
    }

    // #########################################################
    // statistic functions - END
    // #########################################################

    public function deleteFromDb($context_id)
    {
        $query = 'DELETE FROM '.$this->_db_table.' WHERE '.$this->_db_table.'.item_id = "'.$context_id.'"';
        $this->_db_connector->performQuery($query);
    }

    public function deleteReallyOlderThan($days): void
    {
        $symfonyContainer = $this->_environment->getSymfonyContainer();

        /** @var HashManager $hashManager */
        $hashManager = $symfonyContainer->get(HashManager::class);
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_item_file_manager = $this->_environment->getLinkItemFileManager();
        $reader_manager = $this->_environment->getReaderManager();
        $annotation_manager = $this->_environment->getAnnotationManager();
        $announcement_manager = $this->_environment->getAnnouncementManager();
        $dates_manager = $this->_environment->getDatesManager();
        $discussion_manager = $this->_environment->getDiscussionManager();
        $discussionarticles_manager = $this->_environment->getDiscussionarticlesManager();
        $file_manager = $this->_environment->getFileManager();
        $item_manager = $this->_environment->getItemManager();
        $labels_manager = $this->_environment->getLabelManager();
        $links_manager = $this->_environment->getLinkManager();
        $link_item_manager = $this->_environment->getLinkItemManager();
        $material_manager = $this->_environment->getMaterialManager();
        $section_manager = $this->_environment->getSectionManager();
        $step_manager = $this->_environment->getStepManager();
        $tag_manager = $this->_environment->getTagManager();
        $tag2tag_manager = $this->_environment->getTag2TagManager();
        $task_manager = $this->_environment->getTaskManager();
        $todo_manager = $this->_environment->getTodosManager();
        $user_manager = $this->_environment->getUserManager();
        $room_manager = $this->_environment->getRoomManager();

        /** @var EntityManagerInterface $em */
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $query = $em->createQuery('
            SELECT r.itemId, r.contextId
            FROM App\Entity\Room r
            WHERE DATE_DIFF(CURRENT_DATE(), r.deletionDate) > :diff
        ');
        $query->setParameter('diff', $days);
        $rooms = $query->getResult();

        foreach ($rooms as $room) {
            $contextId = $room['contextId'];
            $itemId = $room['itemId'];

            // delete files
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->removeRoomDir($contextId, $itemId);

            // managers
            $hashManager->deleteHashesInContext($itemId);
            $link_modifier_item_manager->deleteFromDb($itemId);
            $link_item_file_manager->deleteFromDb($itemId);
            $reader_manager->deleteFromDb($itemId);
            $annotation_manager->deleteFromDb($itemId);
            $announcement_manager->deleteFromDb($itemId);
            $dates_manager->deleteFromDb($itemId);
            $discussion_manager->deleteFromDb($itemId);
            $discussionarticles_manager->deleteFromDb($itemId);
            $file_manager->deleteFromDb($itemId);
            $item_manager->deleteFromDb($itemId);
            $labels_manager->deleteFromDb($itemId);
            $links_manager->deleteFromDb($itemId);
            $link_item_manager->deleteFromDb($itemId);
            $material_manager->deleteFromDb($itemId);
            $section_manager->deleteFromDb($itemId);
            $step_manager->deleteFromDb($itemId);
            $tag_manager->deleteFromDb($itemId);
            $tag2tag_manager->deleteFromDb($itemId);
            $task_manager->deleteFromDb($itemId);
            $todo_manager->deleteFromDb($itemId);
            $user_manager->deleteFromDb($itemId);
            $room_manager->deleteFromDb($itemId);
        }
    }

    public function getUserRoomsUserIsMemberOf(cs_user_item $user, bool $withExtras = true): cs_list
    {
        $list = new \cs_list();

        if ($user->isReallyGuest()) {
            return $list;
        }

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select('r.item_id', 'r.context_id', 'r.creator_id', 'r.modifier_id', 'r.creation_date',
                'r.modification_date', 'r.title', 'r.status', 'r.activity', 'r.type', 'r.public',
                'r.is_open_for_guests', 'r.continuous', 'r.template', 'r.contact_persons', 'r.room_description',
                'r.lastlogin')
            ->from('room', 'r')
            ->innerJoin('r', 'user', 'u', 'u.context_id = r.item_id')
            ->andWhere('r.deleter_id IS NULL')
            ->andWhere('r.deletion_date IS NULL')
            ->andWhere('r.type = :type')
            ->andWhere('u.auth_source = :authSource')
            ->andWhere('u.deleter_id IS NULL')
            ->andWhere('u.deletion_date IS NULL')
            ->andWhere('u.user_id = :userId')
            ->setParameter('type', 'userroom')
            ->setParameter('authSource', $user->getAuthSource())
            ->setParameter('userId', $user->getUserID());

        if ($withExtras) {
            $queryBuilder->addSelect('r.extras');
        }

        try {
            $results = $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());

            foreach ($results as $result) {
                $list->add($this->_buildItem($result));
            }
        } catch (\Doctrine\DBAL\Exception) {
        }

        return $list;
    }

    public function deleteRoomOfUserAndUserItemsInactivity($uid)
    {
        $rs = [];
        // create backup of item
        global $symfonyContainer;
        $current_datetime = getCurrentDateTimeInMySQL();

        // list of rooms where user is member
        $query = '
            SELECT
                *
            FROM '
            .$this->addDatabasePrefix('user').','
            .$this->addDatabasePrefix('room').'
            WHERE '
            .$this->addDatabasePrefix('user').'.user_id = "'.$uid.'" AND '
            .$this->addDatabasePrefix('user').'.context_id = '.$this->addDatabasePrefix('room').'.item_id AND '
            .$this->addDatabasePrefix('room').'.type != "community" AND '
            .$this->addDatabasePrefix('user').'.deletion_date IS NULL AND
                1 >= (
                    SELECT
                        COUNT(*)
                    FROM '
            .$this->addDatabasePrefix('user').'
                    WHERE '
            .$this->addDatabasePrefix('user').'.context_id = '.$this->addDatabasePrefix('room').'.item_id AND '
            .$this->addDatabasePrefix('user').'.deletion_date IS NULL
                )';

        $result = $this->_db_connector->performQuery($query);
        if (isset($result)) {
            foreach ($result as $rs) {
                $insert_query = 'UPDATE '.$this->addDatabasePrefix('room').' SET';
                $insert_query .= ' modification_date = "'.$current_datetime.'",';
                $insert_query .= ' deletion_date = "'.$current_datetime.'"';
                $insert_query .= ' WHERE item_id = "'.$rs['item_id'].'"';
                $result2 = $this->_db_connector->performQuery($insert_query);
                if (!isset($result2) or !$result2) {
                    trigger_error('Problems automatic deleting materials from query: "'.$insert_query.'"', E_USER_WARNING);
                }
            }
            $user_query = 'UPDATE '.$this->addDatabasePrefix('user').' SET';
            $user_query .= ' modification_date = "'.$current_datetime.'",';
            $user_query .= ' deletion_date = "'.$current_datetime.'"';
            $user_query .= ' WHERE user_id = "'.$rs['user_id'].'"';
            $result3 = $this->_db_connector->performQuery($user_query);
            if (!isset($result3) or !$result3) {
                trigger_error('Problems automatic deleting materials from query: "'.$user_query.'"', E_USER_WARNING);
            }
        }
    }

    public function getNumberOfModerators($roomId)
    {
        $query = '
            SELECT COUNT(user.item_id) AS numMods FROM user
            WHERE
                user.deleter_id IS NULL AND
                user.deletion_date IS NULL AND
                user.status = 3 AND
                user.context_id = '.encode(AS_DB, $roomId).'
        ';

        $result = $this->_db_connector->performQuery($query);

        if ($result && isset($result[0]['numMods'])) {
            return (int) $result[0]['numMods'];
        }

        return 0;
    }
}
