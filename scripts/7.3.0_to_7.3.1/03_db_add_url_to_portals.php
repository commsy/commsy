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
$this->_flushHeadline('db: add url to table portal and server');

$success = true;

if ( !$this->_existsField('portal','url') ) {
   $sql = "ALTER TABLE portal ADD url VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('server','url') ) {
   $sql = "ALTER TABLE server ADD url VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>