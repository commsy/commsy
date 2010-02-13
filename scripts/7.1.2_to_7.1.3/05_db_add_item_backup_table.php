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
$this->_flushHeadline('db: add database table item_backup');

$success = true;

$sql = "
	CREATE TABLE IF NOT EXISTS
		`item_backup` (
			`item_id` int(11) NOT NULL,
  			`backup_date` datetime NOT NULL,
  			`modification_date` datetime DEFAULT NULL,
  			`title` varchar(255) NOT NULL,
  			`description` text,
  			`public` tinyint(11) NOT NULL,
  			`special` text CHARACTER SET ucs2 NOT NULL,
  			PRIMARY KEY(`item_id`)
  		)
  	ENGINE=MyISAM DEFAULT CHARSET=utf8;
";
$success = $success AND $this->_select($sql);

$this->_flushHTML(BRLF);
?>