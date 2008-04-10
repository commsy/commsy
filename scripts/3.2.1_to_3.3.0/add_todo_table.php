<?php
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


include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

##########


function addTableToDo () {
   global $test, $success, $error;
   $query = "DROP TABLE IF EXISTS todos";
   if (!$test) {
       $success = select($query);
       if ($success) {
          echo('<br>delete table todos'."\n");
          echo ('<br>'.$query."\n");
       } else {
          echo "<br><b>mysql complains at line ".__LINE__.":</b> ".$error;
       }
   } else {
       echo ('<br>'.$query."\n");
   }

   $query = 'CREATE TABLE todos (
   item_id int( 11 ) NOT NULL default "0",
   campus_id int( 11 ) NOT NULL default "0",
   room_id int( 11 ) NOT NULL default "0",
   creator_id int( 11 ) NOT NULL default "0",
   modifier_id int( 11 ) default NULL ,
   deleter_id int( 11 ) default NULL ,
   creation_date datetime NOT NULL default "0000-00-00 00:00:00",
   modification_date datetime default NULL ,
   deletion_date datetime default NULL ,
   title varchar( 255 ) NOT NULL default "",
   date datetime default NULL ,
   status tinyint( 3 ) NOT NULL default "1",
   description text,
   public tinyint( 11 ) NOT NULL default "0",
   PRIMARY KEY (item_id) ,
   KEY campus_id (campus_id) ,
   KEY room_id (room_id) ,
   KEY creator_id (creator_id)
   ) TYPE = MYISAM ;';

   if (!$test) {
       $success = select($query);
       if ($success) {
          echo('<br>create table todo'."\n");
          echo ('<br>'.$query."\n");
       } else {
          echo "<br><b>mysql complains at line ".__LINE__.":</b> ".$error;
       }
   } else {
       echo ('<br>'.$query."\n");
   }


}


addTableToDo();





?>