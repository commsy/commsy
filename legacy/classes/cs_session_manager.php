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
class cs_session_manager
{
    /**
     * The Symfony Session Component
     * @var Symfony\Component\HttpFoundation\Session\Session $symfonySession
     */
    private $symfonySession = null;

    /**
    * class - containing a conntection to the (mysql) database
    */
    //public $_db_connector = null;

    /**
    * array - containing the authentication settings
    */
    private $settings;

    private $lastQuery;

    private $isCacheOn = true;

    /** constructor: cs_session_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object db_connector connection to the database
    * @param string domain domain for cookie management
    */

    public function __construct($dbConnector, $settings)
    {
        global $symfonyContainer;
        $this->symfonySession = $symfonyContainer->get('session');

        $this->reset();
        $this->settings = (array) $settings;

        if (!isset($this->settings['cookiepath'])) {
            $this->settings['cookiepath'] = '';
        }

        if (!isset($this->settings['domain'])) {
            $this->settings['domain'] = '';
        }
    }

    /** reset limits
    * reset limits of this class: room limit, delete limit
    */
    public function resetLimits()
    {
    }

    /** reset this manager
    * this method resets the internal information of the session manager
    */
    public function reset()
    {
        $this->_dberrno      = null;
        $this->_dberror      = null;
    }

    /** get error number
    * this method returns the number of an error, if an error occured
    *
    * @return integer error number
    */
    public function getErrorNumber()
    {
        return $this->_dberrno;
    }

    /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error number
    */
    public function getErrorMessage()
    {
        return $this->_dberror;
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /** get session item
    * this method returns a session item
    *
    * @param string session_id the session id
    *
    * @return object cs_session_item the session item
    */
    public function get($session_id)
    {
        if (!empty($session_id) && !empty($this->_cache_object[$session_id])) {
            // take from cache
            $sessionItem = $this->_cache_object[$session_id];
        } else {
            $sessionItem = new cs_session_item();

            $sessionItem->setSessionID($session_id);
            $sessionItem->_data = $this->symfonySession->all();

            if ($this->isCacheOn) {
                $this->_cache_object[$session_id] = $sessionItem;
            }
        }

        return $sessionItem;

      // // don't delete session on every site request
      // if ( 1 == rand(1,100) ) {
      //    $this->deleteOldSessions();
      // }
      // if ( !empty($session_id)
      //      and !empty($this->_cache_object[$session_id])
      //    ) {
      //    $session_item = $this->_cache_object[$session_id];
      // } else {
      //    $session_arrays = array();
      //    $session_item = NULL;
      //    $query = 'SELECT session_value FROM session WHERE session_id="'.encode(AS_DB,$session_id).'";';
      //    $this->lastQuery = $query;
      //    $result = $this->_db_conntector->performQuery($query);
      //    if ( !isset($result) ) {
      //       include_once('functions/error_functions.php');
      //       trigger_error('Problems selecting session values for: '.$session_id.'.', E_USER_WARNING);
      //    } elseif ( !empty($result[0]) ) {
      //       include_once('classes/cs_session_item.php');
      //       $session_item = new cs_session_item();
      //       $session_item->setSessionID($session_id);
      //       $session = $result[0];
      //       if (!empty($session['session_value'])) {
      //          if ( strstr($session['session_value'],'cs_mail_obj') ) {
      //             // must included here, because this objects could get out of the session
      //             include_once('classes/cs_mail_obj.php');
      //          }
      //          include_once('functions/text_functions.php');
      //          $session_array = mb_unserialize($session['session_value']);
      //       } else {
      //          $session_array = array();
      //       }
      //       $session_item->_data = $session_array;
      //       if ( $this->isCacheOn ) {
      //          $this->_cache_object[$session_id] = $session_item;
      //       }
      //    }
      // }
      // return $session_item;
    }

    /** save session item -- TBD: needed any more?
    * this method saves a session item into the database table "session"
    *
    * @param object cs_session_item the session item
    */
    public function save($item)
    {
        // cokie management
        if (!$item->issetValue("cookie") || $item->getValue("cookie") == 2) {
            $this->saveSessionIDInCookie($item->getSessionID(), $item->getToolName());

            if ($item->getValue("cookie") == 2) {
                $item->setValue("cookie", 1);
            }
        }

        // commsy: portal2portal
        // renew cookie when user comes from a soap session via connection key
        elseif ($item->issetValue("cookie") && $item->getValue("cookie") == 3 && !stristr($_SERVER["HTTP_USER_AGENT"], "PHP-SOAP")) {
            $this->saveSessionIDInCookie($item->getSessionID(), $item->getToolName());
            $item->setValue('cookie', 1);
        }

        $this->symfonySession->save();

        // include_once 'functions/date_functions.php';
        // $current_date_time = getCurrentDateTimeInMySQL();
        // $session_data = serialize($item->_data);

        // $query = 'SELECT session_value FROM session WHERE session_id="'.encode(AS_DB, $item->getSessionID()).'";';
        // $this->lastQuery = $query;
        // $result = $this->_db_conntector->performQuery($query);
        // if (!isset($result)) {
        //     include_once 'functions/error_functions.php';
        //     trigger_error('Problems selecting session values for: '.encode(AS_DB, $item->getSessionID()).'.', E_USER_WARNING);
        // } else {
        //     include_once 'functions/text_functions.php';
        //     if (!empty($result[0])) {
        //         $query = "UPDATE session SET session_value='".encode(AS_DB, $session_data)."',
        //                                  created='".$current_date_time."'
        //                              WHERE session_id='".encode(AS_DB, $item->getSessionID())."';";

        //         $this->lastQuery = $query;
        //         $result = $this->_db_conntector->performQuery($query);
        //         if (!isset($result) or !$result) {
        //             trigger_error('Problems saving session values for: '.$item->getSessionID().'.', E_USER_WARNING);
        //         }
        //     } else {
        //         $query = "INSERT INTO session SET session_id='".encode(AS_DB, $item->getSessionID())."',
        //                                       session_key='new_session_type',
        //                                       session_value='".encode(AS_DB, $session_data)."',
        //                                       created='".$current_date_time."'";
        //         $this->lastQuery = $query;
        //         $result = $this->_db_conntector->performQuery($query);
        //         if (!isset($result) or !$result) {
        //             include_once 'functions/error_functions.php';
        //             trigger_error('Problems saving session values for: '.encode(AS_DB, $item->getSessionID()).'.', E_USER_WARNING);
        //         }
        //     }
        // }
        // unset($item);
    }

    /** save session item -- TBD: needed any more?
    * this method saves a session item into the database table "session"
    *
    * @param object cs_session_item the session item
    */
    public function update($item)
    {
        // cookie management
        if (!$item->issetValue('cookie') or $item->getValue('cookie') == 2) {
            $this->saveSessionIDInCookie($item->getSessionID(), $item->getToolName());
            if ($item->getValue('cookie') == 2) {
                $item->setValue('cookie', 1);
            }
        }

        $this->symfonySession->save();

      // include_once('functions/date_functions.php');
      // $current_date_time = getCurrentDateTimeInMySQL();
      // $session_data = serialize($item->_data);

      // include_once('functions/text_functions.php');
      // $query = "UPDATE session SET session_value='".encode(AS_DB,$session_data)."',
      //          created='".$current_date_time."'
      //          WHERE session_id='".encode(AS_DB,$item->getSessionID())."';";
      // $this->lastQuery = $query;
      // $result = $this->_db_conntector->performQuery($query);
      // if ( !isset($result) or !$result ) {
      //    include_once('functions/error_functions.php');
      //    trigger_error('Problems saving session values for: '.$item->getSessionID().'.', E_USER_WARNING);
      // }
      // unset($item);
    }

    /** delete session item
    * this method deletes a session item in the database table "session"
    *
    * @param string the session id
    * @param boolean delete_cookie true -> cookie will be deleted too
    */
    public function delete($session_id, $delete_cookie = false)
    {
        $this->symfonySession->invalidate();

        if ($delete_cookie) {
            $sessionItem = $this->get($session_id);

            $toolname = 'commsy';
            if (isset($sessionItem)) {
                $toolname = $sessionItem->getToolName();
            }

            $this->deleteSessionIDInCookie($session_id, $toolname);
        }

      // $toolname = 'commsy';
      // if ($delete_cookie) {
      //    $session_item = $this->get($session_id);
      //    if (isset($session_item )){
      //       $toolname = $session_item->getToolName();
      //    }
      // }
      // if (isset($session_id )){
      //    $query = 'DELETE FROM session WHERE session_id="'.encode(AS_DB,$session_id).'";';
      //    $this->lastQuery = $query;
      //    $result = $this->_db_conntector->performQuery($query);
      //    if ( !isset($result) or !$result ) {
      //       include_once('functions/error_functions.php');
      //       trigger_error('Problems deleting session values for: '.$session_id.'.', E_USER_WARNING);
      //    }
      // }
      // if ($delete_cookie) {
      //    $this->deleteSessionIDInCookie($session_id,$toolname);
      // }
    }

    /** delete old sessions
    * this method deletes all sessions in the database table "session" older than 6 hours
    */
    public function deleteOldSessions()
    {
        // include_once('functions/date_functions.php');
      // global $c_session_lifetime;
      // if ( !empty($c_session_lifetime)
      //      and is_int($c_session_lifetime)
      //      and $c_session_lifetime > 0
      //      and $c_session_lifetime < 24
      //    ) {
      //    $session_lifetime = $c_session_lifetime;
      // } else {
      //    $session_lifetime = 6;
      // }
      // $datetime = getCurrentDateTimeMinusHoursInMySQL($session_lifetime);
      // $query = 'DELETE LOW_PRIORITY FROM session WHERE created<"'.$datetime.'";';
      // $this->lastQuery = $query;
      // $result = $this->_db_conntector->performQuery($query);
      // if ( !isset($result) or !$result ) {
      //    include_once('functions/error_functions.php');
      //    trigger_error('Problems deleting old session values.', E_USER_WARNING);
      // }
    }

    /** save session id in a cookie, internal -> do not use
    * this method saves the session id in a cookie, if the user allows cookies
    *
    * @param string the session id
    */
    private function saveSessionIDInCookie($sessionId, $toolname)
    {
        if ($toolname != 'commsy') {
            $cookieName = 'SID_'.$toolname;
        } else {
            $cookieName = 'SID';
        }
        setcookie($cookieName, $sessionId, 0, $this->settings['cookiepath'], $this->settings['domain'], 0);
    }

    /** delete cookie with session id, internal -> do not use
    * this method deletes the session id in a cookie by deleting the cookie
    *
    * @param string the session id
    *
    * @author CommSy Development Group
    */
    private function deleteSessionIDInCookie($sessionId, $toolname)
    {
        if ($toolname != 'commsy') {
            $cookieName = 'SID_' . $toolname;
        } else {
            $cookieName = 'SID';
        }
        $time = time() - 3600; // time in the past
        setcookie($cookieName, $sessionId, $time, $this->settings['cookiepath'], $this->settings['domain'], 0);
    }

    public function getActiveSOAPSessionID($userId, $portalId)
    {
        // $retour = '';
        // $query = 'SELECT session_id FROM session WHERE (session_value LIKE "%'.encode(AS_DB, 's:12:"SOAP_SESSION";i:1').'%" or session_value LIKE "%'.encode(AS_DB, 's:10:"SOAP_LOGIN";i:1').'%") and (session_value LIKE "%i:'.encode(AS_DB, $portalId).'%" or session_value LIKE "%'.'\"'.encode(AS_DB, $portalId).'\"'.'%") and (session_value LIKE "%i:'.encode(AS_DB, $userId).'%" or session_value LIKE "%'.'\"'.encode(AS_DB, $userId).'\"'.'%") ORDER BY created DESC;';
        // $this->lastQuery = $query;

        // $result = $this->_db_conntector->performQuery($query);
        // if (!isset($result)) {
        //     include_once 'functions/error_functions.php';
        //     trigger_error('Problems selecting session_id values for: '.$userId.' - '.$portalId.' - SQL-Query:'.$query.'.', E_USER_WARNING);
        // } elseif (!empty($result[0])) {
        //     $session_row = $result[0];
        //     $session_id = $session_row['session_id'];
        //     if (!empty($session_id)) {
        //         $retour = $session_id;
        //     }
        // }

        // return $retour;
    }

    public function getActiveSOAPSessionIDForApp($user_id, $portalId)
    {
        // $retour = '';
        // $query = 'SELECT session_id FROM session WHERE (session_value LIKE "%'.encode(AS_DB, 's:12:"SOAP_SESSION";i:1').'%" or session_value LIKE "%'.encode(AS_DB, 's:10:"SOAP_LOGIN";i:1').'%") and (session_value LIKE "%i:'.encode(AS_DB, $portalId).'%" or session_value LIKE "%'.'\"'.encode(AS_DB, $portalId).'\"'.'%") and (session_value LIKE "%i:'.encode(AS_DB, $user_id).'%" or session_value LIKE "%'.'\"'.encode(AS_DB, $user_id).'\"'.'%") ORDER BY created DESC;';
        // $this->lastQuery = $query;

        // $result = $this->_db_conntector->performQuery($query);
        // if (!isset($result)) {
        //     include_once 'functions/error_functions.php';
        //     trigger_error('Problems selecting session_id values for: '.$user_id.' - '.$portalId.' - SQL-Query:'.$query.'.', E_USER_WARNING);
        // } elseif (!empty($result[0])) {
        //     $session_row = $result[0];
        //     $session_id = $session_row['session_id'];
        //     if (!empty($session_id)) {
        //         $retour = $session_id;
        //     }
        // }

        // return $retour;
    }

    public function getActiveSOAPSessionIDFromConnectionKey($user_key, $portal_id)
    {
        // $retour = '';
        // $query = 'SELECT session_id FROM session WHERE (session_value LIKE "%i:'.encode(AS_DB, $portal_id).'%" or session_value LIKE "%'.'\"'.encode(AS_DB, $portal_id).'\"'.'%") and (session_value LIKE "%s:14:\"CONNECTION_KEY\";s:'.strlen($user_key).':\"'.$user_key.'\"%") ORDER BY created DESC;';
        // $this->lastQuery = $query;

        // $result = $this->_db_conntector->performQuery($query);
        // if (!isset($result)) {
        //     include_once 'functions/error_functions.php';
        //     trigger_error('Problems selecting session_id values for: '.$user_key.' - '.$portal_id.' - SQL-Query:'.$query.'.', E_USER_WARNING);
        // } elseif (!empty($result[0])) {
        //     $session_row = $result[0];
        //     $session_id = $session_row['session_id'];
        //     if (!empty($session_id)) {
        //         $retour = $session_id;
        //     }
        // }

        // return $retour;
    }

    public function updateSessionCreationDate($session_id)
    {
        $this->symfonySession->getMetadataBag()->stampNew();
        $this->symfonySession->save();

        return true;

      // $retour = false;
      // $query = 'UPDATE session SET created = NOW() WHERE session_id="'.encode(AS_DB,$session_id).'";';
      // $this->lastQuery = $query;
      // $result = $this->_db_conntector->performQuery($query);
      // if ( !isset($result) or !$result ) {
      //    include_once('functions/error_functions.php');
      //    trigger_error('Problems updateing creation_date of a session ('.$session_id.').', E_USER_WARNING);
      // } else {
      //    $retour = true;
      // }
      // return $retour;
    }
}
