<?php

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateTitleCharTo255() {
   global $test;

   echo "<br />Setze alle Title und Name-Felder auf 255 Char<br />";

   if (!$test) {
      echo "<br />annotations:   <b>".(select('ALTER TABLE annotations CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />announcements:   <b>".(select(' ALTER TABLE announcements CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />campus:   <b>".(select(' ALTER TABLE campus CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />courses:   <b>".(select(' ALTER TABLE courses CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />dates:   <b>".(select(' ALTER TABLE dates CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />discussionarticles:   <b>".(select(' ALTER TABLE discussionarticles CHANGE subject subject varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />labels:   <b>".(select(' ALTER TABLE labels CHANGE name name varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />materials:   <b>".(select(' ALTER TABLE materials CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />news:   <b>".(select(' ALTER TABLE news CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />rooms:   <b>".(select(' ALTER TABLE rooms CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />section:   <b>".(select(' ALTER TABLE section CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo "<br />tasks:   <b>".(select(' ALTER TABLE tasks CHANGE title title varchar(255) NOT NULL default "";') ? "success" : "query failed!")."</b>";
      echo("<br /><br />done");
   } else {
      echo "<br />testing";
   }
}

updateTitleCharTo255();

?>