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

/** cs_discussion_item is needed to create discussionarticle items
 */
include_once('classes/cs_discussionarticle_item.php');

/** upper class of the annotation manager
 */
include_once('classes/cs_manager.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

// TBD
include_once('functions/text_functions.php');

/** class for database connection to the database table "discussionarticles"
 * this class implements a database manager for the table "discussionarticles"
 */
class cs_discussionarticles_manager extends cs_manager {

  /**
   * integer - containing the age of the discussionarticle as a limit
   */
  var $_age_limit = NULL;

  /**
   * integer - containing the id of a discussion as a limit for the selected discussionarticles
   */
  var $_discussion_limit = NULL;

  /**
   * integer - containing a start point for the selected discussionarticles
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many discussions the select statement should get
   */
  var $_interval_limit = NULL;

  /**
   * string - containing an order limit for the selected discussion
   */
  var $_order = NULL;

  /**
   * object manager - containing object to the select materials for discussionarticles
   */
  var $_material_manager = NULL;

  /**
   * object manager - containing object to the manage discussions
   */
  var $_discussion_manager = NULL;

  /**
   * integer - containing the item id of the current article
   */
  var $_current_article_id = NULL;

  /**
   * string - containing the modification date of the current article
   */
  var $_current_article_modification_date = NULL;

  var $_sort_position = false;

  var $_all_discarticle_list = NULL;
  var $_cached_discussion_item_ids = array();

   /*
    * Translation Object
    */
  private $_translator = null;

  /** constructor: cs_discussionarticles_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'discussionarticles';
      $this->_translator = $environment->getTranslationObject();
   }

  /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_age_limit = NULL;
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_order = NULL;
     $this->_sort_position = false;
  }

  /** set age limit
    * this method sets an age limit for discussionarticles
    *
    * @param integer limit age limit for discussionarticles
    */
  function setAgeLimit ($limit) {
     $this->_age_limit = (integer)$limit;
  }

  /** set discussion limit
    * this method sets an discussion limit for discussionarticles
    *
    * @param integer limit discussion limit for discussionarticles
    */
  function setDiscussionLimit ($discussion) {
     $this->_discussion_limit = (integer)$discussion;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected discussionarticles
    * @param integer interval interval limit for selected discussionarticles
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (integer)$interval;
     $this->_from_limit = (integer)$from;
  }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected discussionarticles
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

  function setSortPosition(){
     $this->_sort_position = true;
  }


  function setRoomLimit ($limit) {
     $this->_room_limit = (string)$limit;
  }

  function _performQuery($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->addDatabasePrefix('discussionarticles').'.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('discussionarticles').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('discussionarticles').'.*';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('discussionarticles');
     $query .= ' INNER JOIN ' . $this->addDatabasePrefix('items') . ' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('discussionarticles').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

     $query .= ' WHERE 1';

     // fifth, insert limits into the select statement
     if (isset($this->_room_limit) and $this->_environment->getCurrentFunction()!='clipboard_index') {
        $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.deleter_id IS NULL';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
     if (isset($this->_typ_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.type = "'.encode(AS_DB,$this->_typ_limit).'"';
     }
     if (isset($this->_discussion_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('discussionarticles').'.discussion_id = "'.encode(AS_DB,$this->_discussion_limit).'"';
     }
     if (isset($this->_group_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.to_item_id="'.encode(AS_DB,$this->_group_limit).'" AND '.$this->addDatabasePrefix('links').'.link_type="relevant_for"';
     }

      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }


     if ($this->_sort_position){
        $query .= ' ORDER BY '.$this->addDatabasePrefix('discussionarticles').'.position ASC';
     }else{
        $query .= ' ORDER BY '.$this->addDatabasePrefix('discussionarticles').'.creation_date ASC, '.$this->addDatabasePrefix('discussionarticles').'.item_id ASC, '.$this->addDatabasePrefix('discussionarticles').'.subject DESC';
     }
      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting discarticles.',E_USER_WARNING);
     } else {
        return $result;
     }
  }

  /** get a discussionartcile in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a discussionarticle
    */
  function getItem ($item_id) {
     $discussionarticles = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix("discussionarticles")." WHERE ".$this->addDatabasePrefix("discussionarticles").".item_id = '".encode(AS_DB,$item_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or empty($result[0]) ) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting one discussionarticles item.',E_USER_WARNING);
     } else {
        $discussionarticles = $this->_buildItem($result[0]);
     }
     return $discussionarticles;
  }

  /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
  function getItemList ($id_array) {
     return $this->_getItemList('discussionarticles', $id_array);
  }

  /** build a new discussionarticle item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    */
   function getNewItem () {
      return new cs_discussionarticle_item($this->_environment);
   }

  /** get a list of section in newest version
    *
    * @param array id_array ids of the items
    * @param integer version_id version of the items (optional)
    *
    * @return object cs_list of cs_section_items
    */
   function getAllDiscArticlesItemListByIDArray ($id_array) {
      if (empty($id_array)) {
         include_once('classes/cs_section_list.php');
         return new cs_section_list();
      } else {
         $section = NULL;
         $query = "SELECT * FROM ".$this->addDatabasePrefix("discussionarticles")." WHERE discussion_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= " AND ".$this->addDatabasePrefix("discussionarticles").".deleter_id IS NULL";
         $query .= " AND ".$this->addDatabasePrefix("discussionarticles").".deletion_date IS NULL";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of section items from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $discarticle_list = new cs_list();
            foreach ($result as $rs) {
               $discarticle_list->add($this->_buildItem($rs));
            }
         }
         if ( $this->_cache_on ) {
            $this->_all_discarticle_list = $discarticle_list;
            $this->_cached_discussion_item_ids = $id_array;
         }
         return $discarticle_list;
      }
   }

    public function getAllArticlesForItem($discussion_item, $show_all = false):? cs_list
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
            if ($show_all == true) {
                $this->setDeleteLimit(false);
            }
            $this->select();
            return $this->get();
        }
    }

    /**
     * Returns the parent article for the given discussion article, i.e. the article to which the given article is an answer.
     * @param \cs_discussionarticle_item $item The discussion article whose parent article shall be returned
     * @return \cs_discussionarticle_item|null Parent discussion article for the given article, or null if it has no parent
     */
    public function getParentForDiscArticle(\cs_discussionarticle_item $item): ?\cs_discussionarticle_item
    {
        // to get the parent's position, remove the trailing position element from the given item's position
        $itemPosition = $item->getPosition();
        $parentPosition = implode('.', explode('.', $itemPosition, -1));
        if (empty($parentPosition)) {
            return null;
        }

        /** @var \cs_discussionarticle_item $parentArticle */
        $parentArticle = null;
        $dbPrefix = $this->addDatabasePrefix($this->_db_table);
        $dbPrefixItems = $this->addDatabasePrefix('items');

        $query = "SELECT * FROM " . $dbPrefix;
        $query .= " INNER JOIN " . $dbPrefixItems . ' ON ' . $dbPrefixItems . '.item_id = ' . $dbPrefix . '.item_id AND ' . $dbPrefixItems . '.draft != "1"';
        $query .= ' WHERE discussion_id="' . encode(AS_DB, $item->getDiscussionID()) . '"';
        $query .= ' AND position="' . encode(AS_DB, $parentPosition) . '"';
        $query .= ' AND ' . $dbPrefix . '.deleter_id IS NULL';
        $query .= ' AND ' . $dbPrefix . '.deletion_date IS NULL';

        $result = $this->_db_connector->performQuery($query);
        if (!$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting parent of discarticle with ID ' . $item->getItemID() . '.', E_USER_WARNING);
        } else {
            $parentArticle = $this->_buildItem($result[0]);
        }

        return $parentArticle;
    }

    /**
     * Returns all child article(s) (aka "answers") for the given discussion article.
     * @param \cs_discussionarticle_item $item The discussion article whose children shall be returned
     * @return \cs_list List of child article(s) for the given discussion article, or an empty list if there aren't any
     */
    public function getChildrenForDiscArticle(\cs_discussionarticle_item $item): \cs_list
    {
        $childrenList = new cs_list();
        $dbPrefix = $this->addDatabasePrefix($this->_db_table);
        $dbPrefixItems = $this->addDatabasePrefix('items');

        $query = "SELECT * FROM " . $dbPrefix;
        $query .= " INNER JOIN " . $dbPrefixItems . ' ON ' . $dbPrefixItems . '.item_id = ' . $dbPrefix . '.item_id AND ' . $dbPrefixItems . '.draft != "1"';
        $query .= ' WHERE discussion_id="' . encode(AS_DB, $item->getDiscussionID()) . '"';
        $query .= ' AND position LIKE "' . encode(AS_DB, $item->getPosition()) . '.%"';
        $query .= ' AND ' . $dbPrefix . '.deleter_id IS NULL';
        $query .= ' AND ' . $dbPrefix . '.deletion_date IS NULL';

        $result = $this->_db_connector->performQuery($query);
        if (!$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting children of discarticle with ID ' . $item->getItemID() . '.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $childrenList->add($this->_buildItem($rs));
            }
        }

        return $childrenList;
    }

    /**
     * update a discussion - internal, do not use -> use method save
     * this method updates a discussion
     *
     * @param cs_discussionarticle_item $discussionarticle_item
     * @throws \Doctrine\DBAL\Exception
     */
    function _update($discussionarticle_item)
    {
        /** @var cs_discussionarticle_item $discussionarticle_item */
        if ($this->_update_with_changing_modification_information) {
            parent::_update($discussionarticle_item);
        }

        $this->_current_article_modification_date = getCurrentDateTimeInMySQL();
        $this->_current_article_id = $discussionarticle_item->getItemID();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $public = !$discussionarticle_item->isPrivateEditing();

        $queryBuilder
            ->update($this->addDatabasePrefix('discussionarticles'))
            ->set('subject', ':subject')
            ->set('position', ':position')
            ->set('description', ':description')
            ->set('public', ':public')
            ->where('item_id = :itemId')
            ->setParameter('subject', $discussionarticle_item->getSubject())
            ->setParameter('position', $discussionarticle_item->getPosition())
            ->setParameter('description', $discussionarticle_item->getDescription())
            ->setParameter('public', $public ? 1 : 0)
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
            include_once('functions/error_functions.php');
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * create a discussionarticle - internal, do not use -> use method save
     * this method creates a discussionarticle
     *
     * @param cs_discussionarticle_item $discussionarticle_item
     */
    function _create(cs_discussionarticle_item $discussionarticle_item)
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
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error('Problems creating discussionarticle item.', E_USER_WARNING);
            $this->_create_id = null;
        }
    }

    /**
     * creates a new discarticlearticle - internal, do not use -> use method save
     * this method creates a new discarticlearticle
     *
     * @param cs_discussionarticle_item $discussionarticle_item
     * @throws \Doctrine\DBAL\Exception
     */
    function _newDiscussionArticle(cs_discussionarticle_item $discussionarticle_item)
    {
        $currentDateTime = getCurrentDateTimeInMySQL();
        $this->_current_article_modification_date = $currentDateTime;
        $modificator = $discussionarticle_item->getModificatorItem();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $public = !$discussionarticle_item->isPrivateEditing();

        $queryBuilder
            ->insert($this->addDatabasePrefix('discussionarticles'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('discussion_id', ':discussionId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('subject', ':subject')
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
            ->setParameter('subject', $discussionarticle_item->getSubject())
            ->setParameter('position', $discussionarticle_item->getPosition())
            ->setParameter('description', $discussionarticle_item->getDescription())
            ->setParameter('public', $public ? 1 : 0);

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            include_once('functions/error_functions.php');
            trigger_error('Problems creating discarticle.', E_USER_WARNING);
        }
    }

  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID() ?: 0;
     $query = 'UPDATE '.$this->addDatabasePrefix('discussionarticles').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting discarticle.',E_USER_WARNING);
     } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         parent::delete($item_id);
         unset($link_manager);
     }
     unset($current_user);
  }

    /**
     * Flags the discussion article with the given ID as having its content overwritten.
     * When an individual discussion article which has child article(s) (aka "answers") is to be deleted, we instead use
     * this method to indicate that its content should get overwritten instead. I.e., the article is kept in the discussion
     * hierarchy (which thus will not be altered by the deletion) but its content will be replaced with some placeholder text.
     * @param int $itemId The ID of the discussion article whose content shall be overwritten
     */
    public function overwriteContent(int $itemId): void
    {
        $currentDatetime = getCurrentDateTimeInMySQL();

        $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('discussionarticles') . ' SET';
        $updateQuery .= ' public = "-2",';
        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
        $updateQuery .= ' WHERE item_id="' . encode(AS_DB, $itemId) . '"';

        $result = $this->_db_connector->performQuery($updateQuery);
        if (!$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems flagging discarticle for content overwrite.', E_USER_WARNING);
        }
    }

   ########################################################
   # statistic functions
   ########################################################

   function getCountDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix($this->_db_table).".item_id) as number FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB,$this->_room_limit)."' and ((".$this->addDatabasePrefix($this->_db_table).".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix($this->_db_table).".creation_date < '".encode(AS_DB,$end)."') or (".$this->addDatabasePrefix($this->_db_table).".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix($this->_db_table).".item_id) as number FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix($this->_db_table).".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix($this->_db_table).".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModDiscArticles ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix($this->_db_table).".item_id) as number FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date < '".encode(AS_DB,$end)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date != ".$this->addDatabasePrefix($this->_db_table).".creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

    function deleteDiscarticlesOfUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== 'TRUE') {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('discussionarticles').'.* FROM ' . $this->addDatabasePrefix('discussionarticles').' WHERE ' . $this->addDatabasePrefix('discussionarticles') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('discussionarticles') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'FLAG') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === 'FALSE') {
                        $updateQuery .= ' subject = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB,$rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        trigger_error('Problems automatic deleting discussionarticles.', E_USER_WARNING);
                    }
                }
            }
        }
    }
}