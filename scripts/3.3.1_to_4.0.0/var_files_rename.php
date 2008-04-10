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


include_once('../update_functions.php');

// there's no real chance for this script to fail...
$success = true;

// time management for this script
$time_start = getmicrotime();

echo ('This script renames the files in var/pictures/ and var/files/ to the new context ID format.<br/>'."\n");

rename_files('../../var/pictures');

rename_files('../../var/files');

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";

function rename_files($directory) {
	$directory_handle = opendir($directory);
	echo('<br/>Scanning directory '.$directory."\n");
	while(false !== ($entry = readdir($directory_handle))) {
		if (0 < preg_match('/cid(.+?)_rid(.+?)_(.+)/', $entry, $matches)) {
			// replace the first element (an unusable match) with the original filename
   		$matches[0] = $entry;
  		   $files[] = $matches;
		}
	}
	$count_files = count($files);
	if($count_files > 0) {
  	   echo('<br/>Start renaming...'."\n");
  	   init_progress_bar($count_files);
  	   foreach ($files as $match) {
  		   if ($match[2] == '0') {
  			   $new_name = 'cid'.$match[1].'_'.$match[3];
  		   } else {
  			   $new_name = 'cid'.$match[2].'_'.$match[3];
  		   }
  		   rename($directory.'/'.$match[0], $directory.'/'.$new_name);
  		   update_progress_bar($count_files);
  	   }
	} else {
		echo('<br/>No files found that need to be renamed.<br/>'."\n");
	}
}
?>