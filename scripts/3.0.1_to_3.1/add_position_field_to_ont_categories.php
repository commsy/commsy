<?
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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


function add_position_field () {
   $success = false;
   echo ("This script adds the field 'position' to the table 'ont_categories'.<br />");
   $alter_query = "ALTER  TABLE ont_categories ADD position INT NOT NULL AFTER ontology_id";
   global $test;
   if(!$test) {
      $success = select($alter_query);
   }
   return $success;
}

$success = add_position_field();

?>