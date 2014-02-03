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

if ( empty($bash) or !$bash ) {
   define('LINEBREAK',"<br/>\n");
} else {
   define('LINEBREAK',"\n");
}
if ( empty($bash) or !$bash ) {
   define('HLINE',"<hr/>\n");
} else {
   define('HLINE',"\n\n");
}
include_once('../../etc/cs_constants.php');
include_once('../../functions/text_functions.php');
include_once('../../functions/misc_functions.php');

function getCurrentDateTimeMinusDaysInMySQL ( $days ) {
   return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), (date('d')-$days), date('Y')));
}

function init_progress_bar($count,$title = 'Total entries to be processed',$value = '100%') {
   global $bash;
   echo LINEBREAK.$title.": ".$count."\n";
   echo LINEBREAK."|....................................................................................................|".$value."\n";
   if ( empty($bash) or !$bash ) {
      echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   }
   echo LINEBREAK."|";
   flush();
}

function update_progress_bar($total) {
   static $counter_upb = 0;
   static $percent = 0;
   $counter_upb++;
   $cur_percent = (int)(($counter_upb*100)/($total) );
   if ($percent < $cur_percent) {
      $add = $cur_percent-$percent;
      while ($add>0) {
         $add--;
         echo(".");
      }
      $percent = $cur_percent;
      flush();
   }
   if ($counter_upb==$total) {
      $counter_upb = 0;
      $percent = 0;
      echo('|');
   }
}

/*function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}*/

function getProcessedTimeInHTML ($time_start) {
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
$retour = LINEBREAK."Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)).LINEBREAK;
return $retour;
}

/** print value in mode print_r
 * methode to test and debug
 *
 * @param   $value

   function pr ($value) {
      echo('<pre>');
      print_r($value);
      echo('</pre>'."\n\n");
   }*/
?>