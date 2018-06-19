<?php
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

/** cs_auth_item is needed to create auth items
 */
include_once('classes/cs_auth_item.php');

/** text functions are needed for create and update sql statements
 */
include_once('functions/text_functions.php');

include_once('classes/cs_auth_manager.php');

/** class for database connection to the database table "auth"
 * this class implements a database manager for the table "auth"
 * maybe this class should named cs_auth_mysql?
 */
class cs_auth_mysql_typo3 extends cs_auth_manager
{


    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbalConnection;

    /**
     * @var cs_auth_item
     */
    private $item;

    /**
     * integer - containing the error number if an error occured
     */
    private $_dberrno;

    /**
     * string - containing the error text if an error occured
     */
    private $_dberror;

    /**
     * string - containing MySQL database table
     */
    private $dbTable;

    /**
     * string - containing MySQL database table field containing User-ID
     */
    private $fieldUserId;

    /**
     * string - containing MySQL database table field containing password
     */
    private $fieldPassword;

    /**
     * @var cs_translator
     */
    private $translator;

    /** constructor
     * the only available constructor, initial values for internal variables
     */
    function __construct()
    {
        $this->_is_implemented_array = [];

        $this->dbTable = 'fe_users';
        $this->fieldUserId = 'username';
        $this->fieldPassword = 'password';

        global $environment;
        $this->translator = $environment->getTranslationObject();
    }

    function setAuthSourceItem($value)
    {
        parent::setAuthSourceItem($value);
    }

    /** reset limits
     * reset limits of this class: room limit, delete limit
     */
    function resetLimits()
    {
    }

    /** get error number
     * this method returns the number of an error, if an error occured
     *
     * @return integer error number
     */
    function getErrorNumber()
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

    /** exists an authentication ?
     * this method returns a boolean whether the authentication exists in the database or not
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return boolean true, if authentication already exists
     *                 false, if authentication not exists -> new user
     */
    public function exists($user_id)
    {
        $this->get($user_id);
        $user_id = $this->item->getUserID();
        if (!empty($user_id)) {
            return true;
        } else {
            $this->_error_array[] = $this->translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
            return false;
        }
    }

    /** is the account granted ?
     * this method returns a boolean, if the account is granted in MySQL.
     *
     * @param string uid user id of the current user
     * @param string password the password of the current user
     *
     * @return boolean true, account is granted in MySQL
     *                 false, account is not granted in MySQL
     */
    public function checkAccount($uid, $password)
    {
        if ($this->exists($uid)) {
            $this->get($uid);
            if (!empty($this->_auth_data_array['ENCRYPTION']) and ($this->_auth_data_array['ENCRYPTION'] == 'md5')) {
                $checkpass = md5($password);
                if ($checkpass == $this->item->getPasswordMD5()) {
                    return true;
                } else {
                    $this->_error_array[] = $this->translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
                }
            } else {
                $checkpass = $password;
                if ($checkpass == $this->item->getPassword()) {
                    return true;
                } else {
                    $this->_error_array[] = $this->translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
                }
            }
        }
        return false;
    }

    /** get auth item form the auth_manager
     * this method returns an auth item form the auth_manager
     *
     * @return object auth_item of the user
     */
    public function getItem($user_id)
    {
        $retour = $this->item;
        if (empty($retour) or $retour->getUserID() != $user_id) {
            if (!empty($user_id)) {
                $this->get($user_id);
                $retour = $this->item;
            }
        }
        return $retour;
    }

    private function getDBLink(): \Doctrine\DBAL\Connection
    {
        if (!isset($this->_dblink)) {
            $authData = $this->_auth_data_array;

            $config = new \Doctrine\DBAL\Configuration();
            $connectionParams = [
                'dbname' => $authData['DBNAME'],
                'user' => $authData['USER'],
                'password' => $authData['PASSWORD'],
                'host' => $authData['HOST'],
                'driver' => 'pdo_mysql',
            ];

            $this->dbalConnection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        }

        return $this->dbalConnection;
    }

    /** get authentication item for a user (user_id), INTERNAL - do not use
     * this method returns a authentication item for a user
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return object cs_item an authentication item
     */
    private function get($userId)
    {
        if (!isset($this->item) or $this->item->getUserID() != $userId) {
            $this->item = NULL;

            $sql = 'SELECT * FROM ' . $this->dbTable . ' WHERE ' . $this->fieldUserId . ' = :userId';
            $stmt = $this->getDBLink()->prepare($sql);
            $stmt->bindValue('userId', $userId);
            $stmt->execute();

            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($user) {
                $this->item = $this->buildItem($user);
            }
        }
    }

    /** build a authentication item out of an (database) array - internal method, do not use
     * this method returns a authentication item out of a row form the database
     *
     * @param array array array with information about the authentication out of the database table "auth"
     *
     * @return object cs_item a authentication item
     *
     * @author CommSy Development Group
     */
    private function buildItem($userData): cs_auth_item
    {
        $item = new cs_auth_item();
        $item->setUserID($userData[$this->fieldUserId]);
        if (!empty($this->_auth_data_array['ENCRYPTION']) and ($this->_auth_data_array['ENCRYPTION'] == 'md5')) {
            $item->setPasswordMD5($userData[$this->fieldPassword]);
        } else {
            $item->setPassword($userData[$this->fieldPassword]);
        }
        return $item;
    }
}