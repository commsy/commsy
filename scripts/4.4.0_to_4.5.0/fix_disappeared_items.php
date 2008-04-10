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

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

set_time_limit(0);
$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = true; // $counter form master_update.php
$success = true;

// init database connection
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script completes the item-table when an item is only stored in its specific table or data is wrong in items table");
if ($do_it) {
   echo("<br/>Correcting errors!");
} else {
   echo("<br/>Only Detecting errors!");
}

if($do_it) {
	/* item.type sometimes contains old type forms (in plural, ending on 's'). Change these to singular */
	echo ("<br />Cleaning up items.type (removing plural forms)");
	$count_items = array_shift(mysql_fetch_row(mysql_query("SELECT COUNT(items.item_id) FROM items;")));   
	init_progress_bar($count_items);
	
	$query = "SELECT item_id,type FROM items WHERE 1";
	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	while ($row) {
		$lastchar = substr($row[1], -1); 	
		$type = $row[1];
		if ($lastchar == 's') {	
			$type = substr($row[1],0,strlen($row[1])-1);		
		}
		$query = 'UPDATE items SET type="'.$type.'" WHERE item_id = "'.$row[0].'"';
		mysql_query($query);
		update_progress_bar($count_items);
		$row = mysql_fetch_row($result);
	}
	echo "<br />";
}
/*tables to process*/
$tables = array('annotations','announcement','room','dates','discussionarticles','discussions','labels','link_items','materials','ontologies','portal','section','tasks','todos','server','user');

//Get number of items to process
$count_items = 0;
foreach($tables as $table) { 
	
      $count_items += array_shift(mysql_fetch_row(mysql_query("SELECT COUNT( DISTINCT ".$table.".item_id) FROM ".$table.";")));   
	
}
$not_in_items = 0;
$inacurate_in_items = 0;

init_progress_bar($count_items);
$counter =0;
foreach ($tables as $table) {
	//sections and materials have to be treated differently due to version... last version must be updated, so get it and not any other
   if ($table == 'materials') {
	   $query = "SELECT item_id,deleter_id,deletion_date,modification_date,context_id,MAX( version_id ) FROM materials WHERE 1 GROUP BY item_id";
	} elseif ($table == 'section') {
	   $query = "SELECT item_id,deleter_id,deletion_date,modification_date,context_id,MAX( version_id ) FROM section WHERE 1 GROUP BY item_id";
	} else {
      $query = 'SELECT item_id,deleter_id,deletion_date,modification_date,context_id FROM '.$table;	
	}
	$result = mysql_query($query);
	
	if ($error = mysql_error() ) {
		echo $error.". QUERY: ".$query;
		$success = false;
	} else {		
		$row = mysql_fetch_row($result);	
		//Check all items in all tables
		while($row) {			
			$item_id = $row[0];
			
			$check_query = 'SELECT item_id,deleter_id,deletion_date,modification_date,context_id,type FROM items WHERE item_id = "'.$item_id.'"';				
			$check_result = mysql_query($check_query);
			if ($error = mysql_error() ) {
				echo $error.". QUERY: ".$query;
				$success = false;
			} else {					
			   //$check_row contains data from the items table to check it, $row contains the original data from the respective table
				$check_row = mysql_fetch_row($check_result);				
				if(empty($check_row)) {
				   //Item not found in table, so add it
					$not_in_items++;					
					if ($do_it) {					
					   $type = getCommsyType($table,$item_id);							
						$query = 'INSERT INTO items VALUES ("'.$item_id.'",'.$row[4].',"'.$type.'",'.($row[1]?'"'.$row[1].'"':'NULL').','.($row[2]?'"'.$row[2].'"':'NULL').','.($row[3]?'"'.$row[3].'"':'NULL').')';						
						mysql_query($query);
						if ($error = mysql_error() ) {
							echo $error.". QUERY: ".$query;
							$success = false;
						}
					}
				} else {				
					if ($row[1] != $check_row[1] OR $row[2] != $check_row[2] OR $row[3] != $check_row[3] OR $row[4] != $check_row[4]) {				
					   //item found, but inacurate
						$inacurate_in_items++;
						if ($do_it) {
							if ($check_row[5] != getCommsyType($table,$item_id))
							{			
								//check if type corresponds to table... if not, repair (-> item_id is duplicated!). Generate new item with data and set ids correct
							   $type = getCommsyType($table,$item_id);	
							   $query_insert = 'INSERT INTO items VALUES (NULL,'.$row[4].',"'.$type.'",'.($row[1]?'"'.$row[1].'"':'NULL').','.($row[2]?'"'.$row[2].'"':'NULL').','.($row[3]?'"'.$row[3].'"':'NULL').')';						
								mysql_query($query_insert);
								$new_item_id = mysql_insert_id();
								$query_update = 'UPDATE '.$table.' SET item_id="'.$new_item_id.'" WHERE item_id = "'.$item_id.'"';
								mysql_query($query_insert);
								mysql_query($query_update);
								if ($error = mysql_error() ) {
									echo $error.". QUERY: ".$query;
									$success = false;
								}								
							}
					
							$query_items = 'UPDATE items SET deleter_id = '.($row[1]?'"'.$row[1].'"':'NULL').', deletion_date = '.($row[2]?'"'.$row[2].'"':'NULL').', modification_date = '.($row[3]?'"'.$row[3].'"':'NULL').', context_id = "'.$row[4].'" WHERE item_id ="'.$item_id.'"';						
							if ($table == 'materials' OR $table == 'section') {
							   $query_items .= 'AND version_id = "'.$row[5].'"';
							}
							
							if ($row[1] === 0) {
								//Deleter id of reference item nulled with '0' not 'NULL', so change that
								$query_table = 'UPDATE '.$table.' SET deleter_id='.($row[1]?'"'.$row[1].'"':'NULL').'WHERE item_id ="'.$item_id.'"';						
								
								mysql_query($query_table);								
								if ($error = mysql_error() ) {
									echo $error.". QUERY: ".$query;
									$success = false;
								}
							}								
                     	
							mysql_query($query_items);							
							if ($error = mysql_error() ) {
								echo $error.". QUERY: ".$query;
								$success = false;
							}
						}
					}
				}			
				update_progress_bar($count_items);	
			   $counter++;
				$row = mysql_fetch_row($result);	
			}  
		}
	}	
}

echo ("<br><br><font color='#ff0000'>".$not_in_items."</font> items not in item-table");

echo ("<br><font color='#ff0000'>".$inacurate_in_items."</font> items inacurate in item-table");
echo ("<br>".$counter);

// end of execution time
echo(getProcessedTimeInHTML($time_start));

function getCommsyType($table,$id) {   
   $type = $table;	
	
   switch ($table) {
		case 'annotations':
		   $type = 'annotation';
		   break;	
		case 'room':
			$query = 'SELECT type FROM room WHERE item_id = "'.$id.'"';
			$type = array_shift(mysql_fetch_row(mysql_query($query)));
			break;
		case 'dates':
		   $type = 'date';
		   break;
		case 'discussionarticles':
		   $type = 'discarticle';
		   break;
      case 'discussions':
		   $type = 'discussion';
		   break;		
		case 'labels':
		   $type = 'label';
		   break;
		case 'link_items':
		   $type = 'link_item';
		   break;		
		case 'ontologies':
		   $type = 'ontology';
		   break;		
		case 'tasks':
		   $type = 'task';
		   break;
		case 'todos':
		   $type = 'todo';
		   break;
		case 'materials':
		   $type = 'material';
			break;
			
	}
   return $type;	
}

?>