<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2007 Iver Jackewitz
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

require_once('functions/security_functions.php');

class db_mysql_connector
{
    private $_db_errno = NULL;
    private $_db_error = NULL;
    private $_query_array = array();
    private $_log_query = false;
    private $_display = true;
    private $_query_failed = 0;

    public function performQuery($query)
    {
        global $symfonyContainer;
        $connection = $symfonyContainer->get('database_connection');

        $statement = $connection->query($query);
        $retour = NULL;

        if ($this->_log_query) {
            $this->_query_array[] = $query;
        }

        $this->_db_errno = $connection->errorCode();
        $this->_db_error = $connection->errorInfo();

        if (!$statement) {
            throw new Exception($this->_db_error[2], $this->_db_errno);
        } else {
            if (mb_substr(trim($query), 0, 6) === 'SELECT' || mb_substr(trim($query), 0, 4) === 'SHOW') {
                $retour = array();

                foreach ($statement as $row) {
                    $retour[] = $row;
                }
            } elseif (mb_substr(trim($query), 0, 6) === 'INSERT') {
                if (strstr($query, 'INSERT INTO chat')
                    || strstr($query, 'INSERT INTO auth')
                    || strstr($query, 'INSERT INTO item_link_file')
                    || strstr($query, 'INSERT INTO external2commsy_id')
                    || strstr($query, 'INSERT INTO links')
                    || strstr($query, 'INSERT INTO link_modifier_item')
                    || strstr($query, 'INSERT INTO materials')
                    || strstr($query, 'INSERT INTO noticed')
                    || strstr($query, 'INSERT INTO reader')
                    || strstr($query, 'INSERT INTO section')) {

                    $retour = $statement;
                } else {
                    $retour = $connection->lastInsertId();
                }
            } else {
                $retour = $statement;
            }

            $this->_query_failed = 0;
            unset($statement);
        }

        return $retour;
    }

    public function setLogQueries()
    {
        $this->_log_query = true;
    }

    public function getQueryArray()
    {
        return $this->_query_array;
    }

    public function getErrno()
    {
        if ($this->_db_errno === "00000") {
            return "";
        }

        return $this->_db_errno;
    }

    public function getError()
    {
        if (isset($this->_db_error[2])) {
            return $this->_db_error[2];
        }

        return "";
    }

    public function setDisplayOff()
    {
        $this->_display = false;
    }

    public function setDisplayOn()
    {
        $this->_display = true;
    }

    public function text_php2db($text)
    {
        return mysql_escape_mimic($text);
    }
}