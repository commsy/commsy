<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

// new version of the update mechanism
// -----------------------------------
// the following is part of the method "asHTML"
// from the object cs_update_view.php

set_time_limit(0);

// init $success
$success = true;

// headline
$this->_flushHeadline('db: set index');

$sql = 'ALTER TABLE hash ADD INDEX rss (rss);';
if ( !$this->_existsIndex('hash','rss') ) {
   $success = $success AND $this->_select($sql);
}

$sql = 'ALTER TABLE hash ADD INDEX ical (ical);';
if ( !$this->_existsIndex('hash','ical') ) {
   $success = $success AND $this->_select($sql);
}

$sql = 'ALTER TABLE links ADD INDEX to_item_id (to_item_id);';
if ( !$this->_existsIndex('links','to_item_id') ) {
   $success = $success AND $this->_select($sql);
}

$sql = 'ALTER TABLE links ADD INDEX link_type (link_type);';
if ( !$this->_existsIndex('links','to_item_id') ) {
   $success = $success AND $this->_select($sql);
}

$sql = 'ALTER TABLE room ADD INDEX type (type);';
if ( !$this->_existsIndex('room','type') ) {
   $success = $success AND $this->_select($sql);
}
?>