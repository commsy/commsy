<?
// set_del_in_items_script.php
// Setzt in der items-Tabelle deletion_date und deleter_id,
// falls in zugehörigen items aus den anderen Tabellen deleter_id gesetzt ist


include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateCampusItem () {
   global $test;

   $select_query  = "SELECT campus.* FROM campus ";

   echo "<br />Hole alle Campus Items:";
   echo "<br />Select_Query: ".$select_query;
   $result = select($select_query);
   $number = mysql_num_rows($result);
   echo "<br />Anzahl: ".$number;

   while ( $row = mysql_fetch_array($result)) {
        $extras = $row['extras'];
        $item_id = $row['item_id'];

        echo "<hr>";
        echo 'Campus:'.$item_id.'<br />wird bearbeitet...<br /><br />Extra-Einträge:<br />'.$extras;
        if (substr_count($extras, '</TOPICS_ARRAY>')>0 ){
        echo '<br /><br />Das Array ist schon enthalten. Es werden keine Änderungen vorgenommen';
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
        	  echo '<br /><br />Der überarbeitete EXTRA-Eintrag:<br />'.$extras;
           $insert_query  = 'UPDATE campus SET extras="'.$extras.'"';
           $insert_query .= " WHERE item_id=".$item_id;

           echo "<br /><br /> Setze das neue Extra-Feld";
           echo "<br /> Insert_Query: ".$insert_query;
           if (!$test) {
              insert($insert_query);
           }
           $check_query = "SELECT item_id, extras FROM campus";
           $check_query .= " WHERE item_id=".$item_id;

           echo "<br /><br />Überprüfe, ob neues Extra-Feld vorhanden ist: ";
           echo "<br />Check_Query: ".$check_query;
           $check_result = select($check_query);
           while ( $check_row = mysql_fetch_array($check_result) ) {
              $item_id = $check_row['item_id'];
              $extras = $check_row['extras'];
              if (substr_count($extras, '</TOPICS_ARRAY>')>0 ){
                echo "<br />Hat alles geklappt!";
              }else{
                 echo"<br />Hat nicht funktioniert!!!!!";
              }
           }
        }
   }
   $row = "";
   $check_row = "";

}

updateCampusItem();

?>