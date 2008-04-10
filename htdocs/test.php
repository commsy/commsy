<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="de">
<head><title>CommSy - Test</title></head>
<body>
<p>WEBSERVER IS UP</p>
<p>
<?php
flush();
chdir('..');
include_once('etc/cs_constants.php');
echo('PHP IS UP'.LF);
flush();
?>
</p>
<p>
<?php
include_once('etc/cs_config.php');
include_once('classes/db_mysql_connector.php');
$db_connector = new db_mysql_connector($db['normal']);
$db_connector->setDisplayOff();
$query = 'SHOW DATABASES;';
$result = $db_connector->performQuery($query);
$databases = array();
foreach ( $result as $row ) {
   $databases[] = $row['Database'];
}
if ( in_array($db['normal']['database'],$databases) ) {
   echo('MYSQL IS UP');
   flush();
}
?>
</p>
</body>
</html>