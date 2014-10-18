<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2012 Dr. Iver Jackewitz
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

// headline
$this->_flushHeadline('db: clean title fields');

$success = true;

$queryArray = array(
	"annotations"			=> array(
		"select"				=> array("item_id", "title"),
		"where"					=> "title"
	),
	"announcement"			=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"dates"					=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"discussionarticles"	=> array(
			"select"			=> array("item_id", "subject"),
			"where"				=> "subject"
	),
	"discussions"			=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"labels"				=> array(
			"select"			=> array("item_id", "name"),
			"where"				=> "name"
	),
	"materials"				=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"section"				=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"step"				=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"tag"				=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	),
	"todos"				=> array(
			"select"			=> array("item_id", "title"),
			"where"				=> "title"
	)
);

$itemArray = array();

foreach ( $queryArray as $table => $queryEntry )
{
	$query = "SELECT ";
	$query .= implode(",", $queryEntry["select"]);
	$query .= " FROM " . $table;
	$query .= " WHERE " . $queryEntry["where"] . " LIKE '%<script%'";
	
	$response = $this->_select($query);
	
	foreach ( $response as $row )
	{
		$itemArray[$row["item_id"]] = array(
			"table"		=> $table,
			"field"		=> $queryEntry["where"],
			"content"	=> $row[$queryEntry["where"]]
		);
	}
	
	$success = $success AND $response;
}

require_once 'libs/HTMLPurifier/HTMLPurifier.auto.php';
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', NULL);

$Purifier= new HTMLPurifier($config);

foreach ( $itemArray as $id => $item )
{
	$item["content"] = $Purifier->purify($item["content"]);
	
	$query = "UPDATE " . $item["table"] . " SET ";
	$query .= $item["field"] . " = '" . mysql_real_escape_string($item["content"]) . "'";
	$query .= " WHERE item_id = " . $id;
	
	$success = $success AND $this->_select($query);
}

$this->_flushHTML(BRLF);
?>