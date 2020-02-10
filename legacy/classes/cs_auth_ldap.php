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
include_once('classes/cs_auth_manager.php');


/**
 * class for database connection to a LDAP-server
 * this class implements a manager for LDAP authentication
 */
class cs_auth_ldap extends cs_auth_manager
{
    /**
     * @var string
     */
    private $connectionString;

    /**
     * @var \cs_translator
     */
    private $translator;

    /**
     * @var string LDAP field name containing user id
     */
    private $fieldUserId = 'uid';

    /**
     * @var string baseDN for ldap search
     */
    private $baseDN;

    /**
     * @var string username for ldap connection
     */
    private $ldapUser;

    /**
     * @var string password for ldap connection
     */
    private $ldapPassword;

    private $userData;

    public function __construct()
    {
        global $environment;
        $this->translator = $environment->getTranslationObject();
    }

    public function setAuthSourceItem($value): void
    {
        parent::setAuthSourceItem($value);

        $auth_data_array = $value->getAuthData();

        $this->connectionString = $auth_data_array['HOST'];

        $this->ldapUser = $auth_data_array['USER'];
        $this->ldapPassword = $auth_data_array['PASSWORD'];

        $this->fieldUserId = $auth_data_array['DBSEARCHUSERID'];
        $this->baseDN = $auth_data_array['BASE'];

        if (!empty($auth_data_array['ENCRYPTION'])) {
            $this->_encryption = $auth_data_array['ENCRYPTION'];
        }
    }

    /** set user with write access
     * this method sets the user with write access
     *
     * @param string value user id
     */
    public function setUser($value)
    {
    }

    /** set password for user with write access
     * this method sets the password for the user with write access
     *
     * @param string value password
     */
    public function setPassword($value)
    {
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
        if (empty($password)) {
            return false;
        }

        $ldap = \Symfony\Component\Ldap\Ldap::create('ext_ldap', [
            'connection_string' => $this->connectionString,
        ]);

        try {
            $ldap->bind($this->ldapUser, $this->encryptPassword($this->ldapPassword));

            // search for user
            $userEntry = false;
            $searchFilter = "($this->fieldUserId=$uid)";
            foreach (explode(';', $this->baseDN) as $searchBase) {
                $query = $ldap->query($searchBase, $searchFilter);
                $results = $query->execute()->toArray();

                if (count($results) === 1) {
                    $userEntry = $results[0];
                    $this->userData[$uid] = $userEntry;
                    $access = $userEntry->getDn();
                }
            }

            if (!$userEntry) {
                $this->_error_array[] = $this->translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD', $uid);
            }

            try {
                $ldap->bind($userEntry->getDn(), $this->encryptPassword($password));
                return true;
            } catch (\Symfony\Component\Ldap\Exception\ConnectionException $exception) {
                $this->_error_array[] = $this->translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD', $uid);
            }
        } catch (\Symfony\Component\Ldap\Exception\ConnectionException $exception) {
            include_once('functions/error_functions.php');
            trigger_error('could not connect to server ' . $this->connectionString, E_USER_WARNING);
        }

        return false;
    }

    /** exists an user_id ? - NOT IMPLEMENTED YET
     * this method returns a boolean whether the user_id exists in the ldap-database or not
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return boolean true, if authentication already exists
     *                 false, if authentication not exists -> new user
     */
    public function exists($user_id)
    {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode EXISTS [LDAP] is not implemented!', E_USER_ERROR);
        return true;
    }

    /** save an authentication - NOT IMPLEMENTED YET
     * save an authentication into the ldap-database
     *
     * @param object cs_item item the authentication item
     */
    public function save($item)
    {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode SAVE [LDAP] is not implemented!', E_USER_ERROR);
    }

    /** change password - NOT IMPLEMENTED YET
     * this method changes the user password in the ldap-database
     *
     * @param string user_id the user id of the user
     * @param string password the new password of the user
     */
    public function changePassword($user_id, $password)
    {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode CHANGEPASSWORD [LDAP] is not implemented!', E_USER_ERROR);
    }

    /** delete an LDAP account - NOT IMPLEMENTED YET
     * this method deletes an LDAP account in the ldap-database
     *
     * @param string user_id the user id of the user
     */
    public function delete($user_id)
    {
        include_once('functions/error_functions.php');
        trigger_error('The methode DELETE [LDAP] is not implemented!', E_USER_ERROR);
    }

    /** get authentication item for a user (user_id) - NOT IMPLEMENTED YET
     * this method returns a authentication item for a user
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return object cs_item an authentication item
     */
    public function get($user_id)
    {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode GET [LDAP] is not implemented!', E_USER_ERROR);
    }

    /** get auth item form the auth_manager - NOT IMPLEMENTED YET
     * this method returns an auth item form the auth_manager
     *
     * @return object auth_item of the user
     */
    public function getItem()
    {
        include_once('functions/error_functions.php');
        trigger_error('The methode getItem [LDAP] is not implemented!', E_USER_ERROR);
    }

    /** get user information out of the auth source
     * this method returns an array of informations form the user
     * in the auth source
     *
     * @return array data of the user
     */
    public function get_data_for_new_account($uid, $password)
    {
        $data = [
            'firstname' => '',
            'lastname' => '',
            'email' => '',
        ];

        if (isset($this->userData[$uid])) {
            /** @var \Symfony\Component\Ldap\Entry $ldapEntry */
            $ldapEntry = $this->userData[$uid];

            if ($ldapEntry->hasAttribte('givenName')) {
                $data['firstname'] = $ldapEntry->getAttribute('givenName')[0];
            }

            if ($ldapEntry->hasAttribte('sn')) {
                $data['lastname'] = $ldapEntry->getAttribute('sn')[0];
            }

            if ($ldapEntry->hasAttribte('mail')) {
                $data['email'] = $ldapEntry->getAttribute('mail')[0];
            }
        }

        return $data;
    }
}