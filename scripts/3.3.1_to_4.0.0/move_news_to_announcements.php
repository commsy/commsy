<?PHP
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

  /**
   * Generates an array out of an xml-string.
   * This function is used at the management of the extras field in the mysql-database
   * Uses expat XML parser / PHP XML-functions.
   *
   * @return  returns an array
   */

function DateAdd($v,$d=null , $f="d/m/Y"){
  $d=($d?$d:date("Y-m-d h:m:s"));
  return date($f,strtotime($v." days",strtotime($d)));
}

  /**
   * Generates an array out of an xml-string.
   * This function is used at the management of the extras field in the mysql-database
   * Uses expat XML parser / PHP XML-functions.
   *
   * @return  returns an array
   */
   function XML2Array2 ($text) {
      $text = '<SAVE>'.$text.'</SAVE>';
      $p = xml_parser_create();
      xml_parse_into_struct($p,$text,$vals,$index);
      $error = xml_get_error_code($p);
      xml_parser_free($p);
      if ( $error != XML_ERROR_NONE ) {
         $error_text = xml_error_string($error);
         include_once('functions/error_functions.php');
         trigger_error('XML-string:<br />'."\n".$text."\n".'<br />is not wellformed: '.$error_text, E_USER_WARNING);
         $result = array();
      } else {
         $result = _convertIntoArray($vals);
         $result = $result['SAVE'];
      }
      return $result;
   }

   function _convertIntoArray2 ($vals) {
      if (count($vals) == 0) {
         include_once('functions/error_functions.php');
         trigger_error('XML-string is not wellformed', E_USER_WARNING);
      } else {
         $retour = array();
         $entry = array_shift($vals);
         while ($entry) {
            // re-set integers in array-keys
            $tag = $entry['tag'];
            if ( strstr($tag,'XML_') ) {
               $tag_begin = substr($tag,0,4);
               if ($tag_begin = 'XML_') {
                  $tag = substr($tag,4);
               }
            }

            if ( $entry['type'] == 'complete' ) {
               if (isset($entry['value'])) {
                  // $retour[$tag] = htmlspecialchars($entry['value']);
                  // why this ^^^^^^^^ ????????
                  // should be:
                  $retour[$tag] = $entry['value'];

                  // convert > and < to their html entities (gt; and &lt;)
                  if ( strstr($retour[$tag],"%CS_AND;") ) {
                     $retour[$tag] = ereg_replace("%CS_AND;", "&", $retour[$tag]);
                  }
                  if ( strstr($retour[$tag],"&lt;") ) {
                     $retour[$tag] = ereg_replace("&lt;", "<", $retour[$tag]);
                  }
                  if ( strstr($retour[$tag],"&gt;") ) {
                     $retour[$tag] = ereg_replace("&gt;", ">", $retour[$tag]);
                  }

               } else {
                  $retour[$tag] = '';
               }
            } elseif ( $entry['type'] == 'open' ) {
               $retour[$tag] = _convertIntoArray($vals);
            } elseif ( $entry['type'] == 'close' ) {
               // Always corresponds to last open-tag.
               // Otherwise the XML-parser stops after open-tag.
               break; // Exit while-loop NOW!
            }
            $entry = array_shift($vals);
         }
      }
      return $retour;
   }




function moveNewsToAnnouncements () {
   global $test, $success, $error;

   $query = 'SHOW TABLES;';
   $result99 = select($query);
   $table_array = array();
   while ( $row99 = mysql_fetch_row($result99)) {
      $table_array[] = $row99[0];
   }
   if (in_array('news',$table_array)) {
      $count_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(rooms.item_id) FROM rooms ")));
      if ($count_rooms < 1) {
         echo "<br />nothing to do.";
         $success = true;
      } else {
         init_progress_bar($count_rooms);
         $select_query  = "SELECT rooms.* FROM rooms";
         $result1 = select($select_query);
         $number1 = mysql_num_rows($result1);
         while ( $row1 = mysql_fetch_array($result1)) {
            $room_id = $row1['item_id'];
            $extras = $row1['extras'];
            $extras = XML2Array2($row1['extras']);
            if (isset($extras['TIMESPREAD']) and !empty($extras['TIMESPREAD'])){
               $time = $extras['TIMESPREAD'];
            }else{
               $time = 7;
            }
            $select_query  = 'SELECT news.* FROM news WHERE news.room_id="'.$room_id.'"';
            $result = select($select_query);
            $number = mysql_num_rows($result);
            while ( $row = mysql_fetch_array($result)) {
               if (!empty($row['modification_date'])){
                  $temp_date = $row['modification_date'];
               }else{
                  $temp_date = $row['creation_date'];
               }

               $enddate = dateAdd($time,$temp_date,"Y-m-d h:m:s");
               $insert_query =
               'INSERT INTO announcements ( item_id , campus_id, room_id , creator_id , modifier_id, deleter_id ,
        				    creation_date, modification_date, deletion_date , title, description,
                                     enddate, public )
                      VALUES ('.'"'.$row['item_id'].'", '
                      		  .'"'.$row['campus_id'].'" , '
                      		  .'"'.$row['room_id'].'" , '
                      		  .'"'.$row['creator_id'].'" , ';
               if (empty($row['modifier_id'])){
                  $insert_query .= 'NULL, ';
               }else{
                  $insert_query .= '"'.$row['modifier_id'].'" , ';
               }
               if (empty($row['deleter_id'])){
                  $insert_query .= 'NULL, ';
               }else{
                  $insert_query .= '"'.$row['deleter_id'].'" , ';
               }
               if (empty($row['creation_date'])){
                  $insert_query .= 'NULL, ';
               }else{
                  $insert_query .= '"'.$row['creation_date'].'" , ';
               }
               if (empty($row['modification_date'])){
                  $insert_query .= 'NULL, ';
               }else{
                  $insert_query .= '"'.$row['modification_date'].'" , ';
               }
               if (empty($row['deletion_date'])){
                  $insert_query .= 'NULL, ';
               }else{
                  $insert_query .= '"'.$row['deletion_date'].'" , ';
               }
               $insert_query .= '"'.$row['title'].'" , '
                       		  .'"'.$row['description'].'" , '
                      		  .'"'.$enddate.'" ,'
                      		  .'"'.$row['public'].'")';
               $success = select($insert_query);

               if(!$success) {
                  echo "<br /><b>mysql complains:</b> ".$error;
                  return;
               }
            }
            update_progress_bar($count_rooms);
         }
         $update_query = 'UPDATE items SET type="announcements" WHERE type ="news"';
         $success = select($update_query);
         if(!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }
         $update_query = 'UPDATE link_items SET first_item_type="announcement" WHERE first_item_type ="news"';
         $success = select($update_query);

         if (!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }

         $update_query = 'UPDATE link_items SET second_item_type="announcement" WHERE second_item_type ="news"';
         $success = select($update_query);

         if (!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }

         $update_query = 'DROP table news ';
         $success = select($update_query);

         if(!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }
      }
   } else {
      echo "<br />nothing to do.";
      $success = true;
   }
}
echo ('This script moves news to annoucements.'."\n");
moveNewsToAnnouncements();
?>