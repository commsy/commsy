<?
//TBD:test

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateRoomItemForTopic () {
   global $test, $success, $error;

   $select_query  = "SELECT rooms.* FROM rooms ";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   while ( $row = mysql_fetch_array($result)) {
        $extras = $row['extras'];
        $item_id = $row['item_id'];
        if (substr_count($extras, '</TOPICS_ARRAY>')>0 ){
            $success = TRUE;
        }else{
           $extras = $extras.'<TOPICS_ARRAY><NAME>topics</NAME>
	                           <DE><GENUS>N</GENUS>
	                           <NOMS>Thema</NOMS>
	                           <GENS>Themas</GENS>
	                           <AKKS>Thema</AKKS>
	                           <DATS>Thema</DATS>
	                           <NOMPL>Themen</NOMPL>
	                           <GENPL>Themen</GENPL>
	                           <AKKPL>Themen</AKKPL>
	                           <DATPL>Themen</DATPL>
	                           </DE>
	                           <EN><GENUS>N</GENUS>
	                           <NOMS>topic</NOMS>
	                           <GENS>topic</GENS>
	                           <AKKS>topic</AKKS>
	                           <DATS>topic</DATS>
	                           <NOMPL>topics</NOMPL>
	                           <GENPL>topics</GENPL>
	                           <AKKPL>topics</AKKPL>
	                           <DATPL>topics</DATPL>
	                           </EN>
	                           </TOPICS_ARRAY>';
           $insert_query  = "UPDATE rooms SET extras='".addslashes($extras)."'";
           $insert_query .= " WHERE item_id=".$item_id;

           if (!$test) {
              $success = select($insert_query);
              if(!$success) {
                  echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
#                  echo "<br />Query: ".$insert_query;
                  return;
              }
           }
           $check_query = "SELECT item_id, extras FROM rooms";
           $check_query .= " WHERE item_id=".$item_id;

#           echo "<br /><br />Überprüfe, ob neues Extra-Feld vorhanden ist: ";
#           echo "<br />Check_Query: ".$check_query;
           $check_result = select($check_query);
           while ( $check_row = mysql_fetch_array($check_result) ) {
              $item_id = $check_row['item_id'];
              $extras = $check_row['extras'];
              if (substr_count($extras, '</TOPICS_ARRAY>')>0 ){
                  $success = TRUE;
              }else{
                  $success = FALSE;
                 echo "<br />something strange happened...";
              }
           }
        }
   }
   $row = "";
   $check_row = "";
}

updateRoomItemForTopic();

?>