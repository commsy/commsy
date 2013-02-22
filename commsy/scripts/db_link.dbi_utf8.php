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
//    along with CommSy

// DBI

function select($select, $quiet = false, $charset = 'utf8') {
   global $DB_Name,$DB_Hostname,$DB_Username, $DB_Password, $error, $errno, $success;
   $link = mysql_pconnect ($DB_Hostname, $DB_Username, $DB_Password)
           or die ("keine Verbindung möglich");

   $db_link = mysql_select_db($DB_Name,$link)
              or die ("Database nicht gefunden (beim lesen)");
   if ( $charset == 'utf8' ) {
      mysql_query("SET NAMES 'utf8'");
      mysql_query("SET CHARACTER SET 'utf8'");
   } else {
      mysql_query("SET NAMES 'latin1'");
      mysql_query("SET CHARACTER SET 'latin1'");
   }
   $result = mysql_query($select);
   $error = mysql_error();
   $errno = mysql_errno();
   if ( !empty($error) and !$quiet ) {
      echo (HLINE.$error.". QUERY: ".$select.HLINE);
      $success = false;
   }
   mysql_close ($link);
   return $result;
}

function select_auth($select) {
   global $AUTH_Name,$AUTH_Hostname,$AUTH_Username, $AUTH_Password, $error, $errno, $success;
   $link = mysql_pconnect ($AUTH_Hostname, $AUTH_Username, $AUTH_Password)
           or die ("keine Verbindung möglich");

   $db_link = mysql_select_db($AUTH_Name,$link)
              or die ("Database nicht gefunden (beim lesen)");
   $result = mysql_query($select);
   $error = mysql_error();
   $errno = mysql_errno();
   if ( !empty($error) ) {
      echo (HLINE.$error.". QUERY: ".$select.HLINE);
      $success = false;
   }
   mysql_close ($link);
   return $result;
}

function insert($insert, $charset = 'utf8') {
   global $DB_Name,$DB_Hostname,$DB_Username,$DB_Password,$success;
   $link2 = mysql_pconnect ($DB_Hostname, $DB_Username, $DB_Password)
            or die ("keine Verbindung möglich");

   $db_link = mysql_select_db($DB_Name,$link2)
              or die ("Database nicht gefunden (beim schreiben)");
   if ( $charset == 'utf8' ) {
      mysql_query("SET NAMES 'utf8'");
      mysql_query("SET CHARACTER SET 'utf8'");
   } else {
      mysql_query("SET NAMES 'latin1'");
      mysql_query("SET CHARACTER SET 'latin1'");
   }
   $result = mysql_query($insert);
   $error = mysql_error();
   $errno = mysql_errno();
   if ( !empty($error) ) {
      echo (HLINE.$error.". QUERY: ".$insert.HLINE);
      $success = false;
   } else {
      $success = true;
   }
   $lastSQLID = mysql_insert_id($link2);
   mysql_close($link2);
   if ( empty($lastSQLID) ) {
      $retour = $success;
   } else {
      $retour = $lastSQLID;
   }
   return $retour;
}
?>