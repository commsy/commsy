<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos√© Manuel Gonz√°lez V√°zquez
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

$cid = 0;
if ( !empty($_GET['cid']) ) {
	$cid = $_GET['cid'];
}

@include_once('../etc/commsy/development.php');
if ( isset($c_cron_use_new)
     and !empty($c_cron_use_new)
     and $c_cron_use_new
   ) {
	if ( !isset($c_cron_use_old_array)
	     or empty($c_cron_use_old_array)
	     or empty($cid)
	     or !in_array($cid,$c_cron_use_old_array)
	   ) {
      include_once('cron_new.php');
	} else {
		include_once('cron_old.php');
	}
} else {
   include_once('cron_old.php');
}
?>