<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// headline
$this->_flushHeadline('db: add index to links table');

$success = true;

if ( !$this->_existsIndex('links','link_type ') ) {
   $sql = "ALTER TABLE links ADD INDEX ( link_type );";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsIndex('links','from_item_id ') ) {
   $sql = "ALTER TABLE links ADD INDEX ( from_item_id );";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsIndex('links','from_version_id ') ) {
   $sql = "ALTER TABLE links ADD INDEX ( from_version_id );";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsIndex('links','to_item_id ') ) {
   $sql = "ALTER TABLE links ADD INDEX ( to_item_id );";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsIndex('links','to_version_id ') ) {
   $sql = "ALTER TABLE links ADD INDEX ( to_version_id );";
   $success = $success AND $this->_select($sql);
}
$this->_flushHTML(BRLF);
?>