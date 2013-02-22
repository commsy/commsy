<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Josï¿½ Manuel Gonzï¿½lez Vï¿½zquez
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


/** writes a content in a file
 *  this method writes the string content in the file named filename
 *
 *  @param string content which should be written in the file
 *  @param string filename  is the filename
 *
 * @author CommSy Development Group
 */

function write2File($content, $filename){
    $messagefile = fopen($filename,"w");
    fwrite($messagefile, $content);
    fclose($messagefile);
}

/**
 * Concatenates two file paths
 *
 * @param   $path1  the left part of the new path
 * @param   $path2  the right part of the new path
 * @return  returns the concatenated path as a string
 * @author  rickert
 *
 */
function concatPath($path1,$path2) {
   $newpath = '';
   $p1hasSlash = false;
   $p2hasSlash = false;

   if ( strlen($path2) != 0 ) {
      if ( strlen($path1) == (mb_strrpos($path1,'/')+1) ) {
         $p1hasSlash = true;
      }
      if ( mb_strpos($path2,'/') === false ) {
         $p2hasSlash=false;
      } elseif ( mb_strpos($path2,'/') ==0 ) {
         $p2hasSlash=true;
      }
      if ( !$p1hasSlash ) {
         $path1 = $path1.'/';
      }
      if ( $p2hasSlash ) {
         $path2 = substr($path2, 1, strlen($path2));
      }
   }
   $newpath = $path1.$path2;
   return $newpath;
}

function getFilesize($file) {
    $size = filesize($file);
	
    if($size < 1000) {
       return number_format($size, 0, ",", ".")." Bytes";
    }
    elseif($size < 1000000)
    {
       return number_format($size/1024, 0, ",", ".")." kB";
    }
    else
    {
	   return number_format($size/1048576, 0, ",", ".")." MB";
    }

}
?>