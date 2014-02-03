<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2010 Dr. Iver Jackewitz
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
$this->_flushHeadline('db: add description field to table tag_portfolio');

$success = true;

if ( !$this->_existsTable('zzz_external_viewer') ) {
   $sql = "CREATE TABLE zzz_external_viewer (item_id INT( 11 ) NOT NULL , user_id VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , INDEX ( item_id , user_id ));";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('tag_portfolio','description') ) {
	$sql = "ALTER TABLE `tag_portfolio` ADD `description` TEXT  NULL  AFTER `column`";
	$success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>