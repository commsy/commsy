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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

require_once 'functions/security_functions.php';

class db_mysql_connector
{
    private int $dbErrorCode = 0;
    private string $dbError = '';
    private array $queryArray = [];
    private bool $logQuery = false;

    private readonly Connection $connection;

    public function __construct()
    {
        global $symfonyContainer;
        $this->connection = $symfonyContainer->get('database_connection');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function performQuery($query, array $params = []): Result|array|string
    {
        try {
            $result = $this->connection->executeQuery($query, $params);

            if ($this->logQuery) {
                $this->queryArray[] = $query;
            }

            if ('SELECT' === mb_substr(trim((string) $query), 0, 6) || 'SHOW' === mb_substr(trim((string) $query), 0, 4)) {
                return $result->fetchAllAssociative();
            } elseif ('INSERT' === mb_substr(trim((string) $query), 0, 6)) {
                if (strstr((string) $query, 'INSERT INTO item_link_file')
                    || strstr((string) $query, 'INSERT INTO external2commsy_id')
                    || strstr((string) $query, 'INSERT INTO links')
                    || strstr((string) $query, 'INSERT INTO link_modifier_item')
                    || strstr((string) $query, 'INSERT INTO materials')
                    || strstr((string) $query, 'INSERT INTO noticed')
                    || strstr((string) $query, 'INSERT INTO reader')
                    || strstr((string) $query, 'INSERT INTO section')) {
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
