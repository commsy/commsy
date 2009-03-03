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

function del_dos_line_feeds ($file) {
   $retour = false;
   $file_string = file_get_contents($file);
   if (preg_match('~<?[PHP|php]+\r\r~u',$file_string)) {
      $file_string = preg_replace('~\r\n?~u',"\n",$file_string);
      $file_string = preg_replace('~\n\n?~u',"\n",$file_string);
      $file_string = trim($file_string);
      if (@file_put_contents($file,$file_string)) {
         echo($file.' success<br/>'."\n");
      } else {
         echo($file.' <span style="color: red;">failed</span><br/>'."\n");
      }
      $retour = true;
   }
   return $retour;
}

function runFilesInDir ($directory) {
   $directory_handle  = opendir($directory);
   $do = false;

   while (false !== ($entry = readdir($directory_handle))) {
      if ( $entry != '.'
           and $entry != '..'
           and !mb_stristr($entry,'CVSROOT')
           and !mb_stristr($entry,'lib')
           and !mb_stristr($entry,'TestSource')
           and !mb_stristr($entry,'var')
           and !mb_stristr($entry,'CVS')
           and is_dir($directory.'/'.$entry)
         ) {
         $do = $do or runFilesInDir($directory.'/'.$entry);
      } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
         $do = $do or del_dos_line_feeds($directory.'/'.$entry);
      }
   }
   return $do;
}

chdir('../../..');

echo('this script deletes msdos line feed and change it to unix line feed<hr/>');
echo('Attention:<br/>script needs write access to all files in the commsy tree<br/>');
echo('you may need to change rights in the commsy directory (<code>chmod -R o+w</code>)<hr/>');

$do = runFilesInDir('.');
if (!$do) {
   echo('nothing to do');
}
?>