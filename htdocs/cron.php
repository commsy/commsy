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
if (!empty($_GET['cid'])) {
    $cid = $_GET['cid'];
} elseif (!empty($_SERVER["argv"][1])) {
    $cid = $_SERVER["argv"][1];
}

// pretend, we work from the CommSy basedir to allow
// giving include files without "../" prefix all the time.
chdir('..');

include_once('etc/cs_config.php');
if (isset($c_commsy_cron_token) and !empty($c_commsy_cron_token)) {
    // if cron token is enabled
    if (isset($_GET['cron_token']) and ($_GET['cron_token'] === $c_commsy_cron_token)) {
        include_once('cron_new.php');
    // try to get from command line - should be third parameter
    } elseif (isset($_SERVER["argv"][2]) && $_SERVER["argv"][2] === $c_commsy_cron_token) {
        include_once('cron_new.php');
    } else {
        die("cron token does not match");
    }
} else {
    // if cron token is disabled
    include_once('cron_new.php');
}
