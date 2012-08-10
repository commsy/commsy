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
$this->_flushHeadline('db: add workflow_read table');

$success = true;

$sql = "
	CREATE TABLE IF NOT EXISTS `portfolio` (
	  `item_id` int(11) NOT NULL DEFAULT '0',
	  `user_id` int(11) NOT NULL DEFAULT '0',
	  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
	  `description` mediumtext CHARACTER SET utf8 NOT NULL,
	  `modifictaion_date` datetime NOT NULL,
	  `deletion_date` datetime NOT NULL,
	  `rows` int(11) NOT NULL DEFAULT '0',
	  `columns` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`item_id`)
	);
";

$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `user_portfolio` (
	  `p_id` int(11) NOT NULL DEFAULT '0',
	  `u_id` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`p_id`,`u_id`)
	);
";

$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `tag_portfolio` (
	  `p_id` int(11) NOT NULL DEFAULT '0',
	  `t_id` int(11) NOT NULL DEFAULT '0',
	  `row` int(11) NOT NULL DEFAULT '0',
	  `column` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`p_id`,`t_id`),
	  KEY `row` (`row`,`column`)
	);
";

$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `annotation_portfolio` (
	  `p_id` int(11) NOT NULL DEFAULT '0',
	  `a_id` int(11) NOT NULL DEFAULT '0',
	  `row` int(11) NOT NULL DEFAULT '0',
	  `column` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`p_id`,`a_id`),
	  KEY `row` (`row`,`column`)
	);
";

$success = $success AND $this->_select($sql);

$this->_flushHTML(BRLF);
?>