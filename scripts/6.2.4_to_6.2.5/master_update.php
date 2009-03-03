<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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
$test = false;
#$test = true;

if ( empty($bash) or !$bash ) {
   define('LINEBREAK',"<br/>\n");
} else {
   define('LINEBREAK',"\n");
}

include_once('../../etc/cs_constants.php');
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// select scripts automatically
$scripts = array();
$directory = '.';
$directory_handle  = opendir($directory);
while(false !== ($entry = readdir($directory_handle))) {
   if ( $entry != '.'
        and $entry != '..'
        and $entry != 'master_update.php'
        and $entry != 'master_update_bash.php'
        and !is_dir($directory.'/'.$entry)
        and !strstr($entry,'~')
      ) {
      $scripts[] = $entry;
   }
}
sort($scripts);

set_time_limit(0);

// start of execution time
$time_start_all = getmicrotime();

$current_dir = getcwd();
$current_dir = str_replace('\\','/',$current_dir);
$current_dir = substr($current_dir,strrpos($current_dir,'/')+1);
$current_dir = str_replace('_',' ',$current_dir);

$title = 'Master Update Script for CommSy Update '.$current_dir;

if ( empty($bash) or !$bash ) {
   echo('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n");
   echo('<html>'."\n");
   echo('<head>'."\n");
   echo('<title>'.$title.'</title>'."\n");
   echo('</head>'."\n");
   echo('<body>'."\n");
   echo('<h2>'.$title.'</h2>'."\n");
} else {
   echo($title."\n");
}
flush();

$first = true;
foreach ($scripts as $script) {
   $success = FALSE;
   if ($first) {
      $first = false;
   } else {
      if ( empty($bash) or !$bash ) {
         echo "<br/><b>---------------------------------</b><br/>"."\n";
      } else {
         echo "\n"."---------------------------------"."\n";
      }
   }
   if ( empty($bash) or !$bash ) {
      echo('<h3>'.$script);
   } else {
      echo($script);
   }
   if ($test) {
      echo(' (testing)');
   } else {
      echo(' (executing)');
   }
   if ( empty($bash) or !$bash ) {
      echo('</h3>'."\n");
   } else {
      echo("\n");
   }
   if ( empty($bash) or !$bash ) {
      echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   }
   flush();

   include_once($script);
   if ( empty($bash) or !$bash ) {
      echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   }
   flush();

   if ($success == FALSE) {
      if ( empty($bash) or !$bash ) {
         echo "<font color='#ff0000'><b> [failed]</b></font>"."\n";
      } else {
         echo " [failed]"."\n";
      }
      break;
   } else {
      if ( empty($bash) or !$bash ) {
         echo "<font color='#00ff00'><b> [done]</b></font>"."\n";
      } else {
         echo " [done]"."\n";
      }
   }
   if ( empty($bash) or !$bash ) {
      echo('<br>');
      echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   } else {
      echo("\n");
   }
   flush();

   // um mysql eine verschnaufpause zwischen jedem script zu gönnen
   sleep(5);
}

// set commsy server version
$sql = 'SELECT item_id,extras FROM server WHERE item_id=99;';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( !empty($row['item_id'])
     and $row['item_id'] == 99
   ) {
   if ( !empty($row['extras']) ) {
      $extras = unserialize($row['extras']);
   } else {
      $extras = array();
   }
   $current_dir_array = explode(' ',$current_dir);
   $extras['VERSION'] = $current_dir_array[2];

   $update_query = 'UPDATE server SET extras="'.addslashes(serialize($extras)).'" WHERE item_id=99;';
   if ( !select($update_query) ) {
      if ( empty($bash) or !$bash ) {
         echo('<br/>Error while updating version number in server item.');
      } else {
         echo("\n".'Error while updating version number in server item.');
      }
   }
} else {
   if ( empty($bash) or !$bash ) {
      echo('<br/>Server Item not found.');
   } else {
      echo("\n".'Server Item not found.');
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start_all,3);
if ( empty($bash) or !$bash ) {
   echo "<br/><br/><b>".count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours</b><br><br><br>\n";
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>';
   echo('</body>'."\n");
   echo('</html>'."\n");
} else {
   echo(count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours\n");
   echo("\n");
}
flush();
?>