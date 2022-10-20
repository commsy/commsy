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

/** upper class of the room manager
 */
include_once('classes/cs_context_manager.php');

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community"
 */
class cs_room2_manager extends cs_context_manager {

  /**
   * string - containing the last login limit (a datetimeor "NULL") of rooms
   */
  protected $_lastlogin_limit = NULL;

  /**
   * string - containing the last login limit (a datetime) of rooms
   */
  protected $_lastlogin_older_limit = NULL;
	
  /**
   * string - containing the last login limit (a datetime) of rooms
   */
  protected $_lastlogin_newer_limit = NULL;

    /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

    /** reset limits
    * reset limits of this class: lastlogin and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_lastlogin_limit = NULL;
      $this->_lastlogin_older_limit = NULL;
      $this->_lastlogin_newer_limit = NULL;
   }
  
   public function setLastLoginLimit ($limit) {
      $this->_lastlogin_limit = (string)$limit;
   }
  
   public function setLastLoginOlderLimit ($limit) {
      $this->_lastlogin_older_limit = (string)$limit;
   }
  
   public function setLastLoginNewerLimit ($limit) {
      $this->_lastlogin_newer_limit = (string)$limit;
   }
  
   public function setActiveLimit () {
      include_once('functions/date_functions.php');
      $this->setLastLoginNewerLimit(getCurrentDateTimeMinusDaysInMySQL(100));
   }
   
   // archiving
   public function saveLastLogin ($item, $datetime = '') {
   	$retour = false;
   	if ( !empty($datetime) ) {
   		$query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
   		                  ' lastlogin="'.$datetime.'"'.
   		                  ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
   		 
   	} else {
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
                  ' lastlogin=NOW()'.
                  ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
   	}
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems saving lastlogin to room ('.$item->getItemID().') - '.$this->_db_table.'.',E_USER_WARNING);
      } else {
      	$retour = true;
      }
      return $retour;
   }
   
  /** update a room - internal, do not use -> use method save
    * this method updates a room
    *
    * @param object cs_context_item a commsy room
    */
   function _update ($item) {
      if ( $this->_update_with_changing_modification_information ) {
         parent::_update($item);
      }
      $query  = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET ';
      if ( $this->_update_with_changing_modification_information ) {
         $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",';
         $modifier_id = $this->_current_user->getItemID();
         if ( !empty($modifier_id) ) {
            $query .= 'modifier_id="'.encode(AS_DB,$modifier_id).'",';
         }
      }

      if ($item->isOpenForGuests()) {
         $open_for_guests = 1;
      } else {
         $open_for_guests = 0;
      }
      if ( $item->isContinuous() ) {
         $continuous = 1;
      } else {
         $continuous = -1;
      }
      if ( $item->isTemplate() ) {
         $template = 1;
      } else {
         $template = -1;
      }

      if ( $item->getActivityPoints() ) {
         $activity = $item->getActivityPoints();
      } else {
         $activity = '0';
      }

      if ( $item->getPublic() ) {
         $public = '1';
      } else {
         $public = '0';
      }

      $title = str_ireplace("'", '"', $item->getTitle());
      $slug = $item->getSlug();

      $query .= 'title="'.encode(AS_DB,$title).'",'.
                "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."',".
                "status='".encode(AS_DB,$item->getStatus())."',".
                "activity='".encode(AS_DB,$activity)."',".
                "public='".encode(AS_DB,$public)."',".
                "continuous='".$continuous."',".
                "template='".$template."',".
                "is_open_for_guests='".$open_for_guests."',".
                "contact_persons='".encode(AS_DB,$item->getContactPersonString())."',".
                "slug=" . (!empty($slug) ? "'" . encode(AS_DB, $slug) . "'" : "NULL") . ",";
                if ($this->_existsField($this->_db_table, 'room_description')){
                   $query .= "room_description='".encode(AS_DB,$item->getDescription())."'";
                }else{
                    $query .= "description='".encode(AS_DB,$item->getDescription())."'";
                }

      $query .= ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating '.$this->_db_table.' item from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   /** creates a new room - internal, do not use -> use method save
    * this method creates a new room
    *
    * @param object cs_context_item (upper class) a commsy room
    */
   function _new ($item) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $user = $item->getCreatorItem();
      if (empty($user)) {
         $user = $this->_environment->getCurrentUserItem();
      }

      if ($item->isContinuous()) {
         $continuous = 1;
      } else {
         $continuous = -1;
      }

      if ($item->getPublic()) {
         $public = $item->getPublic();
      } else {
         $public = 0;
      }

      $title = str_ireplace("'", '"', $item->getTitle());
      $slug = $item->getSlug();

      $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'modifier_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$title).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'type="'.encode(AS_DB,$item->getRoomType()).'",'.
               'continuous="'.$continuous.'",'.
               'status="'.encode(AS_DB,$item->getStatus()).'",'.
               'contact_persons="'.encode(AS_DB,$item->getContactPersonString()).'",'.
               'slug=' . (!empty($slug) ? '"' . encode(AS_DB, $slug) . '"' : "NULL") . ',';
                if ($this->_existsField($this->_db_table, 'room_description')){
                   $query .= 'room_description="'.encode(AS_DB,$item->getDescription()).'"';
                }else{
                   $query .= 'description="'.encode(AS_DB,$item->getDescription()).'"';
                }

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating new '.$this->_room_type.' item from query: "'.$query.'"', E_USER_ERROR);
      }
   }
}
?>