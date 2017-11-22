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

/** class for database connection to the database table "tag2tag"
 * this class implements a database manager for the table "tag2tag"
 */
class cs_tag2tag_manager extends cs_manager {

  /** constructor: cs_tag2tag_manager
    * the only available constructor, initial values for internal variables
    */
  function __construct ($environment) {
     cs_manager::__construct($environment);
     $this->_db_table = CS_TAG2TAG_TYPE;
     $this->_cached_rows = array();
     $this->_cached_father_id_array = array();
     $this->_cached_children_id_array_array = NULL;
  }

  function _buildItem ($data_array) {
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

  /** get a link
    *
    * @param integer father_id id of the father
    * @param integer child_id id of the child
    *
    * @return object link
    */
  public function getItem ($father_id, $child_id = null) {
     $retour = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".from_item_id = '".encode(AS_DB,$father_id)."' AND ".$this->addDatabasePrefix($this->_db_table).".to_item_id = '".encode(AS_DB,$child_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0])) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting one tag link item from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $retour = $this->_buildItem($result[0]);
     }
     return $retour;
  }

  /** get a link
    *
    * @param integer link_id id of the link
    *
    * @return object link
    */
  private function _getItemTo ($to_id) {
     $retour = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".to_item_id = '".encode(AS_DB,$to_id)."' AND deletion_date is NULL and deleter_id IS NULL;";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0])) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting one tag link item: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $retour = $this->_buildItem($result[0]);
     }
     return $retour;
  }

   /** build a new tag2tag item
    * this method returns a new EMTPY tag link item
    *
    * @return object cs_tag2tag_item a new EMPTY tag link item
    */
   public function getNewItem () {
      include_once('classes/cs_tag2tag_item.php');
      return new cs_tag2tag_item($this->_environment);
   }

  /** update a tag2tag - internal, do not use -> use method save
    * this method updates a tag2tag
    *
    * @param object cs_tag2tag_item tag2tag_item the link tag - tag
    */
  function _update ($item) {
     include_once('functions/date_functions.php');
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($item->getSortingPlace()) {
        $sorting_place = '"'.$item->getSortingPlace().'"';
     } else {
        $sorting_place = 'NULL';
     }

     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'from_item_id="'.encode(AS_DB,$item->getFatherItemID()).'",'.
              'modifier_id="'.encode(AS_DB,$item->getModifierItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'sorting_place='.encode(AS_DB,$sorting_place).''.
              ' WHERE link_id="'.encode(AS_DB,$item->getLinkID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating tag link from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($item);
  }

  /** create a tag2tag - internal, do not use -> use method save
    * this method creates a tag2tag
    *
    * @param object cs_tag2tag_item tag2tag_item the link tag - tag
    */
  private function _create ($item) {
     $this->_newTag2TagLink($item);
     unset($item);
  }

  /** creates a new tag2tag - internal, do not use -> use method save
    * this method creates a new tag link
    *
    * @param object cs_tag2tag_item tag2tag_item the link tag - tag
    */
  private function _newTag2TagLink ($item) {
     include_once('functions/date_functions.php');
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($item->getSortingPlace()) {
        $sorting_place = '"'.encode(AS_DB,$item->getSortingPlace()).'"';
     } else {
        $sorting_place = 'NULL';
     }

     $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'from_item_id="'.encode(AS_DB,$item->getFatherItemID()).'",'.
              'to_item_id="'.encode(AS_DB,$item->getChildItemID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextItemID()).'",'.
              'creator_id="'.encode(AS_DB,$item->getCreatorItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$item->getModifierItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'sorting_place='.$sorting_place;

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating tag2tag link from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($item);
  }

  /** save a item
    * this method saves a item
    *
    * @param cs_tag2tag_item
    */
  function saveItem ($item) {
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

  function delete ($father_id, $child_id = null) {
     include_once('functions/date_functions.php');
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE from_item_id="'.encode(AS_DB,$father_id).'" AND to_item_id="'.encode(AS_DB,$child_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting tag2tag link from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $this->_cleanSortingPlaces($father_id);
     }
  }

  function deleteTagLinks ($link_id) {
     $link_item = $this->_getItemTo($link_id);
     $father_id = $link_item->getFatherItemID();

     include_once('functions/date_functions.php');
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE from_item_id="'.encode(AS_DB,$link_id).'" OR to_item_id="'.encode(AS_DB,$link_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting tag2tag link from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $this->_cleanSortingPlaces($father_id);
     }
     unset($link_item);
  }

   function deleteTagLinksFromToItemID ($item_id) {

      $father_id = $this->getFatherItemID($item_id);

      include_once('functions/date_functions.php');
      $current_datetime = getCurrentDateTimeInMySQL();
      $user_id = $this->_current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE from_item_id="'.encode(AS_DB,$father_id).'" AND to_item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting tag2tag link from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $this->_cleanSortingPlaces($father_id);
      }
   }

   public function deleteTagLinksForTag ( $item_id ) {

      $father_id = $this->getFatherItemID($item_id);
      $children_array = $this->getChildrenItemIDArray($item_id);

      include_once('functions/date_functions.php');
      $current_datetime = getCurrentDateTimeInMySQL();
      $user_id = $this->_current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE from_item_id="'.encode(AS_DB,$item_id).'" OR to_item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting tag2tag link from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $this->_cleanSortingPlaces($father_id);
         if ( !empty($children_array) ) {
            $tag_manager = $this->_environment->getTagManager();
            foreach ($children_array as $child_id) {
               $tag_manager->delete($child_id);
            }
            unset($tag_manager);
         }
      }
   }

   public function getFatherItemID ($item_id) {
      $retour = '';
      if ( count($this->_cached_father_id_array) == 0 ) {
         if ( empty($this->_cached_rows) ) {
            $this->_cacheAllLinkRows();
         }
         foreach ( $this->_cached_rows as $db_row ) {
            $this->_cached_father_id_array[$db_row['to_item_id']] = $db_row['from_item_id'];
         }
      }

      if ( !empty($this->_cached_father_id_array[$item_id]) ) {
         $retour = $this->_cached_father_id_array[$item_id];
      }

      return $retour;
   }

   public function getGrandFatherItemID ($item_id) {
      $retour = '';
      $array = $this->getFatherItemIDArray($item_id);
      if ( !empty($array) ) {
         $retour = array_pop($array);
      }
      return $retour;
   }

   public function resetCachedFatherIdArray(){
   	  $this->_cached_father_id_array = array();
   	  $this->_cached_rows = array();
   }

   public function getFatherItemIDArray ($item_id) {
      $retour = array();
      $father_id = $this->getFatherItemID($item_id);
      while ( !empty($father_id) ) {
         $retour[] = $father_id;
         $father_id = $this->getFatherItemID($father_id);
      }
      array_pop($retour);
      return $retour;
   }

   public function resetCachedChildrenIdArray(){
   	unset($this->_cached_children_id_array_array);
   	$this->_cached_rows = array();
   }

   public function getChildrenItemIDArray ($item_id) {
      $retour = array();
      if ( !isset($this->_cached_children_id_array_array) ) {
         if ( empty($this->_cached_rows) ) {
            $this->_cacheAllLinkRows();
         }
         foreach ( $this->_cached_rows as $db_row ) {
            $this->_cached_children_id_array_array[$db_row['from_item_id']][] = $db_row['to_item_id'];
         }
      }
      if ( !empty($this->_cached_children_id_array_array[$item_id]) ) {
         $retour = $this->_cached_children_id_array_array[$item_id];
      }
      return $retour;
   }

   public function getRecursiveChildrenItemIDArray ($item_id) {
      $retour = array();
      $children = $this->getChildrenItemIDArray($item_id);
      if ( !empty($children) ) {
         $retour = array_merge($retour,$children);
         foreach ( $children as $child_item_id ) {
            $retour = array_merge($retour,$this->getRecursiveChildrenItemIDArray($child_item_id));
         }
      }
      return $retour;
   }

   public function sortRecursiveABC($root_id) {
      //////////////////////////////
      // "0." force cache reset ////
      //////////////////////////////
//      $this->_cached_rows = array();
//      $this->_cached_father_id_array = array();
//      $this->_cached_children_id_array_array = NULL;
      //////////////////////////////
      //////////////////////////////
      //////////////////////////////

      // 1. fetch all direct children
      $children_array = $this->getChildrenItemIDArray($root_id);

      // 2. sort children
      if(sizeof($children_array) > 1) {
         $temp_array = array();
         $tag_manager = $this->_environment->getTagManager();
         foreach($children_array as $id) {
            $item = $tag_manager->getItem($id);
            if(isset($item)){
	            $item_title = $item->getTitle();
	            array_push($temp_array, array(   'id'   =>   $id,
	                                             'name' =>   $item_title));
            }
         }

         usort(   $temp_array,
                  create_function('$a,$b','return strnatcasecmp($a[\'name\'],$b[\'name\']);'));

         // 3. change sort order
         foreach($children_array as $id) {
            $item = $tag_manager->getItem($id);

            // get new position
            $new_sort_order = 1;
            foreach($temp_array as $item_info_array) {
               if($item_info_array['id'] == $id) {
                  break;
               }
               $new_sort_order++;
            }

            // change sort order and save
            if(isset($item)){
	            $item->setPosition($root_id, $new_sort_order);
	            $item->setSavePositionWithoutChange(true);
	            $item->save();
	            unset($item);
             // 4. call recursive
            $this->sortRecursiveABC($id);
            }
         }
         unset($tag_manager);
      }
   }

   public function countChildren ($item_id) {
      return count($this->getChildrenItemIDArray($item_id));
   }

   private function _cleanSortingPlaces ($item_id) {
      if ( isset($item_id) ) {
         $query = 'SELECT link_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id = '.encode(AS_DB,$item_id).' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL ORDER BY sorting_place ASC;';
         $result = $this->_db_connector->performQuery($query);
         $link_id_array = array();
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems cleaning sorting place for father item id (GET) '.encode(AS_DB,$item_id).' from query: "'.$query.'"',E_USER_WARNING);
         } else {
            foreach ( $result as $result_array ) {
               $link_id_array[] = $result_array['link_id'];
            }
         }
         $counter = 1;
         foreach ($link_id_array as $link_id) {
            $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$counter).' WHERE link_id='.encode(AS_DB,$link_id);
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result) or !$result) {
               include_once('functions/error_functions.php');
               trigger_error('Problems cleaning sorting place for father item id (UPDATE) '.encode(AS_DB,$item_id).' from query: "'.$query.'"',E_USER_WARNING);
            }
            $counter++;
         }
      }
   }

   public function deleteAllTagLinks ($context_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id='.encode(AS_DB,$current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB,$context_id);
      unset($current_user);
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting items from query: "'.$query.'"',E_USER_WARNING);
      }
   }

  /** get all links
    * this method get all links
    */
   public function _performQuery ($mode = '') {
      $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE 1';

      if ( isset($this->_room_limit) ) {
         $query .= ' AND context_id="'.encode(AS_DB,$this->_room_limit).'"';
      }
      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      $query .= ' ORDER BY sorting_place';

      $result = $this->_db_connector->performQuery($query);

      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems with links from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   public function insert ($item_id,$father_id,$place='') {
      $tag2tag_item = $this->getNewItem();
      $tag2tag_item->setFatherItemID($father_id);
      $tag2tag_item->setContextItemID($this->_environment->getCurrentContextID());
      $tag2tag_item->setChildItemID($item_id);
      $tag2tag_item->save();
      if ( !empty($place) ) {
         $this->change($item_id,$father_id,$place);
      }
   }

   public function change ($item_id,$father_id,$place) {
      // select all links from father
      $query = 'SELECT link_id,from_item_id,to_item_id,sorting_place FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id = '.encode(AS_DB,$father_id).' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL ORDER BY sorting_place ASC;';
      $result = $this->_db_connector->performQuery($query);
      $link_id_array = array();
      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems cleaning sorting place for father item id (GET) '.encode(AS_DB,$item_id).' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $old_place = '';
         $link_id = '';
         foreach ( $result as $result_array ) {
            if ( $result_array['to_item_id'] == $item_id ) {
               $old_place = $result_array['sorting_place'];
               $link_id = $result_array['link_id'];
               break;
            }
         }

         if ( empty($old_place) and empty($link_id) ) {
            $this->deleteTagLinksFromToItemID($item_id);
            $this->insert($item_id,$father_id,$place);
         } elseif ( empty($old_place) ) {
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place+1 WHERE from_item_id='.encode(AS_DB,$father_id).' AND sorting_place >= '.$place.';';
            $result = $this->_db_connector->performQuery($update);
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$place).' WHERE link_id='.encode(AS_DB,$link_id).';';
            $result = $this->_db_connector->performQuery($update);
         } elseif ( $old_place < $place ) {
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place-1 WHERE from_item_id='.encode(AS_DB,$father_id).' AND sorting_place > '.$old_place.' AND sorting_place <= '.$place.';';
            $result = $this->_db_connector->performQuery($update);
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$place).' WHERE link_id='.encode(AS_DB,$link_id).';';
            $result = $this->_db_connector->performQuery($update);
         } elseif ( $old_place > $place ) {
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=sorting_place+1 WHERE from_item_id='.encode(AS_DB,$father_id).' AND sorting_place < '.$old_place.' AND sorting_place >= '.$place.';';
            $result = $this->_db_connector->performQuery($update);
            $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$place).' WHERE link_id='.encode(AS_DB,$link_id).';';
            $result = $this->_db_connector->performQuery($update);
         }
      }
   }
   
   public function changeUpdate ($item_id,$place) {
        $update = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$place).' WHERE to_item_id='.encode(AS_DB,$item_id).';';
        $result = $this->_db_connector->performQuery($update); 
   }

   /**
    * Combines two categories to one
    *
    * @param $item_id_1 first item id
    * @param $item_id_2 second item id
    * @param $father_id father id under which the combined categorie will be inserted
    */
   public function combine($item_id_1, $item_id_2, $father_id) {
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
      if ( $this->isASuccessorOfB($item_id_1, $item_id_2)) {
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
      $new->setTitle($item_title_1 . '/' . $item_title_2);
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
      foreach(array_merge($childrenIdArrayItem_1, $childrenIdArrayItem_2) as $item_id) {
         // get item
         $item = $tag_manager->getItem($item_id);

         // set position
         $item->setPosition($new_id, $count);
         $item->save();

         unset($item);
         $count++;
      }
      
      

      unset($tag_manager);
      unset($new);
   }
   
   /**
    * 
    */
   public function isASuccessorOfB($itemIdA, $itemIdB)
   {
   	  // get all father items of item A
   	  $aFatherArray = $this->getFatherItemIDArray($itemIdA);
   	  
   	  if (in_array($itemIdB, $aFatherArray)) {
   	  	return true;
   	  }
   	  
   	  return false;
   }

   private function _cacheAllLinkRows () {
      $this->_cached_rows = $this->_performQuery();
   }

   function _updateFromBackup ( $data_array ) {
      $success = false;

      // is entry allready stored in database ?
      $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE link_id="'.encode(AS_DB,$data_array['link_id']).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem deleting items from query: "'.$query.'"',E_USER_ERROR);
      } else {

         // now the backup
         $query = '';
         if ( empty($result[0]) ) {
            $query .= 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).'';
         } else {
            $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';
         }

         $query .= ' SET ';
         $first = true;

         foreach ($data_array as $key => $value) {
            if ( empty($result[0])
                 or $key != 'link_id'
               ) {
               if ($first) {
                  $first = false;
               } else {
                  $query .= ',';
               }
               $query .= $key.'="'.encode(AS_DB,$value).'"';
            }
         }

         if ( strstr($query,'deleter_id="0"') ) {
            $query = str_replace('deleter_id="0"','deleter_id=NULL',$query);
         }
         if ( strstr($query,'deletion_date="0"') ) {
            $query = str_replace('deletion_date="0"','deletion_date=NULL',$query);
         }
         if ( strstr($query,'sorting_place="0"') ) {
            $query = str_replace('sorting_place="0"','sorting_place=NULL',$query);
         }

         if ( !empty($result[0]) ) {
            $query .= ' WHERE link_id="'.encode(AS_DB,$data_array['link_id']).'"';
         }
         $query .= ';';

         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problem backuping item from query: "'.$query.'"',E_USER_ERROR);
         } else {
            $success = true;
         }
      }
      return $success;
   }
   
   public function insert_with_context ($item_id,$father_id,$place='', $context_id) {
      $tag2tag_item = $this->getNewItem();
      $tag2tag_item->setFatherItemID($father_id);
      $tag2tag_item->setContextItemID($context_id);
      $tag2tag_item->setChildItemID($item_id);
      $tag2tag_item->save();
      if ( !empty($place) ) {
         $this->change($item_id,$father_id,$place);
      }
   }
}
?>