<?php

include_once('migration.conf.php');
include_once('db_link.dbi.php');

function updateRoomStatus() {
   global $test;
   
   echo "<br />Setze den Status aller Projekträume ohne Namen auf 4 (noch nie geöffnet)";
   
   $select = 'SELECT * FROM rooms WHERE title =""';
   $result = select($select);
   $number = mysql_num_rows($result);
   echo "<br />Anzahl der Räume ohne Titel: ".$number;
   
   $select = 'SELECT * FROM rooms WHERE title ="" AND status!=4';
   $result = select($select);
   $number = mysql_num_rows($result);
   echo "<br />Anzahl betroffener Räume: ".$number;


   if(!$test) {
      $sql = 'UPDATE rooms SET status="4" WHERE title =""';
      echo "<br /><b>".(select($sql) ? "success" : "query failed!")."</b>";
   } else {
      echo "<br />testing";
   }
}

updateRoomStatus();

?>