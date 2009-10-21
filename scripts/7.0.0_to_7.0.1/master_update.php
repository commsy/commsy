<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// select scripts automatically
$title = 'Master Update Script for CommSy Update '.$current_dir;

if ( empty($bash) or !$bash ) {
   echo('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n");
   echo('<html>'."\n");
   echo('<head>'."\n");
   echo('<title>'.$title.'</title>'."\n");
   echo('</head>'."\n");
   echo('<body>'."\n");
   echo('<h2>'.$title.'</h2>'."\n");
} else {
   echo($title."\n");
}
flush();

// this update mechansim is old, use new one now
echo('This update mechanism is outdated. Please use the new one.');
echo('<br/><br/>1. Login in CommSy as root.');
echo('<br/>2. Go to the configuration of the commsy server.');
echo('<br/>3. Go to the update option.');

// end of execution time
if ( empty($bash) or !$bash ) {
   echo('</body>'."\n");
   echo('</html>'."\n");
} else {
   echo("\n");
}
flush();
?>