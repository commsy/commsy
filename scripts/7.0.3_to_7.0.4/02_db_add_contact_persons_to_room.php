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
$this->_flushHeadline('db: add contact_persons and description to room and room_privat');

$success = true;

if ( !$this->_existsField('room','contact_persons') ) {
   $sql = "ALTER TABLE room ADD contact_persons VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsField('room','description') ) {
   $sql = "ALTER TABLE room ADD description TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsField('room_privat','contact_persons') ) {
   $sql = "ALTER TABLE room_privat ADD contact_persons VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsField('room_privat','description') ) {
   $sql = "ALTER TABLE room_privat ADD description TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;";
   $success = $success AND $this->_select($sql);
}
$this->_flushHTML(BRLF);
?>