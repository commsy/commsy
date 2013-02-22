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

include_once('functions/text_functions.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** class for database connection to the database table "homepage_link_page_page"
 * this class implements a database manager for the table "homepage_link_page_page"
 */
class cs_homepagelink_manager extends cs_manager {

  /** constructor: cs_homepage_manager
    * the only available constructor, initial values for internal variables
    */
  function __construct ($environment) {
     $this->cs_manager($environment);
    $this->_db_table = CS_LINKHOMEPAGEHOMEPAGE_TYPE;
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
     return $retour;
  }

  /** get a link
    *
    * @param integer father_id id of the father
    * @param integer child_id id of the child
    *
    * @return object link
    */
  public function getItem ($father_id, $child_id) {
     $retour = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".from_item_id = '".encode(AS_DB,$father_id)."' AND ".$this->addDatabasePrefix($this->_db_table).".to_item_id = '".encode(AS_DB,$child_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0])) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting one homepage link item from query: "'.$query.'"',E_USER_WARNING);
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
        include_once('functions/error_functions.php');trigger_error('Problems selecting one homepage link item: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $retour = $this->_buildItem($result[0]);
     }
     return $retour;
  }

   /** build a new homepage item
    * this method returns a new EMTPY homepage link item
    *
    * @return object cs_homepagelink_item a new EMPTY homepage link item
    */
   public function getNewItem () {
      include_once('classes/cs_homepagelink_item.php');
      return new cs_homepagelink_item($this->_environment);
   }


  /** update an homepage - internal, do not use -> use method save
    * this method updates an homepage
    *
    * @param object cs_homepagelink_item homepagelink_item the link homepage - homepage
    */
  function _update ($homepagelink_item) {
     $current_datetime = getCurrentDateTimeInMySQL();

     $query  = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'from_item_id="'.encode(AS_DB,$homepagelink_item->getFatherItemID()).'",'.
               'modifier_id="'.encode(AS_DB,$homepagelink_item->getModifierItemID()).'",'.
               'modification_date="'.$current_datetime.'",';
     if ($homepagelink_item->getSortingPlace()) {
        $query .= 'sorting_place="'.encode(AS_DB,$homepagelink_item->getSortingPlace()).'"';
     } else {
        $query .= 'sorting_place=NULL';
     }
     $query .= ' WHERE link_id="'.encode(AS_DB,$homepagelink_item->getLinkID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating homepage link from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  /** create a homepage - internal, do not use -> use method save
    * this method creates a homepage
    *
    * @param object cs_homepagelink_item homepagelink_item the link homepage - homepage
    */
  private function _create ($homepagelink_item) {
     $this->_newHomepageLink($homepagelink_item);
     unset($homepagelink_item);
  }

  /** creates a new homepage - internal, do not use -> use method save
    * this method creates a new homepage link
    *
    * @param object cs_homepagelink_item homepagelink_item the link homepage - homepage
    */
  private function _newHomepageLink ($homepagelink_item) {
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($homepagelink_item->getSortingPlace()) {
        $sorting_place = '"'.encode(AS_DB,$homepagelink_item->getSortingPlace()).'"';
     } else {
        $sorting_place = 'NULL';
     }

     $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'from_item_id="'.encode(AS_DB,$homepagelink_item->getFatherItemID()).'",'.
              'to_item_id="'.encode(AS_DB,$homepagelink_item->getChildItemID()).'",'.
              'context_id="'.encode(AS_DB,$homepagelink_item->getContextItemID()).'",'.
              'creator_id="'.encode(AS_DB,$homepagelink_item->getCreatorItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$homepagelink_item->getModifierItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'sorting_place='.$sorting_place;

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating homepage link from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($homepagelink_item);
  }

  /** save a item
    * this method saves a item
    *
    * @param cs_homepagelink_item
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

  function delete ($father_id, $child_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE from_item_id="'.encode(AS_DB,$father_id).'" AND to_item_id="'.encode(AS_DB,$child_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting homepage link from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  function deleteHomepageLinks ($link_id) {
     $link_item = $this->_getItemTo($link_id);
     $father_id = $link_item->getFatherItemID();

     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE from_item_id="'.encode(AS_DB,$link_id).'" OR to_item_id="'.encode(AS_DB,$link_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting homepage link from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $this->_cleanSortingPlaces($father_id);
     }
     unset($link_item);
  }

   public function getFatherItemID ($item_id) {
      $retour = '';
      $query = 'SELECT from_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE to_item_id="'.encode(AS_DB,$item_id).'";';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting father item id from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $result_array = $result[0];
         if ( !empty($result_array['from_item_id']) ) {
            $retour = $result_array['from_item_id'];
         }
      }
      return $retour;
   }

   public function getGrandFatherItemID ($item_id) {
      $retour = '';
      if ( !empty($item_id) ) {
         $item_id_before = '';
         $run = true;
         while ($run) {
            $query = 'SELECT from_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE to_item_id="'.encode(AS_DB,$item_id).'";';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting father item id from query: "'.$query.'"',E_USER_WARNING);
               $run = false;
            } elseif ( !empty($result[0]) ) {
               $result_array = $result[0];
               if ( empty($result_array['from_item_id']) ) {
                  $retour = $item_id_before;
                  $run = false;
               } else {
                  $item_id_before = $item_id;
                  $item_id = $result_array['from_item_id'];
               }
            } else {
               $retour = $item_id_before;
               $run = false;
            }
         }
      }
      return $retour;
   }

   public function getFatherItemIDArray ($item_id) {
      $retour = array();
      $run = true;
      while ($run) {
         $query = 'SELECT from_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE to_item_id="'.encode(AS_DB,$item_id).'";';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting father item id from query: "'.$query.'"',E_USER_WARNING);
            $run = false;
         } elseif ( !empty($result) ) {
            $result_array = $result[0];
            if ( empty($result_array['from_item_id']) ) {
               $run = false;
            } else {
               $item_id = $result_array['from_item_id'];
               $retour[] = $item_id;
            }
         } else {
            $run = false;
         }
      }
      $retour = array_reverse($retour);
      return $retour;
   }

   public function countChildren ($item_id) {
      $retour = 0;
      $query  = 'SELECT count(to_item_id) AS count FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' INNER JOIN '.$this->addDatabasePrefix('homepage_page').' ON '.$this->addDatabasePrefix('homepage_page').'.item_id='.$this->addDatabasePrefix($this->_db_table).'.to_item_id';
      $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id="'.encode(AS_DB,$item_id).'" AND '.$this->addDatabasePrefix('homepage_page').'.page_type="CHILD" AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL;';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');trigger_error('Problems counting children of father item id from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $result_array = $result[0];
         if ( !empty($result_array['count']) ) {
            $retour = $result_array['count'];
         }
      }
      return $retour;
   }

   private function _moveUpDown ($direction, $homepage_id, $father_id) {
      $query = 'SELECT sorting_place FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND to_item_id = '.encode(AS_DB,$homepage_id);
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');trigger_error('Problems get sorting place from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $result_array = $result[0];
         if ( !empty($result_array['sorting_place']) ) {
            $sorting_place = $result_array['sorting_place'];
            if ($direction == 'up') {
               $sorting_place--;
            } elseif ($direction == 'down') {
               $sorting_place++;
            }
            $query = 'SELECT to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND sorting_place = '.encode(AS_DB,$sorting_place);
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result) or empty($result[0])) {
               include_once('functions/error_functions.php');trigger_error('Problems get other homepage id from query: "'.$query.'"',E_USER_WARNING);
            } else {
               $result_array = $result[0];
               if ( !empty($result_array['to_item_id']) ) {
                  $to_item_id = $result_array['to_item_id'];
                  if ($direction == 'up') {
                     $new_sorting_place = 'sorting_place+1';
                  } elseif ($direction == 'down') {
                     $new_sorting_place = 'sorting_place-1';
                  } else {
                     $new_sorting_place = 'sorting_place';
                  }
                  $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$new_sorting_place).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND to_item_id = '.encode(AS_DB,$to_item_id);
                  $result = $this->_db_connector->performQuery($query);
                  if (!isset($result) or !$result) {
                     include_once('functions/error_functions.php');trigger_error('Problems update sortingplace from query: "'.$query.'"',E_USER_WARNING);
                  } else {
                     if ($direction == 'up') {
                        $new_sorting_place = 'sorting_place-1';
                     } elseif ($direction == 'down') {
                        $new_sorting_place = 'sorting_place+1';
                     } else {
                        $new_sorting_place = 'sorting_place';
                     }
                     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place='.encode(AS_DB,$new_sorting_place).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND to_item_id = '.encode(AS_DB,$homepage_id);
                     $result = $this->_db_connector->performQuery($query);
                     if (!isset($result) or !$result) {
                        include_once('functions/error_functions.php');trigger_error('Problems update sortingplace from query: "'.$query.'"',E_USER_WARNING);
                     }
                  }
               }
            }
         }
      }
   }

   public function moveUp ($homepage_id, $father_id) {
      $this->_moveUpDown('up',$homepage_id,$father_id);
   }

   public function moveDown ($homepage_id, $father_id) {
      $this->_moveUpDown('down',$homepage_id,$father_id);
   }

   public function moveLeft ($homepage_id, $father_id) {
      $new_father_item_id = $this->getFatherItemID($father_id);
      $count_children = $this->countChildren($new_father_item_id);
      $link_item = $this->getItem($father_id,$homepage_id);
      $link_item->setFatherItemID($new_father_item_id);
      $new_sorting_place = $count_children+1;
      $link_item->setSortingPlace($new_sorting_place);
      $link_item->save();
      $this->_cleanSortingPlaces($father_id);
      unset($link_item);
   }

   private function _cleanSortingPlaces ($item_id) {
      $query = 'SELECT link_id FROM '.$this->addDatabasePrefix($this->_db_table).' INNER JOIN '.$this->addDatabasePrefix('homepage_page').' ON '.$this->addDatabasePrefix('homepage_page').'.item_id = '.$this->addDatabasePrefix($this->_db_table).'.to_item_id WHERE '.$this->addDatabasePrefix($this->_db_table).'.from_item_id = '.encode(AS_DB,$item_id).' AND '.$this->addDatabasePrefix('homepage_page').'.page_type="CHILD" AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date is NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL ORDER BY sorting_place ASC;';
      $result = $this->_db_connector->performQuery($query);
      $link_id_array = array();
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems cleaning sorting place for father item id (GET) '.encode(AS_DB,$item_id).' from query: "'.$query.'"',E_USER_WARNING);
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
            include_once('functions/error_functions.php');trigger_error('Problems cleaning sorting place for father item id (UPDATE) '.encode(AS_DB,$item_id).' from query: "'.$query.'"',E_USER_WARNING);
         }
         $counter++;
      }
   }

   public function moveRight ($homepage_id, $father_id) {
      $query = 'SELECT sorting_place FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND to_item_id = '.encode(AS_DB,$homepage_id);
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');trigger_error('Problems get sorting place: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $result_array = $result[0];
         if ( !empty($result_array['sorting_place']) ) {
            $sorting_place = $result_array['sorting_place'];
            $sorting_place--;
            $query = 'SELECT to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE from_item_id = '.encode(AS_DB,$father_id).' AND sorting_place = '.encode(AS_DB,$sorting_place);
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result) or empty($result[0])) {
               include_once('functions/error_functions.php');trigger_error('Problems get other homepage id from query: "'.$query.'"',E_USER_WARNING);
            } else {
               $result_array = $result[0];
               if ( !empty($result_array['to_item_id']) ) {
                  $new_father_item_id = $result_array['to_item_id'];
                  $count_children = $this->countChildren($new_father_item_id);
                  $link_item = $this->getItem($father_id,$homepage_id);
                  $link_item->setFatherItemID($new_father_item_id);
                  $new_sorting_place = $count_children+1;
                  $link_item->setSortingPlace($new_sorting_place);
                  $link_item->save();
                  $this->_cleanSortingPlaces($father_id);
                  unset($link_item);
               }
            }
         }
      }
   }

   public function deleteAllHomepageLinks ($context_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id='.encode(AS_DB,$current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB,$context_id);
      unset($current_user);
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problems deleting items from query: "'.$query.'"',E_USER_WARNING);
      }
   }

  /** get all links
    * this method get all links
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
   public function _performQuery ($mode = 'select') {
      $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE 1';

      if ( isset($this->_room_limit) ) {
         $query .= ' AND context_id="'.encode(AS_DB,$this->_room_limit).'"';
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems with links from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }
}
?>