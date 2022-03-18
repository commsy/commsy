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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Result;

require_once('functions/security_functions.php');

class db_mysql_connector
{
    private int $dbErrorCode = 0;
    private string $dbError = '';
    private array $queryArray = [];
    private bool $logQuery = false;

    /** @var Connection */
    private Connection $connection;

    public function __construct()
    {
        global $symfonyContainer;
        $this->connection = $symfonyContainer->get('database_connection');
    }

    /**
     * @param $query
     * @param array $params
     * @return Result|array|string
     * @throws \Doctrine\DBAL\Exception
     */
    public function performQuery($query, array $params = [])
    {
        try {
            $result = $this->connection->executeQuery($query, $params);

            if ($this->logQuery) {
                $this->queryArray[] = $query;
            }

            if (mb_substr(trim($query), 0, 6) === 'SELECT' || mb_substr(trim($query), 0, 4) === 'SHOW') {
                return $result->fetchAllAssociative();
            } elseif (mb_substr(trim($query), 0, 6) === 'INSERT') {
                if (strstr($query, 'INSERT INTO item_link_file')
                    || strstr($query, 'INSERT INTO external2commsy_id')
                    || strstr($query, 'INSERT INTO links')
                    || strstr($query, 'INSERT INTO link_modifier_item')
                    || strstr($query, 'INSERT INTO materials')
                    || strstr($query, 'INSERT INTO noticed')
                    || strstr($query, 'INSERT INTO reader')
                    || strstr($query, 'INSERT INTO section')) {

                    return $result;
                } else {
                    return $this->connection->lastInsertId();
                }
            } else {
                return $result;
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->dbErrorCode = $e->getCode();
            $this->dbError = $e->getMessage();

            throw $e;
        }
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function setLogQueries()
    {
        $this->logQuery = true;
    }

    public function getQueryArray()
    {
        return $this->queryArray;
    }

    public function getErrno()
    {
        return $this->dbErrorCode;
    }

    public function getError()
    {
        return $this->dbError;
    }

    public function text_php2db($text)
    {
        return mysql_escape_mimic($text);
    }
}