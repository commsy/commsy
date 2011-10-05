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
$this->_flushHeadline('db: update item search indices');

ini_set('max_execution_time', 0);

$success = true;

// add database tables
$sql = "
	CREATE TABLE IF NOT EXISTS `search_index` (
	  `si_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	  `si_sw_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	  `si_item_id` int(11) NOT NULL DEFAULT '0',
	  `si_item_type` varchar(15) NOT NULL,
	  `si_count` smallint(5) unsigned NOT NULL DEFAULT '0',
	  PRIMARY KEY (`si_id`),
	  UNIQUE KEY `un_si_sw_id` (`si_item_id`,`si_sw_id`,`si_item_type`),
	  KEY `si_sw_id` (`si_sw_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `search_time` (
	  `st_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	  `st_item_id` int(11) NOT NULL DEFAULT '0',
	  `st_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	  PRIMARY KEY (`st_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";
$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `search_word` (
	  `sw_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	  `sw_word` varchar(32) NOT NULL DEFAULT '',
	  `sw_lang` varchar(5) NOT NULL,
	  PRIMARY KEY (`sw_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";
$success = $success AND $this->_select($sql);

global $c_indexed_search;
if(isset($c_indexed_search) && $c_indexed_search === true) {
	$this->_flushHTML('The index process can take a long time - restarting it means loosing all data');
	$this->_flushHTML('<div id="indexing_status"></div>');
	$this->_flushHTML('<script src="javascript/jQuery/commsy/search_index.js" type="text/javascript"></script>'.LF);
} else {
	$this->_flushHTML('Indices not updated, feature is not activated!');
}

//$success = $success AND $this->_select($sql);

$this->_flushHTML(BRLF);
?>