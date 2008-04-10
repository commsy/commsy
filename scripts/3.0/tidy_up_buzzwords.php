<?
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../../functions/misc_functions.php');

global $test, $success, $error;

$query_campus = "SELECT labels.item_id, TRIM(labels.name) AS trim_name, labels.campus_id FROM labels INNER JOIN campus ON (campus.item_id = labels.campus_id) WHERE labels.type='buzzword' AND labels.room_id='0' ORDER BY labels.campus_id, trim_name";
$query_rooms = "SELECT labels.item_id, TRIM(labels.name) AS trim_name, labels.campus_id, labels.room_id FROM labels INNER JOIN rooms ON (rooms.item_id = labels.room_id) WHERE labels.type='buzzword' ORDER BY labels.room_id, trim_name";

//count doubles
$double =0; 

$from_db_campus=select($query_campus);
$from_db_rooms=select($query_rooms);

$result_campus=mysql_fetch_assoc($from_db_campus); 
$result_rooms=mysql_fetch_assoc($from_db_rooms); 

//----------------------campus-----------------------
//Sort results into array with campus_id as key
$campus_array=array();
$temp_array=array();
$campus_id = 0;
while($result_campus) {    
   if ($result_campus['campus_id'] == $campus_id) {
       $temp_array[]=$result_campus;
   } else {      
      if ($campus_id != 0) {
         $campus_array[$campus_id] = $temp_array;  
      }                 
      $campus_id = $result_campus['campus_id'];
      $temp_array = array();
   }
   $result_campus=mysql_fetch_assoc($from_db_campus);       
}

//sort campus array, so that every campus_id contains an array with all buzzwords. 
//These Buzzwords contain all items that have this name. so if it's size is >1, there are some doublettes. 
$double_array = array();
$name='';
foreach ($campus_array as $id => $campus) {
   foreach ($campus as $item) { 
       if ($item['trim_name'] == $name) {
           $temp_array[]=$item;
       } else {      
          if ($name != '') {
             $double_array[$id][$name] = $temp_array;  
          }                 
          $name = $item['trim_name'];
          $temp_array = array();
          $temp_array[]=$item; 
       }
   }          
}

//process the sorted array for campus buzzwords
foreach ($double_array as $id => $campus) {
   foreach ($campus as $buzzword => $items) {
      //if (count($items) >1) {
        mergeBuzzwords($items);          
      //} 
       
   }
}

//-------------------rooms------------------------------
//Sort results into array with room_id as key
$rooms_array=array();
$temp_array=array();
$rooms_id = 0;
while($result_rooms) {     
   if ($result_rooms['room_id'] == $rooms_id) {
       $temp_array[]=$result_rooms;
   } else {      
      if ($rooms_id != 0) {
         $rooms_array[$rooms_id] = $temp_array;  
      }                 
      $rooms_id = $result_rooms['room_id'];
      $temp_array = array();
   }
   $result_rooms=mysql_fetch_assoc($from_db_rooms);       
}

//sort rooms array, so that every room_id contains an array with all buzzwords. 
//These Buzzwords contain all items that have this name. so if it's size is >1, there are some doublettes. 
$double_array = array();
$name='';
foreach ($rooms_array as $id => $rooms) {
   foreach ($rooms as $item) { 
       if ($item['trim_name'] == $name) {
           $temp_array[]=$item;
       } else {      
          if ($name != '') {
             $double_array[$id][$name] = $temp_array;  
          }                 
          $name = $item['trim_name'];
          $temp_array = array();
          $temp_array[]=$item; 
       }
   }          
}

//process the sorted array for campus buzzwords
foreach ($double_array as $id => $room) {
   foreach ($room as $buzzword => $items) {
      //if (count($items) >1) {
        mergeBuzzwords($items);          
      //} 
       
   }
}

//Merges double Buzzwords- keeps the first one and cleans up its name (trim)
//Deletes the other buzzwords of same name
//redirects links of deleted buzzwords to kept buzzword 
function mergeBuzzwords($item_array) {
    global $test, $success, $error;
    //id of buzzword that will not be deleted
    $merge_to_id = $item_array[0]['item_id'];
    // clean up the name of the buzzword- we have here trimmed names, so better make sure it is trimmed in db, too
    $clean_name_query = "UPDATE labels SET name='".addslashes($item_array[0]['trim_name'])."' WHERE item_id='".$merge_to_id."'";
    //echo "Updating kept buzzword<br />";
    $success = select($clean_name_query);
    
    if (!$success) {
       echo $error;    
    }
    
    for ($i=1;$i < count($item_array); $i++) {
       $double++; 
       $delete_buzz_id = $item_array[$i]['item_id'];
       $campus_id = $item_array[$i]['campus_id'];
       $room_id = $item_array[$i]['room_id'];
       echo "Delete unused buzz ".$delete_buzz_id."<br />";
       $delete_query="DELETE FROM labels WHERE item_id='".$delete_buzz_id."' LIMIT 1";
       $success = select($delete_query);
       if (!$success) {
          echo $error;    
       }
       $success = redirect_links($delete_buzz_id, $merge_to_id,$campus_id,$room_id);
       if (!$success) {
          echo $error;    
       }           
    } 
    
     
}

function redirect_links($deleted_id,$new_id,$campus_id,$room_id) {
    global $test, $success, $error;
   $redirect_query = "UPDATE links SET to_item_id='".$new_id."' WHERE to_item_id='".$deleted_id."' AND link_type='buzzword_for'";
   $redirect_query .="AND campus_id='".$campus_id."' AND room_id='".$room_id."'";
   //echo "redirect<br />-----<br /><br />";
   $success = select($redirect_query);
   if (!$success) {
       echo $error;    
    }       
}


echo "Skript processed ".$double." double entries!" 
 
?>