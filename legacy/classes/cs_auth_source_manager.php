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

include_once('classes/cs_auth_source_item.php');
include_once('functions/text_functions.php');
include_once('functions/date_functions.php');

/** class for database connection to the database table "auth_source"
 * this class implements a database manager for the table "auth_source"
 */
class cs_auth_source_manager extends cs_manager {

   private $_cache = array();

  /** constructor: cs_auth_source_manager
    * the only available constructor, initial values for internal variables
    */
  function __construct($environment) {
     cs_manager::__construct($environment);
     $this->_db_table = CS_AUTH_SOURCE_TYPE;
  }

  /** reset limits
    * reset limits of this class
    */
  function resetLimits () {
     parent::resetLimits();
  }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function setOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function _performQuery($mode = 'select') {

      if ($mode == 'count') {
         $query = 'SELECT count(auth_source.item_id) as count';
      } elseif ($mode == 'id_array') {
          $query = 'SELECT auth_source.item_id';
      } else {
         $query = 'SELECT auth_source.*';
      }
      $query .= ' FROM auth_source';

      $query .= ' WHERE 1';
      if (isset($this->_room_limit)) {
         $query .= ' AND auth_source.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
      if ($this->_delete_limit == true) {
         $query .= ' AND auth_source.deleter_id IS NULL';
      }

      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'modified' ) {
            $query .= ' ORDER BY auth_source.modification_date DESC';
         } elseif ( $this->_sort_order == 'modified_rev' ) {
            $query .= ' ORDER BY auth_source.modification_date';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY auth_source.title';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY auth_source.title DESC';
         }
      }

      if ( $mode == 'select' ) {
         if ( isset($this->_interval_limit) and isset($this->_from_limit) ) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting auth_source.',E_USER_WARNING);
      } else {
         return $result;
      }
   }

  /** get an auth_source
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
  function getItem ($item_id) {
     $retour = NULL;
     if ( !empty($item_id) ) {
        if ( !isset($this->_cache[$item_id]) ) {
           $item = NULL;
           $query = "SELECT * FROM auth_source WHERE auth_source.item_id = '".encode(AS_DB,$item_id)."'";
           $result = $this->_db_connector->performQuery($query);
           if ( !isset($result) or empty($result[0]) ) {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting one auth_source item ['.$item_id.'].',E_USER_WARNING);
              $this->_cache[$item_id] = NULL;
           } else {
              $retour = $this->_buildItem($result[0]);
              if ( $this->_cache_on ) {
                 $this->_cache[$item_id] = $retour;
              }
           }
        } else {
           $retour = $this->_cache[$item_id];
        }
     }
     return $retour;
  }

   function getItemList($id_array) {
      return $this->_getItemList(CS_AUTH_SOURCE_TYPE, $id_array);
   }

   /** build a new auth_source item
    * this method returns a new EMTPY auth_source item
    *
    * @return object cs_item a new EMPTY auth_source
    */
   function getNewItem () {
      return new cs_auth_source_item($this->_environment);
   }

  /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
  function _buildItem($db_array) {
      $db_array['extras'] = mb_unserialize($db_array['extras']);
      $item = parent::_buildItem($db_array);
      return $item;
  }

  /** update an auth_source - internal, do not use -> use method save
    * this method updates an auth_source
    *
    * @param object cs_item item the auth_source
    */
  function _update ($item) {
     parent::_update($item);

     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ( $item->isPublic() ) {
        $public = '1';
     } else {
        $public = '0';
     }

     $query = 'UPDATE auth_source SET '.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating auth_source.',E_USER_WARNING);
     }
     unset($item);
  }

  /** create an auth_source - internal, do not use -> use method save
    * this method creates an auth_source
    *
    * @param object cs_item item the auth_source
    */
  function _create ($item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="'.CS_AUTH_SOURCE_TYPE.'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating auth_source.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newAuthSource($item);
     }
     unset($item);
  }

  /** creates an new auth_source - internal, do not use -> use method save
    * this method creates an new auth_source
    *
    * @param object cs_item item the auth_source
    */
  function _newAuthSource ($item) {
     $user = $item->getCreatorItem();
     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ( $item->isPublic() ) {
        $public = '1';
     } else {
        $public = '0';
     }

     $query = 'INSERT INTO auth_source SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating auth_source.',E_USER_WARNING);
     }
     unset($item);
  }

  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE auth_source SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting auth_source.',E_USER_WARNING);
     } else {
        parent::delete($item_id);
     }
  }
}
?>