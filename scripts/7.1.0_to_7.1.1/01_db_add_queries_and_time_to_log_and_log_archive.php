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
$this->_flushHeadline('db: add queries and time to log and log_archive');

$success = true;

if ( !$this->_existsField('log','queries') ) {
   $sql = "ALTER TABLE log ADD queries SMALLINT NULL;";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsField('log','time') ) {
   $sql = "ALTER TABLE log ADD time VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('log_archive','queries') ) {
   $sql = "ALTER TABLE log_archive ADD queries SMALLINT NULL;";
   $success = $success AND $this->_select($sql);
}
if ( !$this->_existsField('log_archive','time') ) {
   $sql = "ALTER TABLE log_archive ADD time VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
   $success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>