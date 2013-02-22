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

/** class for database connection to the database table "homepage"
 * this class implements a database manager for the table "homepage_page"
 */
class cs_homepage_manager extends cs_manager {

  private $_sort_order = NULL;
  private $_father_limit = NULL;
  private $_index_limit = NULL;
  private $_interval_limit = NULL;
  private $_from_limit = NULL;

  /** constructor: cs_homepage_manager
    * the only available constructor, initial values for internal variables
    */
  function __construct ($environment) {
     $this->cs_manager($environment);
     $this->_db_table = CS_HOMEPAGE_TYPE;
  }

  /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    *
    * @author CommSy Development Group
    */
  public function resetLimits () {
     parent::resetLimits();
     $this->_father_limit = NULL;
     $this->_index_limit = NULL;
     $this->_sort_order = NULL;
     $this->_interval_limit = NULL;
     $this->_from_limit = NULL;
  }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected material
    * @param integer interval interval limit for selected material
    *
    * @author CommSy Development Group
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (int)$interval;
      $this->_from_limit = (int)$from;
   }

   public function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   public function setOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   public function getChildList ($father_id) {
      $this->reset();
      $this->setContextLimit($this->_environment->getCurrentContextID());
      $this->_father_limit = (int)$father_id;
      $this->select();
      return $this->get();
   }

   public function setIndexLimit () {
      $this->_index_limit = true;
   }

   function _performQuery ($mode = 'select') {

      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
      } elseif ($mode == 'id_array') {
          $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
      } elseif ( isset($this->_index_limit) and $this->_index_limit ) {
          $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*,'.$this->addDatabasePrefix('room').'.title AS room_title,'.$this->addDatabasePrefix('room').'.activity AS room_activity';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*,'.$this->addDatabasePrefix('homepage_link_page_page').'.sorting_place,'.$this->addDatabasePrefix('homepage_link_page_page').'.from_item_id';
      }
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table).'';
      if ( isset($this->_index_limit) and $this->_index_limit ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('room').' ON '.$this->addDatabasePrefix($this->_db_table).'.context_id='.$this->addDatabasePrefix('room').'.item_id';
      } else {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('homepage_link_page_page').' ON '.$this->addDatabasePrefix($this->_db_table).'.item_id='.$this->addDatabasePrefix('homepage_link_page_page').'.to_item_id';
      }
      $query .= ' WHERE 1';

      if ( isset($this->_index_limit) and $this->_index_limit ) {
         $query .= ' AND '.$this->addDatabasePrefix('room').'.extras LIKE "%HOMEPAGELINK\";s:1%"';
      }

      if (isset($this->_room_limit) and !isset($this->_index_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND ('.$this->addDatabasePrefix($this->_db_table).'.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'" )';
      }
      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }
     if ( !empty($this->_father_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('homepage_link_page_page').'.from_item_id="'.encode(AS_DB,$this->_father_limit).'" AND '.$this->addDatabasePrefix($this->_db_table).'.page_type="CHILD"';
     }
     if ( isset($this->_index_limit) and $this->_index_limit ) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.page_type="ROOT"';
     }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
       if ( isset($this->_index_limit) and $this->_index_limit ) {
          $field_array = array('room.title');
       } else {
          $field_array = array(''.$this->addDatabasePrefix($this->_db_table).'.description',''.$this->addDatabasePrefix($this->_db_table).'.title');
       }
       $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
       $query .= $search_limit_query_code;
         $query .= ')';
      }
      if ( isset($this->_index_limit) and $this->_index_limit ) {
         $order_query = ' '.$this->addDatabasePrefix('room').'.activity DESC, ';
     } else {
       $order_query = '';
     }

     if ( isset($this->_sort_order) and !empty($this->_sort_order) ) {
         if ( $this->_sort_order == 'modified' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
         } elseif ( $this->_sort_order == 'modified_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
       } elseif ( $this->_sort_order == 'activity' and isset($this->_index_limit) and $this->_index_limit ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('room').'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
       } elseif ( $this->_sort_order == 'activity_rev' and isset($this->_index_limit) and $this->_index_limit ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('room').'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
       } elseif ( $this->_sort_order == 'room_title' and isset($this->_index_limit) and $this->_index_limit ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('room').'.title DESC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
       } elseif ( $this->_sort_order == 'room_title_rev' and isset($this->_index_limit) and $this->_index_limit ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('room').'.title ASC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
       }
      } elseif ( !isset($this->_index_limit) or !$this->_index_limit ) {
        $query .= ' ORDER BY '.$this->addDatabasePrefix('homepage_link_page_page').'.sorting_place ASC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
     }

      if ( $mode == 'select' ) {
         if ( isset($this->_interval_limit) and isset($this->_from_limit) ) {
            $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
         }
      }

      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting homepage from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

  /** get a homepage
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a homepage
    */
  public function getItem ($item_id) {
     $homepage = NULL;
     if ( !empty($item_id) ) {
        $query  = "SELECT *,".$this->addDatabasePrefix("homepage_link_page_page").".sorting_place,".$this->addDatabasePrefix("homepage_link_page_page").".from_item_id FROM ".$this->addDatabasePrefix($this->_db_table);
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('homepage_link_page_page').' ON '.$this->addDatabasePrefix($this->_db_table).'.item_id='.$this->addDatabasePrefix('homepage_link_page_page').'.to_item_id';
        $query .= " WHERE ".$this->addDatabasePrefix($this->_db_table).".item_id = '".encode(AS_DB,$item_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting one homepage item: "'.$item_id.'" from query: "'.$query.'"',E_USER_WARNING);
        } else {
           $homepage = $this->_buildItem($result[0]);
        }
     }
     return $homepage;
  }

  public function getImprintPageItem ($cid) {
     return $this->_getSpecialPageItem('IMPRINT',$cid);
  }

  public function getRootPageItem ($cid) {
     return $this->_getSpecialPageItem('ROOT',$cid);
  }

  /** get root homepage
    *
    * @param integer item_id id of the context
    *
    * @return object cs_item a homepage
    */
  private function _getSpecialPageItem ($type,$cid) {
     $homepage = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".context_id='".encode(AS_DB,$cid)."' AND ".$this->addDatabasePrefix($this->_db_table).".page_type='".encode(AS_DB,$type)."' AND deleter_id IS NULL AND deletion_date IS NULL;";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting one homepage item from query: "'.$query.'"',E_USER_WARNING);
     } elseif ( !empty($result[0]) ) {
        $homepage = $this->_buildItem($result[0]);
     }
     return $homepage;
  }

   public function getItemList($id_array) {
      return $this->_getItemList(CS_HOMEPAGE_TYPE, $id_array);
   }

   /** build a new homepage item
    * this method returns a new EMTPY homepage item
    *
    * @return object cs_item a new EMPTY homepage item
    */
   public function getNewItem () {
      include_once('classes/cs_homepage_item.php');
      return new cs_homepage_item($this->_environment);
   }


  /** update an homepage - internal, do not use -> use method save
    * this method updates an homepage
    *
    * @param object cs_item homepage_item the homepage
    */
  function _update ($homepage_item) {
     parent::_update($homepage_item);

     $modificator = $homepage_item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($homepage_item->isPublic()) {
        $public = '1';
     } else {
        $public = '0';
     }

     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.encode(AS_DB,$current_datetime).'",'.
              'title="'.encode(AS_DB,$homepage_item->getTitle()).'",'.
              'description="'.encode(AS_DB,$homepage_item->getDescription()).'",'.
              'public="'.encode(AS_DB,$public).'",'.
              'page_type="'.encode(AS_DB,$homepage_item->getPageType()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$homepage_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating homepage from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($homepage_item);
     unset($modificator);
  }

  /** create a homepage - internal, do not use -> use method save
    * this method creates a homepage
    *
    * @param object cs_item homepage_item the homepage
    */
  function _create ($homepage_item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$homepage_item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="'.$this->_db_table.'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating homepage from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $homepage_item->setItemID($this->getCreateID());
        $this->_newHomepage($homepage_item);
     }
     unset($homepage_item);
  }

  /** creates a new homepage - internal, do not use -> use method save
    * this method creates a new homepage
    *
    * @param object cs_item homepage_item the homepage
    */
  private function _newHomepage ($homepage_item) {
     $user = $homepage_item->getCreatorItem();
     $modificator = $homepage_item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($homepage_item->isPublic()) {
        $public = '1';
     } else {
        $public = '0';
     }

     $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'item_id="'.encode(AS_DB,$homepage_item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$homepage_item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$homepage_item->getTitle()).'",'.
              'public="'.encode(AS_DB,$public).'",'.
              'page_type="'.encode(AS_DB,$homepage_item->getPageType()).'",'.
              'description="'.encode(AS_DB,$homepage_item->getDescription()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating homepage from query: "'.$query.'"',E_USER_WARNING);
     } else {
       // make link to father page
       $father_id = $homepage_item->getFatherID();
       if ( !empty($father_id) ) {
          $homepage_link_manager = $this->_environment->getHomepageLinkManager();
          $new_link = $homepage_link_manager->getNewItem();
          $new_link->setContextItemID($this->_environment->getCurrentContextID());
          $new_link->setFatherItemID($father_id);
          $new_link->setChildItemID($homepage_item->getItemID());
          $new_link->setCreatorItemID($user->getItemID());
          $new_link->setModifierItemID($user->getItemID());
          $sorting_place = $this->countChildren($father_id);
          $sorting_place++;
          $new_link->setSortingPlace($sorting_place);
          $new_link->save();
          unset($homepage_link_manager);
          unset($new_link);
       }
    }
    unset($homepage_item);
    unset($modificator);
  }

  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting homepage from query: "'.$query.'"',E_USER_WARNING);
     } else {
        parent::delete($item_id);
        $this->_deleteHomepageLinks($item_id);
     }
  }

  private function _deleteHomepageLinks ($item_id) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->deleteHomepageLinks($item_id);
     unset($homepagelink_manager);
  }

  public function getGrandFatherItem ($item_id) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $grand_father_item_id = $homepagelink_manager->getGrandFatherItemID($item_id);
     unset($homepagelink_manager);
     return $this->getItem($grand_father_item_id);
  }

  public function getFatherItemList ($item_id) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $father_item_id_array = $homepagelink_manager->getFatherItemIDArray($item_id);
     unset($homepagelink_manager);
     $father_list = new cs_list();
     foreach ($father_item_id_array as $father_item_id) {
        $father_item = $this->getItem($father_item_id);
        $father_list->add($father_item);
        unset($father_item);
     }
     return $father_list;
  }

  public function moveUp ($item) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->moveUp($item->getItemID(),$item->getFatherID());
     unset($homepagelink_manager);
  }

  public function moveDown ($item) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->moveDown($item->getItemID(),$item->getFatherID());
     unset($homepagelink_manager);
  }

  public function moveLeft ($item) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->moveLeft($item->getItemID(),$item->getFatherID());
     unset($homepagelink_manager);
  }

  public function moveRight ($item) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->moveRight($item->getItemID(),$item->getFatherID());
     unset($homepagelink_manager);
  }

  private function countChildren ($item_id) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $retour = $homepagelink_manager->countChildren($item_id);
     unset($homepagelink_manager);
     return $retour;
  }

   function _buildItem ($value) {
      $retour = parent::_buildItem($value);
      if ( isset($value['from_item_id']) and !empty($value['from_item_id']) ) {
         $retour->setFatherID($value['from_item_id']);
      }
      if ( isset($value['room_title']) and !empty($value['room_title']) ) {
         $retour->setRoomTitle($value['room_title']);
      }
      if ( isset($value['room_activity']) and !empty($value['room_activity']) ) {
         $retour->setRoomActivity($value['room_activity']);
      }
      return $retour;
   }

  public function initHomepage ($context_id) {

     $root_page_item = $this->getRootPageItem($context_id);
     $do = false;
     if ( !isset($root_page_item) ) {
        $do = true;
     } else {
        $item_id = $root_page_item->getItemID();
        if ( empty($item_id) ) {
           $do = true;
        }
     }
     if ( $do ) {

        $current_user = $this->_environment->getCurrentUserItem();
        $translator = $this->_environment->getTranslationObject();

        $root_item = $this->getNewItem();
        $root_item->setPageTypeToRoot();
        $root_item->setContextID($context_id);
        $root_item->setTitle($translator->getMessage('HOMEPAGE_PAGE_ROOT_TITLE'));
        $root_item->setDescription($translator->getMessage('HOMEPAGE_PAGE_ROOT_DESCRIPTION'));
        $root_item->setCreatorID($current_user->getItemID());
        $root_item->setModifierID($current_user->getItemID());
        $root_item->save();

        $imprint_item = $this->getNewItem();
        $imprint_item->setPageTypeToImPrint();
        $imprint_item->setContextID($context_id);
        $imprint_item->setTitle($translator->getMessage('HOMEPAGE_PAGE_IMPRINT_TITLE'));
        $imprint_item->setDescription($translator->getMessage('HOMEPAGE_PAGE_IMPRINT_DESCRIPTION'));
        $imprint_item->setCreatorID($current_user->getItemID());
        $imprint_item->setModifierID($current_user->getItemID());
        $imprint_item->save();

        $homepagelink_manager = $this->_environment->getHomepageLinkManager();
        $link = $homepagelink_manager->getNewItem();
        $link->setContextItemID($context_id);
        $link->setFatherItemID($root_item->getItemID());
        $link->setChildItemID($imprint_item->getItemID());
        $link->setCreatorItemID($current_user->getItemID());
        $link->setModifierItemID($current_user->getItemID());
        $link->save();

        unset($current_user);
        unset($link);
        unset($imprint_item);
        unset($root_item);
        unset($homepagelink_manager);
     }
     unset($root_page_item);
  }

  public function deleteHomepage ($context_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE context_id="'.encode(AS_DB,$context_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting homepage from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $item_manager = $this->_environment->getItemManager();
        $item_manager->deleteSpecialItems($context_id,CS_HOMEPAGE_TYPE);
        $this->_deleteAllHomepageLinks($context_id);
        unset($item_manager);
     }
  }

  private function _deleteAllHomepageLinks ($item_id) {
     $homepagelink_manager = $this->_environment->getHomepageLinkManager();
     $homepagelink_manager->deleteAllHomepageLinks($item_id);
     unset($homepagelink_manager);
  }
}
?>