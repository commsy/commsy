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
$this->_flushHeadline('db: bugfix item_link_file');

$success = true;

$sql = "INSERT INTO zzz_item_link_file SELECT item_link_file.* FROM item_link_file WHERE file_id IN (SELECT zzz_files.files_id FROM zzz_files)";
$success = $success AND $this->_select($sql);

if ($success) {
   $sql = "DELETE FROM item_link_file WHERE file_id IN (SELECT zzz_files.files_id FROM zzz_files)";
   $success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>