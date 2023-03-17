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

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community".
 */
class cs_room2_manager extends cs_context_manager
{
    /**
     * string - containing the last login limit (a datetimeor "NULL") of rooms.
     */
    protected $_lastlogin_limit = null;

    /**
     * string - containing the last login limit (a datetime) of rooms.
     */
    protected $_lastlogin_older_limit = null;

    /**
     * string - containing the last login limit (a datetime) of rooms.
     */
    protected $_lastlogin_newer_limit = null;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

   /** reset limits
    * reset limits of this class: lastlogin and all limits from upper class.
    */
   public function resetLimits()
   {
       parent::resetLimits();
       $this->_lastlogin_limit = null;
       $this->_lastlogin_older_limit = null;
       $this->_lastlogin_newer_limit = null;
   }

   public function setLastLoginLimit($limit)
   {
       $this->_lastlogin_limit = (string) $limit;
   }

   public function setLastLoginOlderLimit($limit)
   {
       $this->_lastlogin_older_limit = (string) $limit;
   }

   public function setLastLoginNewerLimit($limit)
   {
       $this->_lastlogin_newer_limit = (string) $limit;
   }

   public function setActiveLimit()
   {
       $this->setLastLoginNewerLimit(getCurrentDateTimeMinusDaysInMySQL(100));
   }

    public function saveLastLogin($item)
    {
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
            ' lastlogin=NOW()'.
            ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems saving lastlogin to room ('.$item->getItemID().') - '.$this->_db_table.'.',
                E_USER_WARNING);
        } else {
            return true;
        }

        return false;
    }

   /** update a room - internal, do not use -> use method save
    * this method updates a room.
    *
    * @param object cs_context_item a commsy room
    */
   public function _update($item)
   {
       if ($this->_update_with_changing_modification_information) {
           parent::_update($item);
       }
       $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET ';
       if ($this->_update_with_changing_modification_information) {
           $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",';
           $modifier_id = $this->_current_user->getItemID();
           if (!empty($modifier_id)) {
               $query .= 'modifier_id="'.encode(AS_DB, $modifier_id).'",';
           }
       }

       if ($item->isOpenForGuests()) {
           $open_for_guests = 1;
       } else {
           $open_for_guests = 0;
       }
       if ($item->isContinuous()) {
           $continuous = 1;
       } else {
           $continuous = -1;
       }
       if ($item->isTemplate()) {
           $template = 1;
       } else {
           $template = -1;
       }

       if ($item->getActivityPoints()) {
           $activity = $item->getActivityPoints();
       } else {
           $activity = '0';
       }

       if ($item->getPublic()) {
           $public = '1';
       } else {
           $public = '0';
       }

       $title = str_ireplace("'", '"', $item->getTitle());

       $query .= 'title="'.encode(AS_DB, $title).'",'.
                 "extras='".encode(AS_DB, serialize($item->getExtraInformation()))."',".
                 "status='".encode(AS_DB, $item->getStatus())."',".
                 "activity='".encode(AS_DB, $activity)."',".
                 "public='".encode(AS_DB, $public)."',".
                 "continuous='".$continuous."',".
                 "template='".$template."',".
                 "is_open_for_guests='".$open_for_guests."',".
                 "contact_persons='".encode(AS_DB, $item->getContactPersonString())."',";

       if ($this->_existsField($this->_db_table, 'room_description')) {
           $query .= "room_description='".encode(AS_DB, $item->getDescription())."'";
       } else {
           $query .= "description='".encode(AS_DB, $item->getDescription())."'";
       }

       if ($this->_existsField($this->_db_table, 'archived') && method_exists($item, 'getArchived')) {
           $query .= ', archived='.encode(AS_DB, $item->getArchived() ? 1 : 0);
       }

       $query .= ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problems updating '.$this->_db_table.' item from query: "'.$query.'"', E_USER_WARNING);
       }
   }

   /** creates a new room - internal, do not use -> use method save
    * this method creates a new room.
    *
    * @param object cs_context_item (upper class) a commsy room
    */
   public function _new($item)
   {
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

       $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
                'item_id="'.encode(AS_DB, $item->getItemID()).'",'.
                'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                'creator_id="'.encode(AS_DB, $user->getItemID()).'",'.
                'modifier_id="'.encode(AS_DB, $user->getItemID()).'",'.
                'creation_date="'.$current_datetime.'",'.
                'modification_date="'.$current_datetime.'",'.
                'title="'.encode(AS_DB, $title).'",'.
                'extras="'.encode(AS_DB, serialize($item->getExtraInformation())).'",'.
                'public="'.encode(AS_DB, $public).'",'.
                'type="'.encode(AS_DB, $item->getRoomType()).'",'.
                'continuous="'.$continuous.'",'.
                'status="'.encode(AS_DB, $item->getStatus()).'",'.
                'contact_persons="'.encode(AS_DB, $item->getContactPersonString()).'",';
       if ($this->_existsField($this->_db_table, 'room_description')) {
           $query .= 'room_description="'.encode(AS_DB, $item->getDescription()).'"';
       } else {
           $query .= 'description="'.encode(AS_DB, $item->getDescription()).'"';
       }

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems creating new '.$this->_room_type.' item from query: "'.$query.'"', E_USER_ERROR);
       }
   }
}
