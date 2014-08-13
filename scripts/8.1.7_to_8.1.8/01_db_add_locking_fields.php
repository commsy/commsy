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
$this->_flushHeadline('db: adding locking fields to some tables');

$fields = array(
	"locking_date",
	"locking_user_id"
);

$tables = array(
	"materials",
	"announcement",
	"dates",
	"discussions",
	"labels",
	"todos",
	"zzz_materials",
	"zzz_announcement",
	"zzz_dates",
	"zzz_discussions",
	"zzz_labels",
	"zzz_todos"
);

$success = true;
foreach ($tables as $table) {
	foreach ($fields as $field) {
		if (!$this->_existsField($table, $field)) {
			$sql = "ALTER TABLE " . $table . " ADD " . $field . " ";

			if ($field == "locking_date") {
				$sql .= "DATETIME DEFAULT NULL;";
			} else {
				$sql .= "INT DEFAULT NULL;";
			}

			$success = $success && $this->_select($sql);
		}
	}
}

$this->_flushHTML(BRLF);