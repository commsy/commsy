<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Serves as prototype class for database interaction
 * @author fabian
 *
 */
abstract class MediabirdDbo {
	/**
	 * Connects database
	 */
	abstract function connect();
	/**
	 * Disconnects database
	 */
	abstract function disconnect();
	/**
	 * Retrieves a recordset that can be used in fetchNextRecord
	 * @param string $sql
	 * @param string $limit_from
	 * @param string $limit 
	 * @return stdClass
	 */
	abstract function getRecordset($sql,$limit_from='',$limit='');
	/**
	 * Determines the record count of a record set
	 * @param stdClass $result Record set given by getRecordSet 
	 * @return int
	 */
	abstract function recordLength($result);
	/**
	 * Fetches the next unread record from a recordset
	 * @param stdClass $result Recordset retrieved using getRecordSet
	 * @return stdClass record featuring the corresponding row's columns as properties
	 */
	abstract function fetchNextRecord($result);
	/**
	 * Retrieves as single record from the database
	 * @param string $table Table name without prefix
	 * @param string $select Where clause of query
	 * @return stdClass
	 */
	abstract function getRecord($table,$select);
	/**
	 * Retrieves 1 or many records from the database
	 * @param $table Table name without prefix
	 * @param $select Where clause of query
	 * @param $sort Sort clause
	 * @param $fields Fields to select
	 * @param $limitfrom Page offset
	 * @param $limitnum Record limit
	 * @return stdClass[] Array of records or null if none found
	 */
	abstract function getRecords($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='');
	/**
	 * Deletes records on the base of a where clause
	 * @param string $table Table name where to delete records
	 * @param string $select Where clause
	 * @return bool True on success, false otherwise
	 */
	abstract function deleteRecords($table,$select);
	/**
	 * Converts a record into an associative array
	 * @param stdClass $obj Record
	 * @return array
	 */
	abstract function recordToArray($obj);
	/**
	 * Escapes a string such that it can be safely stored in the database
	 * @param string $str Raw value
	 * @return string Escaped value
	 */
	abstract function escape($str);
	/**
	 * Updates a record specified by the id property of $record in the given table $table
	 * @param string $table Name of table without prefix
	 * @param stdClass $record Object featuring properties of the record to be updated
	 * @return bool True on success, false otherwise
	 */
	abstract function updateRecord($table,$record);
	/**
	 * Inserts a new record into the database
	 * @param string $table Table name without prefix
	 * @param stdClass $dataobject Record to be inserted
	 * @param bool $returnid True to return id of inserted record
	 * @param string $primarykey Not used
	 * @return int Id of inserted record if $returnId set to true
	 */
	abstract function insertRecord($table, $dataobject, $returnid = true, $primarykey = 'id');
	/**
	 * Converts a date value from the database into a time stamp
	 * @param string $date
	 * @return int
	 */
	abstract function timestamp($date);
	/**
	 * Converts a time stamp into database date format
	 * @param int $time
	 * @return string
	 */
	abstract function datetime($time);
	
	/**
	 * Count the records in a table which match a particular WHERE clause.
	 * @param string $table
	 * @param string $select
	 * @param string $countitem
	 * @return int
	 */
	abstract function countRecords($table, $select, $countitem='COUNT(*)');
	 
	
}
?>