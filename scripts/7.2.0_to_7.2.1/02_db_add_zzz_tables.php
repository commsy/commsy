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
$this->_flushHeadline('db: add zzz_ tables for room backup');

$success = true;

$table_array = array();
$table_array[] = 'annotations';
$table_array[] = 'announcement';
$table_array[] = 'dates';
$table_array[] = 'discussionarticles';
$table_array[] = 'discussions';
$table_array[] = 'files';
$table_array[] = 'hash';
$table_array[] = 'homepage_link_page_page';
$table_array[] = 'homepage_page';
$table_array[] = 'items';
$table_array[] = 'item_link_file';
$table_array[] = 'labels';
$table_array[] = 'links';
$table_array[] = 'link_items';
$table_array[] = 'link_modifier_item';
$table_array[] = 'materials';
$table_array[] = 'noticed';
$table_array[] = 'reader';
$table_array[] = 'room';
$table_array[] = 'section';
$table_array[] = 'step';
$table_array[] = 'tag';
$table_array[] = 'tag2tag';
$table_array[] = 'tasks';
$table_array[] = 'todos';
$table_array[] = 'user';

$prefix = 'zzz_';

foreach($table_array as $table){
   if ( !$this->_existsTable($prefix.$table) ) {
      $sql = "CREATE TABLE ".$prefix.$table." LIKE ".$table;
      $success = $success AND $this->_select($sql);
   }
}

$this->_flushHTML(BRLF);
?>