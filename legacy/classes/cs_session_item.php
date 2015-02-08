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

/** class for session items
 * this class implements a session item
 */
class cs_session_item
{
    /**
    * string - containing the type of the item
    */
    private $type = 'auth';

    /**
     * The Symfony Session Component
     * @var Symfony\Component\HttpFoundation\Session\Session $symfonySession
     */
    private $symfonySession = null;

    /** constructor: cs_session_item
    * the only available constructor, initial values for internal variables
    */
    public function __construct()
    {
        global $symfonyContainer;
        $this->symfonySession = $symfonyContainer->get('session');

        $this->reset();
    }

    /** is the type of the item = $type ?
    * this method returns a boolean expressing if type of the item is $type or not
    *
    * @param string type string to compare with type of the item (type)
    *
    * @return boolean   true - type of this item is $type
    *                   false - type of this item is not $type
    *
    * @author CommSy Development Group
    */
    public function isA($type)
    {
        return $this->type == $type;
    }

    /** reset session item
    * this method resets the internal information of the session item
    *
    * @author CommSy Development Group
    */
    public function reset()
    {
        //$this->symfonySession->clear();
        //$this->sessionId = null;
        //$this->data = array();
    }

    /** get session id
    * this method returns the session id
    *
    * @return string user id
    *
    * @author CommSy Development Group
    */
    public function getSessionID()
    {
        return $this->symfonySession->getId();
    }

    /** set session id
    * this method sets the session id
    *
    * @param string value session id
    *
    * @author CommSy Development Group
    */
    public function setSessionID($value)
    {
        $this->reset();

        //$this->symfonySession->setId((string) $value);
    }

    /** add a value to the session
    * this method adds a value (string, integer or array) to the session
    *
    * @param string key   the key (name) of the value
    * @param *      value the value: string, integer, array
    *
    * @author CommSy Development Group
    */
    public function setValue($key, $value)
    {
        $this->symfonySession->set($key, $value);
    }

    /** unset a value
    * this method unsets a value of the session
    *
    * @param string key   the key (name) of the value
    *
    * @author CommSy Development Group
    */
    public function unsetValue($key)
    {
        if ($this->symfonySession->has($key)) {
            $this->symfonySession->remove($key);
        }
    }

    /** exists the value with the name $key ?
    * this method returns a boolean, if the value exists or not
    *
    * @param string key   the key (name) of the value
    *
    * @return boolean true, if value exists
    *                 false, if not
    *
    * @author CommSy Development Group
    */
    public function issetValue($key)
    {
        return $this->symfonySession->has($key);
    }

    /** get a session value
    * this method returns a value of the session
    *
    * @param string key the key (name) of the value
    *
    * @return * value of the session
    *
    * @author CommSy Development Group
    */
    public function getValue($key)
    {
        if ($this->symfonySession->has($key)) {
            return $this->symfonySession->get($key);
        }
    }

    public function setToolName($value)
    {
        $this->setValue('cs_external_tool', $value);
    }

    public function getToolName()
    {
        $retour = 'commsy';
        if ($this->issetValue('cs_external_tool')) {
            $value = $this->getValue('cs_external_tool');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    /** get all keys
    * this method returns an array with all keys in
    *
    * @return array returns an array with all keys in
    *
    * @author CommSy Development Group
    */
    public function getKeys()
    {
        return array_keys($this->symfonySession->all());
    }

    /** create a session id
    * this method creates a session id out of the user id, the current time and a random number
    *
    * @param string user id
    *
    * @author CommSy Development Group
    */
    public function createSessionID($uid)
    {
        $this->setValue("user_id", $uid);

        // include_once 'functions/date_functions.php';
        // $current_time = getCurrentDateTimeInMySQL();
        // $session_id = '';
        // $randum_number = rand(0, 999999);
        // for ($i = 0; $i<mb_strlen($current_time); $i++) {
        //     $session_id .= mb_substr($current_time, $i, 1).mb_substr($uid, $i, 1).mb_substr($randum_number, $i, 1);
        // }
        // $this->sessionId = md5($session_id);
        // $this->setValue('user_id', $uid);
    }

    public function isSoapSession()
    {
        $retour = false;
        if ($this->issetValue('SOAP_SESSION')) {
            $value = $this->getValue('SOAP_SESSION');
            if ($value == 1) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setSoapSession()
    {
        $this->setValue('SOAP_SESSION', 1);
    }

    public function setLoginSession()
    {
        $this->setValue('SOAP_LOGIN', 1);
    }

    public function isLoginSession()
    {
        $retour = false;
        if ($this->issetValue('SOAP_LOGIN')) {
            $value = $this->getValue('SOAP_LOGIN');
            if ($value == 1) {
                $retour = true;
            }
        }

        return $retour;
    }
}
