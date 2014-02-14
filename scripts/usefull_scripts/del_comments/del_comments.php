<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2007 Iver Jackewitz
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

if ( empty($bash) ) {
   $NL = '<br/>'."\n";
} else {
   $NL = "\n";
}
if ( empty($bash) ) {
   $HR = '<hr/>'."\n";
} else {
   $HR = "\n".'---'."\n";
}

function del_comments2 ($string) {
   $string = preg_replace('~\/\*[\w\W]*\*\/?~u','',$string); // nimmt zu viel weg
   $string = preg_replace('~\/\/.*\n?~u','',$string);
   $string = preg_replace('~\n\n?~u',"\n",$string);
   $string = preg_replace('~\n\n?~u',"\n",$string);
   $string = preg_replace('~\n\n?~u',"\n",$string);
   // nur, wenn alle Lehrzeichen in der HTML-Ausgaben zu &nbsp; geändert wurden
   #$string = preg_replace('§[ ]?§','',$string);
   $string = trim($string);
   return $string;
}

function del_comments ($file) {
   global $NL, $bash;
   $retour = true;
   $file_string_old = file_get_contents($file);
   if (preg_match('~<?[PHP|php]+~u',$file_string_old)) {
      $file_string = del_comments2($file_string_old);
      if ( mb_strlen($file_string) != mb_strlen($file_string_old) ) {
         if (file_put_contents($file,$file_string)) {
            echo($file.': success'.$NL);
         } elseif ( empty($bash) or !$bash ) {
            $retour = false;
            echo($file.': <span style="color: red;">failed</span>'.$NL);
         } else {
            $retour = false;
            echo($file.': FAILED'.$NL);
         }
      } else {
         echo($file.': no comments inside'.$NL);
      }
      exit();
   }
   return $retour;
}

function del_comments_php ($file) {
   global $NL, $bash;
   $retour = true;
   $file_string_old = file_get_contents($file);
   if (preg_match('~<?[PHP|php]+~u',$file_string_old)) {
      $file_string = shell_exec('php '.escapeshellcmd('-w -f '.$file));
      if ( mb_strlen($file_string) != mb_strlen($file_string_old) ) {
         if (file_put_contents($file,$file_string)) {
            echo($file.': success'.$NL);
         } elseif ( empty($bash) or !$bash ) {
            $retour = false;
            echo($file.': <span style="color: red;">failed</span>'.$NL);
         } else {
            $retour = false;
            echo($file.': FAILED'.$NL);
         }
      } else {
         echo($file.': no comments inside'.$NL);
      }
   }
   return $retour;
}

function runFilesInDir ($directory) {
   $directory_handle  = opendir($directory);
   $do = true;

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
         $do = $do and runFilesInDir($directory.'/'.$entry);
      } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
         $do = $do and del_comments_php($directory.'/'.$entry);
      }
   }
   return $do;
}

chdir('../../..');

echo('this script deletes commentary lines'.$HR);
echo('Attention:'.$NL.'script needs write access to all files in the commsy tree'.$NL);
echo('you may need to change rights in the commsy directory (<code>chmod -R o+w</code>)'.$HR);

$do = runFilesInDir('.');
if (!$do) {
   echo('nothing to do'.$NL);
}
?>