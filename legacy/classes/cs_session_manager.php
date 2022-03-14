<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** class for database connection to the database table "session"
 * this class implements a database manager for the table "session"
 */
class cs_session_manager {

   /**
    * class - containing a conntection to the (mysql) database
    */
   var $_db_connector = NULL;

   /**
    * array - containing the authentication settings
    */
   var $_settings;

   var $_last_query;

   var $_cache_on = true;

   /** constructor: cs_session_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object db_connector connection to the database
    * @param string domain domain for cookie management
    */
   function __construct($db_conntector, $settings)
   {
        $this->reset();
        $this->_db_conntector = $db_conntector;
        $this->_settings = (array)$settings;
        if (!isset($this->_settings['cookiepath'])) {
            $this->_settings['cookiepath'] = '';
        }
        if (!isset($this->_settings['domain'])) {
            $this->_settings['domain'] = '';
        }
   }

   /** reset limits
    * reset limits of this class: room limit, delete limit
    */
   function resetLimits () {  }

   /** reset this manager
    * this method resets the internal information of the session manager
    */
   function reset () {
      $this->_dberrno      = NULL;
      $this->_dberror      = NULL;
   }

   /** get error number
    * this method returns the number of an error, if an error occured
    *
    * @return integer error number
    */
   function getErrorNumber () {
      return $this->_dberrno;
   }

   /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error number
    */
   function getErrorMessage () {
      return $this->_dberror;
   }

   function getLastQuery () {
      return $this->_last_query;
   }

   /** get session item
    * this method returns a session item
    *
    * @param string session_id the session id
    *
    * @return object cs_session_item the session item
    */
   function get ($session_id) {
      // don't delete session on every site request
      if ( 1 == rand(1,100) ) {
         $this->deleteOldSessions();
      }
      if ( !empty($session_id)
           and !empty($this->_cache_object[$session_id])
         ) {
         $session_item = $this->_cache_object[$session_id];
      } else {
         $session_arrays = array();
         $session_item = NULL;
         $query = 'SELECT session_value FROM session WHERE session_id="'.encode(AS_DB,$session_id).'";';
         $this->_last_query = $query;
         $result = $this->_db_conntector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting session values for: '.$session_id.'.', E_USER_WARNING);
         } elseif ( !empty($result[0]) ) {
            include_once('classes/cs_session_item.php');
            $session_item = new cs_session_item();
            $session_item->setSessionID($session_id);
            $session = $result[0];
            if (!empty($session['session_value'])) {
               if ( strstr($session['session_value'],'cs_mail_obj') ) {
                  // must included here, because this objects could get out of the session
                  include_once('classes/cs_mail_obj.php');
               }
               include_once('functions/text_functions.php');
               $session_array = mb_unserialize($session['session_value']);
            } else {
               $session_array = array();
            }
            $session_item->_data = $session_array;
            if ( $this->_cache_on ) {
               $this->_cache_object[$session_id] = $session_item;
            }
         }
      }
      return $session_item;
   }

   /** save session item -- TBD: needed any more?
    * this method saves a session item into the database table "session"
    *
    * @param object cs_session_item the session item
    */
   function save ($item) {
   	
   	// cookie management
      if (!$item->issetValue('cookie') or $item->getValue('cookie') == 2) {
         $this->_saveSessionIDInCookie($item->getSessionID(),$item->getToolName());
         if ($item->getValue('cookie') == 2) {
            $item->setValue('cookie',1);
         }
      }
      
      // commsy: portal2portal
      // set cookie new when user comes from a soap session via connection key
      elseif ( $item->issetValue('cookie')
      		   and $item->getValue('cookie') == 3
      		   and !stristr($_SERVER['HTTP_USER_AGENT'],'PHP-SOAP')
      		 ) {
      	$this->_saveSessionIDInCookie($item->getSessionID(),$item->getToolName());
     		$item->setValue('cookie',1);
      }
      
      include_once('functions/date_functions.php');
      $current_date_time = getCurrentDateTimeInMySQL();
      $session_data = serialize($item->_data);

      $query = 'SELECT session_value FROM session WHERE session_id="'.encode(AS_DB,$item->getSessionID()).'";';
      $this->_last_query = $query;
      $result = $this->_db_conntector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting session values for: '.encode(AS_DB,$item->getSessionID()).'.', E_USER_WARNING);
      } else {
         include_once('functions/text_functions.php');
         if (!empty($result[0])) {
            $query = "UPDATE session SET session_value='".encode(AS_DB,$session_data)."',
                                         created='".$current_date_time."'
                                     WHERE session_id='".encode(AS_DB,$item->getSessionID())."';";

            $this->_last_query = $query;
            $result = $this->_db_conntector->performQuery($query);
            if ( !isset($result) or !$result ) {
               trigger_error('Problems saving session values for: '.$item->getSessionID().'.', E_USER_WARNING);
            }
         } else {
            $query = "INSERT INTO session SET session_id='".encode(AS_DB,$item->getSessionID())."',
                                              session_key='new_session_type',
                                              session_value='".encode(AS_DB,$session_data)."',
                                              created='".$current_date_time."'";
            $this->_last_query = $query;
            $result = $this->_db_conntector->performQuery($query);
            if ( !isset($result) or !$result ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems saving session values for: '.encode(AS_DB,$item->getSessionID()).'.', E_USER_WARNING);
            }
         }
      }
      unset($item);
   }

   /** save session item -- TBD: needed any more?
    * this method saves a session item into the database table "session"
    *
    * @param object cs_session_item the session item
    */
   function update ($item) {
      // cookie management
      if (!$item->issetValue('cookie') or $item->getValue('cookie') == 2) {
         $this->_saveSessionIDInCookie($item->getSessionID(),$item->getToolName());
         if ($item->getValue('cookie') == 2) {
            $item->setValue('cookie',1);
         }
      }

      include_once('functions/date_functions.php');
      $current_date_time = getCurrentDateTimeInMySQL();
      $session_data = serialize($item->_data);

      include_once('functions/text_functions.php');
      $query = "UPDATE session SET session_value='".encode(AS_DB,$session_data)."',
               created='".$current_date_time."'
               WHERE session_id='".encode(AS_DB,$item->getSessionID())."';";
      $this->_last_query = $query;
      $result = $this->_db_conntector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems saving session values for: '.$item->getSessionID().'.', E_USER_WARNING);
      }
      unset($item);
   }


   /** delete session item
    * this method deletes a session item in the database table "session"
    *
    * @param string the session id
    * @param boolean delete_cookie true -> cookie will be deleted too
    */
   function delete ($session_id, $delete_cookie = false) {
      $toolname = 'commsy';
      if ($delete_cookie) {
         $session_item = $this->get($session_id);
         if (isset($session_item )){
            $toolname = $session_item->getToolname();
         }
      }
      if (isset($session_id )){
         $query = 'DELETE FROM session WHERE session_id="'.encode(AS_DB,$session_id).'";';
         $this->_last_query = $query;
         $result = $this->_db_conntector->performQuery($query);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems deleting session values for: '.$session_id.'.', E_USER_WARNING);
         }
      }
      if ($delete_cookie) {
         $this->_deleteSessionIDInCookie($session_id,$toolname);
      }
   }

   /** delete old sessions
    * this method deletes all sessions in the database table "session" older than 6 hours
    */
   function deleteOldSessions() {
      include_once('functions/date_functions.php');

      global $symfonyContainer;
      $c_session_lifetime = $symfonyContainer->getParameter('commsy.settings.session_lifetime');
      
      if ( !empty($c_session_lifetime)
           and is_int($c_session_lifetime)
           and $c_session_lifetime > 0
           and $c_session_lifetime < 24
         ) {
         $session_lifetime = $c_session_lifetime;
      } else {
         $session_lifetime = 6;
      }
      $datetime = getCurrentDateTimeMinusHoursInMySQL($session_lifetime);
      $query = 'DELETE LOW_PRIORITY FROM session WHERE created<"'.$datetime.'";';
      $this->_last_query = $query;
      $result = $this->_db_conntector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting old session values.', E_USER_WARNING);
      }
   }

   /** save session id in a cookie, internal -> do not use
    * this method saves the session id in a cookie, if the user allows cookies
    *
    * @param string the session id
    */
   function _saveSessionIDInCookie ($session_id, $toolname)
    {
        if ($toolname != 'commsy') {
            $cookie_name = 'SID_'.$toolname;
        } else {
            $cookie_name = 'SID';
        }

        if (!isset($this->_settings['cookiepath']) || empty($this->_settings['cookiepath'])) {
            $this->_settings['cookiepath'] = '/';
        }

        setcookie($cookie_name, $session_id, 0, $this->_settings['cookiepath'], $this->_settings['domain'], 0);
   }

   /** delete cookie with session id, internal -> do not use
    * this method deletes the session id in a cookie by deleting the cookie
    *
    * @param string the session id
    *
    * @author CommSy Development Group
    */
   function _deleteSessionIDInCookie ($session_id, $toolname) {
      if ( $toolname != 'commsy' ) {
         $cookie_name = 'SID_'.$toolname;
      } else {
         $cookie_name = 'SID';
      }
      $time = time() - 3600; // time in the past
      setcookie($cookie_name, $session_id, $time, $this->_settings['cookiepath'], $this->_settings['domain'], 0);
   }
}
?>