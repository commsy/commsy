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

/** class for database connection to the database table "tag2tag"
 * this class implements a database manager for the table "tag2tag".
 */
class cs_tag2tag_manager extends cs_manager
{
    private array $cachedRows = [];
    private array $cachedFatherIdArray = [];
    private ?array $cachedChildrenIdArray = null;

    /** constructor: cs_tag2tag_manager
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = CS_TAG2TAG_TYPE;
    }

    public function _buildItem(array $data_array)
    {
        $retour = $this->getNewItem();
        $retour->setLinkID($data_array['link_id']);
        $retour->setContextItemID($data_array['context_id']);
        $retour->setCreatorItemID($data_array['creator_id']);
        $retour->setModifierItemID($data_array['modifier_id']);
        $retour->setDeleterItemID($data_array['deleter_id']);
        $retour->setCreationDate($data_array['creation_date']);
        $retour->setModificationDate($data_array['modification_date']);
        $retour->setDeletionDate($data_array['deletion_date']);
        $retour->setFatherItemID($data_array['from_item_id']);
        $retour->setChildItemID($data_array['to_item_id']);
        $retour->setSortingPlace($data_array['sorting_place']);

        return $retour;
    }

    /** get a link.
     *
     * @param int father_id id of the father
     * @param int child_id id of the child
     *
     * @return object link
     */
    public function getItem($father_id, $child_id = null)
    {
        $retour = null;
        $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).".from_item_id = '".encode(AS_DB, $father_id)."' AND ".$this->addDatabasePrefix($this->_db_table).".to_item_id = '".encode(AS_DB, $child_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
            trigger_error('Problems selecting one tag link item from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $retour = $this->_buildItem($result[0]);
        }

        return $retour;
    }

    /** get a link.
     *
     * @param int link_id id of the link
     *
     * @return object link
     */
    private function _getItemTo($to_id)
    {
        $retour = null;
        $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).".to_item_id = '".encode(AS_DB, $to_id)."' AND deletion_date is NULL and deleter_id IS NULL;";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
            trigger_error('Problems selecting one tag link item: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $retour = $this->_buildItem($result[0]);
        }

        return $retour;
    }

     /** build a new tag2tag item
      * this method returns a new EMTPY tag link item.
      *
      * @return object cs_tag2tag_item a new EMPTY tag link item
      */
     public function getNewItem()
     {
         return new cs_tag2tag_item($this->_environment);
     }

    /** update a tag2tag - internal, do not use -> use method save
     * this method updates a tag2tag.
     *
     * @param object cs_tag2tag_item tag2tag_item the link tag - tag
     */
    public function _update($item)
    {
        $current_datetime = getCurrentDateTimeInMySQL();

        if ($item->getSortingPlace()) {
            $sorting_place = '"'.$item->getSortingPlace().'"';
        } else {
            $sorting_place = 'NULL';
        }

        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'from_item_id="'.encode(AS_DB, $item->getFatherItemID()).'",'.
                 'modifier_id="'.encode(AS_DB, $item->getModifierItemID()).'",'.
                 'modification_date="'.$current_datetime.'",'.
                 'sorting_place='.encode(AS_DB, $sorting_place).''.
                 ' WHERE link_id="'.encode(AS_DB, $item->getLinkID()).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating tag link from query: "'.$query.'"', E_USER_WARNING);
        }
        unset($item);
    }

    /** create a tag2tag - internal, do not use -> use method save
     * this method creates a tag2tag.
     *
     * @param object cs_tag2tag_item tag2tag_item the link tag - tag
     */
    private function _create($item)
    {
        $this->_newTag2TagLink($item);
        unset($item);
    }

    /** creates a new tag2tag - internal, do not use -> use method save
     * this method creates a new tag link.
     *
     * @param object cs_tag2tag_item tag2tag_item the link tag - tag
     */
    private function _newTag2TagLink($item)
    {
        $current_datetime = getCurrentDateTimeInMySQL();

        if ($item->getSortingPlace()) {
            $sorting_place = '"'.encode(AS_DB, $item->getSortingPlace()).'"';
        } else {
            $sorting_place = 'NULL';
        }

        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'from_item_id="'.encode(AS_DB, $item->getFatherItemID()).'",'.
                 'to_item_id="'.encode(AS_DB, $item->getChildItemID()).'",'.
                 'context_id="'.encode(AS_DB, $item->getContextItemID()).'",'.
                 'creator_id="'.encode(AS_DB, $item->getCreatorItemID()).'",'.
                 'creation_date="'.$current_datetime.'",'.
                 'modifier_id="'.encode(AS_DB, $item->getModifierItemID()).'",'.
                 'modification_date="'.$current_datetime.'",'.
                 'sorting_place='.$sorting_place;

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating tag2tag link from query: "'.$query.'"', E_USER_WARNING);
        }
        unset($item);
    }

    /** save a item
     * this method saves a item.
     *
     * @param cs_tag2tag_item
     */
    public function saveItem($item)
    {
        $modifier_id = $item->getModifierItemID();
        if (empty($modifier_id)) {
            $user = $this->_environment->getCurrentUser();
            $item->setModifierItemID($user->getItemID());
            unset($user);
        }

        $link_id = $item->getLinkID();
        if (!empty($link_id)) {
            $this->_update($item);
        } else {
            $creator_id = $item->getCreatorItemID();
            if (empty($creator_id)) {
                $user = $this->_environment->getCurrentUser();
                $item->setCreatorItemID($user->getItemID());
                unset($user);
            }
            $this->_create($item);
        }
        unset($item);
    }

    public function delete($father_id, $child_id = null): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user_id = $this->_current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE from_item_id="'.encode(AS_DB, $father_id).'" AND to_item_id="'.encode(AS_DB, $child_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting tag2tag link from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $this->_cleanSortingPlaces($father_id);
        }
    }

    public function deleteTagLinks($link_id)
    {
        $link_item = $this->_getItemTo($link_id);
        $father_id = $link_item->getFatherItemID();

        $current_datetime = getCurrentDateTimeInMySQL();
        $user_id = $this->_current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE from_item_id="'.encode(AS_DB, $link_id).'" OR to_item_id="'.encode(AS_DB, $link_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting tag2tag link from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $this->_cleanSortingPlaces($father_id);
        }
        unset($link_item);
    }

     public function deleteTagLinksFromToItemID($item_id)
     {
         $father_id = $this->getFatherItemID($item_id);

         $current_datetime = getCurrentDateTimeInMySQL();
         $user_id = $this->_current_user->getItemID() ?: 0;
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                  'deletion_date="'.$current_datetime.'",'.
                  'deleter_id="'.encode(AS_DB, $user_id).'"'.
                  ' WHERE from_item_id="'.encode(AS_DB, $father_id).'" AND to_item_id="'.encode(AS_DB, $item_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems deleting tag2tag link from query: "'.$query.'"', E_USER_WARNING);
         } else {
             $this->_cleanSortingPlaces($father_id);
         }
     }

     public function deleteTagLinksForTag($item_id)
     {
         $father_id = $this->getFatherItemID($item_id);
         $children_array = $this->getChildrenItemIDArray($item_id);

         $current_datetime = getCurrentDateTimeInMySQL();
         $user_id = $this->_current_user->getItemID() ?: 0;
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                  'deletion_date="'.$current_datetime.'",'.
                  'deleter_id="'.encode(AS_DB, $user_id).'"'.
                  ' WHERE from_item_id="'.encode(AS_DB, $item_id).'" OR to_item_id="'.encode(AS_DB, $item_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems deleting tag2tag link from query: "'.$query.'"', E_USER_WARNING);
         } else {
             $this->_cleanSortingPlaces($father_id);
             if (!empty($children_array)) {
                 $tag_manager = $this->_environment->getTagManager();
                 foreach ($children_array as $child_id) {
                     $tag_manager->delete($child_id);
                 }
                 unset($tag_manager);
             }
         }
     }

     public function getFatherItemID($item_id)
     {
         $retour = '';
         if ((is_countable($this->cachedFatherIdArray) ? count($this->cachedFatherIdArray) : 0) == 0) {
             if (empty($this->cachedRows)) {
                 $this->_cacheAllLinkRows();
             }
             foreach ($this->cachedRows as $db_row) {
                 $this->cachedFatherIdArray[$db_row['to_item_id']] = $db_row['from_item_id'];
             }
         }

         if (!empty($this->cachedFatherIdArray[$item_id])) {
             $retour = $this->cachedFatherIdArray[$item_id];
         }

         return $retour;
     }

     public function getGrandFatherItemID($item_id)
     {
         $retour = '';
         $array = $this->getFatherItemIDArray($item_id);
         if (!empty($array)) {
             $retour = array_pop($array);
         }

         return $retour;
     }

     public function resetCachedFatherIdArray()
     {
         $this->cachedFatherIdArray = [];
         $this->cachedRows = [];
     }

     public function getFatherItemIDArray($item_id)
     {
         $retour = [];
         $father_id = $this->getFatherItemID($item_id);
         while (!empty($father_id)) {
             $retour[] = $father_id;
             $father_id = $this->getFatherItemID($father_id);
         }
         array_pop($retour);

         return $retour;
     }

     public function resetCachedChildrenIdArray()
     {
         unset($this->cachedChildrenIdArray);
         $this->cachedRows = [];
     }

     public function getChildrenItemIDArray($item_id)
     {
         if (!isset($this->cachedChildrenIdArray)) {
             if (empty($this->cachedRows)) {
                 $this->_cacheAllLinkRows();
             }
             $this->cachedChildrenIdArray = [];
             foreach ($this->cachedRows as $db_row) {
                 $this->cachedChildrenIdArray[$db_row['from_item_id']][] = $db_row['to_item_id'];
             }
         }

         if (!empty($this->cachedChildrenIdArray[$item_id])) {
             return $this->cachedChildrenIdArray[$item_id];
         }

         return [];
     }

     public function getRecursiveChildrenItemIDArray($item_id)
     {
         $retour = [];
         $children = $this->getChildrenItemIDArray($item_id);
         if (!empty($children)) {
             $retour = array_merge($retour, $children);
             foreach ($children as $child_item_id) {
                 $retour = array_merge($retour, $this->getRecursiveChildrenItemIDArray($child_item_id));
             }
         }

         return $retour;
     }

     public function countChildren($item_id)
     {
         return is_countable($this->getChildrenItemIDArray($item_id)) ? count($this->getChildrenItemIDArray($item_id)) : 0;
     }

     private function _cleanSortingPlaces($item_id)
     {
         if (isset($item_id)) {
             $query = 'SELECT link_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id = '.encode(AS_DB, $item_id).' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL ORDER BY sorting_place ASC;';
             $result = $this->_db_connector->performQuery($query);
             $link_id_array = [];
             if (!isset($result)) {
                 trigger_error('Problems cleaning sorting place for father item id (GET) '.encode(AS_DB, $item_id).' from query: "'.$query.'"', E_USER_WARNING);
             } else {
                 foreach ($result as $result_array) {
                     $link_id_array[] = $result_array['link_id'];
                 }
             }
             $counter = 1;
             foreach ($link_id_array as $link_id) {
                 $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB, $counter).' WHERE link_id='.encode(AS_DB, $link_id);
                 $result = $this->_db_connector->performQuery($query);
                 if (!isset($result) or !$result) {
                     trigger_error('Problems cleaning sorting place for father item id (UPDATE) '.encode(AS_DB, $item_id).' from query: "'.$query.'"', E_USER_WARNING);
                 }
                 ++$counter;
             }
         }
     }

     public function deleteAllTagLinks($context_id)
     {
         $current_user = $this->_environment->getCurrentUserItem();
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id='.encode(AS_DB, $current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB, $context_id);
         unset($current_user);
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems deleting items from query: "'.$query.'"', E_USER_WARNING);
         }
     }

     /** get all links
      * this method get all links.
      */
     public function _performQuery($mode = '')
     {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
         $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' WHERE 1';

         if (isset($this->_room_limit)) {
             $query .= ' AND context_id="'.encode(AS_DB, $this->_room_limit).'"';
         }
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
         $query .= ' ORDER BY sorting_place';

         $result = $this->_db_connector->performQuery($query);

         if (!isset($result)) {
             trigger_error('Problems with links from query: "'.$query.'"', E_USER_WARNING);
         } else {
             return $result;
         }
     }

     public function insert($item_id, $father_id, $place = '')
     {
         $tag2tag_item = $this->getNewItem();
         $tag2tag_item->setFatherItemID($father_id);
         $tag2tag_item->setContextItemID($this->_environment->getCurrentContextID());
         $tag2tag_item->setChildItemID($item_id);
         $tag2tag_item->save();
         if (!empty($place)) {
             $this->change($item_id, $father_id, $place);
         }
     }

     public function change($item_id, $father_id, $place)
     {
         // select all links from father
         $query = 'SELECT link_id,from_item_id,to_item_id,sorting_place FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id = '.encode(AS_DB, $father_id).' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL ORDER BY sorting_place ASC;';
         $result = $this->_db_connector->performQuery($query);
         $link_id_array = [];
         if (!isset($result)) {
             trigger_error('Problems cleaning sorting place for father item id (GET) '.encode(AS_DB, $item_id).' from query: "'.$query.'"', E_USER_WARNING);
         } else {
             $old_place = '';
             $link_id = '';
             foreach ($result as $result_array) {
                 if ($result_array['to_item_id'] == $item_id) {
                     $old_place = $result_array['sorting_place'];
                     $link_id = $result_array['link_id'];
                     break;
                 }
             }

             if (empty($old_place) and empty($link_id)) {
                 $this->deleteTagLinksFromToItemID($item_id);
                 $this->insert($item_id, $father_id, $place);
             } elseif (empty($old_place)) {
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place+1 WHERE from_item_id='.encode(AS_DB, $father_id).' AND sorting_place >= '.$place.';';
                 $result = $this->_db_connector->performQuery($update);
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB, $place).' WHERE link_id='.encode(AS_DB, $link_id).';';
                 $result = $this->_db_connector->performQuery($update);
             } elseif ($old_place < $place) {
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place-1 WHERE from_item_id='.encode(AS_DB, $father_id).' AND sorting_place > '.$old_place.' AND sorting_place <= '.$place.';';
                 $result = $this->_db_connector->performQuery($update);
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB, $place).' WHERE link_id='.encode(AS_DB, $link_id).';';
                 $result = $this->_db_connector->performQuery($update);
             } elseif ($old_place > $place) {
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place+1 WHERE from_item_id='.encode(AS_DB, $father_id).' AND sorting_place < '.$old_place.' AND sorting_place >= '.$place.';';
                 $result = $this->_db_connector->performQuery($update);
                 $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB, $place).' WHERE link_id='.encode(AS_DB, $link_id).';';
                 $result = $this->_db_connector->performQuery($update);
             }
         }
     }

     public function changeUpdate($item_id, $place)
     {
         $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB, $place).' WHERE to_item_id='.encode(AS_DB, $item_id).';';
         $result = $this->_db_connector->performQuery($update);
     }

     /**
      * Combines two categories to one.
      *
      * @param $item_id_1 first item id
      * @param $item_id_2 second item id
      * @param $father_id father id under which the combined categorie will be inserted
      */
     public function combine($item_id_1, $item_id_2, $father_id)
     {
         // get children of both items
         $childrenIdArrayItem_1 = $this->getChildrenItemIDArray($item_id_1);
         $childrenIdArrayItem_2 = $this->getChildrenItemIDArray($item_id_2);

         // get item titles
         $tag_manager = $this->_environment->getTagManager();
         $item_1 = $tag_manager->getItem($item_id_1);
         $item_title_1 = $item_1->getTitle();
         $item_2 = $tag_manager->getItem($item_id_2);
         $item_title_2 = $item_2->getTitle();

         // get all linked items
         $linkedIDsItem_1 = $item_1->getAllLinkedItemIDArray();
         $linkedIDsItem_2 = $item_2->getAllLinkedItemIDArray();

         // delete tags, but keep children alive
         if ($this->isASuccessorOfB($item_id_1, $item_id_2)) {
             $tag_manager->delete($item_id_1, false);
             $tag_manager->delete($item_id_2, false);
         } else {
             $tag_manager->delete($item_id_2, false);
             $tag_manager->delete($item_id_1, false);
         }

         unset($item_1);
         unset($item_2);

         // create new tag and set linked items
         $mergedLinkedIDs = array_unique(array_merge($linkedIDsItem_1, $linkedIDsItem_2));

         $new = $tag_manager->getNewItem();
         $new->setTitle($item_title_1.'/'.$item_title_2);
         $new->setContextID($this->_environment->getCurrentContextID());
         $new->setCreatorItem($this->_environment->getCurrentUserItem());
         $new->setCreationDate(getCurrentDateTimeInMySQL());
         $new->setLinkedItemsByIDArray($mergedLinkedIDs);

         // set position
         $new->setPosition($father_id, $this->countChildren($father_id));

         // save
         $new->save();

         // link old childrens to new tag
         $new_id = $new->getItemID();
         $count = 1;
         foreach (array_merge($childrenIdArrayItem_1, $childrenIdArrayItem_2) as $item_id) {
             // get item
             $item = $tag_manager->getItem($item_id);

             // set position
             $item->setPosition($new_id, $count);
             $item->save();

             unset($item);
             ++$count;
         }

         unset($tag_manager);
         unset($new);
     }

     public function isASuccessorOfB($itemIdA, $itemIdB)
     {
         // get all father items of item A
         $aFatherArray = $this->getFatherItemIDArray($itemIdA);

         if (in_array($itemIdB, $aFatherArray)) {
             return true;
         }

         return false;
     }

     private function _cacheAllLinkRows()
     {
         $this->cachedRows = $this->_performQuery();
     }

     public function _updateFromBackup($data_array)
     {
         $success = false;

         // is entry allready stored in database ?
         $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' WHERE link_id="'.encode(AS_DB, $data_array['link_id']).'"';

         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problem deleting items from query: "'.$query.'"', E_USER_ERROR);
         } else {
             // now the backup
             $query = '';
             if (empty($result[0])) {
                 $query .= 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).'';
             } else {
                 $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';
             }

             $query .= ' SET ';
             $first = true;

             foreach ($data_array as $key => $value) {
                 if (empty($result[0])
                      or 'link_id' != $key
                 ) {
                     if ($first) {
                         $first = false;
                     } else {
                         $query .= ',';
                     }
                     $query .= $key.'="'.encode(AS_DB, $value).'"';
                 }
             }

             if (strstr($query, 'deleter_id="0"')) {
                 $query = str_replace('deleter_id="0"', 'deleter_id=NULL', $query);
             }
             if (strstr($query, 'deletion_date="0"')) {
                 $query = str_replace('deletion_date="0"', 'deletion_date=NULL', $query);
             }
             if (strstr($query, 'sorting_place="0"')) {
                 $query = str_replace('sorting_place="0"', 'sorting_place=NULL', $query);
             }

             if (!empty($result[0])) {
                 $query .= ' WHERE link_id="'.encode(AS_DB, $data_array['link_id']).'"';
             }
             $query .= ';';

             $result = $this->_db_connector->performQuery($query);
             if (!isset($result) or !$result) {
                 trigger_error('Problem backuping item from query: "'.$query.'"', E_USER_ERROR);
             } else {
                 $success = true;
             }
         }

         return $success;
     }
}
