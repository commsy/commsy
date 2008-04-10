<?php
//
//    Class is based on Script by Oliver Schildmann (cybaer@binon.net)
//    Located at http://coding.binon.net/server/zip.htm
//    From March, 23rd 2007
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
//

class ziparch {

 function mkzip($source='.', $zipfile='',$catch = TRUE, $include_basedir = TRUE) {

   if(function_exists('gzcompress')) {
   
      $worklist=array();
      $prepath = '';
      $listtype = '';
      $use_folder_ext = TRUE;
      $use_file_ext = FALSE;
      $overwrite_entries = TRUE;
      if(is_array($source)) {
          $filelist = $source;
          $listtype = 'entries';
    
          if(!$zipfile && is_string($include_basedir) && strlen($include_basedir)) {
             $include_basedir = unify_path($include_basedir);
             $zipname = realDirname($include_basedir,1,FALSE);
             $zipfile = getAbsPath(filename($zipname,!$use_folder_ext).'.zip');
          }	
      } elseif(abs_path($source)) {
    
         if(!$zipfile) {
            $zipfile = getAbsPath(filename($source,!(is_dir($source)?$use_folder_ext:$use_file_ext)).'.zip');
         } elseif(is_path($zipfile)) {
              $zipfile .= filename($source,!(is_dir($source)?$use_folder_ext:$use_file_ext)).'.zip';
         }
         if(is_dir($source)) {
            $filelist = filelist('',$source,'',($catch),FALSE);
            $listtype = 'directory';
         } elseif(is_file($source)) {
            $filelist = array($source);
            $listtype = 'entries';
		 }
      }

   
      if(!$listtype || !abs_path($zipfile) || !isWritable($zipfile)) {
         $result = FALSE;
      } else {
           if($include_basedir != FALSE) {
              if(is_string($include_basedir) && strlen($include_basedir)) {
                 $prepath = $include_basedir.'/';
              } elseif($listtype == 'directory') {
                 $prepath = basename(($source)?realpath($source):getcwd()).'/';
			  }
              if($prepath) { $prepath = str_replace('//','/',unify_path($prepath.'/')); }
          }
          foreach($filelist as $filename) {
             $serverFilename = (($listtype == 'directory')?($source.'/'):'').$filename;
             if(is_file($serverFilename)) {
                if($listtype == 'entries') { 
				   $filename = basename($filename); 
				}
				$worklist[$prepath.$filename] = $serverFilename;
             }
          }
          unset($filelist);

          if(file_exists($zipfile)) {
             $zh = zip_open($zipfile);
             if($zh) {
                while($entry = zip_read($zh)) {
                   if(zip_entry_open($zh,$entry,'r')) {
                      $entryFilename = zip_entry_name($entry);
                      if(!isset($worklist[$entryFilename]) || $overwrite_entries === FALSE) {
                         $this->addFile(zip_entry_read($entry,zip_entry_filesize($entry)),$entryFilename);
                         if(isset($worklist[$entryFilename])) { 
						    unset($worklist[$entryFilename]); 
						 }
                      }
                      zip_entry_close($entry);
                   }
                }
             zip_close($zh);
            }
         }

         foreach($worklist as $entryFilename => $serverFilename) {
            $this->addFile(file_get_contents($serverFilename),$entryFilename);
         }
  
         $archive = $this->file();
         $fh = @fopen($zipfile,'wb');
         if($fh) {
            fwrite($fh,$archive,strlen($archive));
            fclose($fh);
            $result = $zipfile;
         } else {
            $result = FALSE;
         }
     }
  } else {
      $result = FALSE;
  }
  return $result;
  }

 // =================== Zip file creation class (uses zLib) ===================

    var $datasec      = array();
    var $ctrl_dir     = array();
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    var $old_offset   = 0;

    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method


    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    function addFile($data, $name, $time = 0)
    {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data,9);
        $c_len   = strlen($zdata);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $fr      .= pack('V', $crc);            // crc32
        $fr      .= pack('V', $c_len);          // compressed filesize
        $fr      .= pack('V', $unc_len);        // uncompressed filesize
        $fr      .= pack('v', strlen($name));   // length of filename
        $fr      .= pack('v', 0);               // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;
        $new_offset        = strlen(implode('', $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method


    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    function file()
    {
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    } // end of the 'file()' method

} // end of the 'ZIParchive' class

// ---------------------------------------------------------------------------

function unify_path($path,$dos = FALSE) {
   return ($dos)?str_replace('/','\\',$path):str_replace('\\','/',$path);
}

function filename($file,$nosuffix=TRUE) {
 
   $filename = realBasename($file);
   if($nosuffix) {
      $extPos=strrpos($filename,'.');
      if($extPos && $extPos<strlen($filename)-1) { 
	     $filename=substr($filename,0,$extPos); 
	  }
   }
   return $filename;
}

function realDirname($path,$part = -1,$last_is_file = TRUE) {
   $result = FALSE;
   $path = unify_path($path);
   $folder = explode('/',$path);
   if($last_is_file) {
      $filename=array_pop($folder);
      $path=implode('/',$folder);
   }
   if((strlen($path)>0 && $path{0}=='/') || (strlen($path)>1 && $path{1}==':')) { 
      array_shift($folder); 
   }
   if($part > 0) {
      $part -= 1;
   } elseif($part < 0) {
      $part = sizeof($folder)+($part-((is_path($path))?1:0));
   } else {
      $result = sizeof($folder)-((is_path($path))?1:0);
   }
   if($result === FALSE) { 
      $result=(!empty($folder[$part]))?$folder[$part]:FALSE; 
   }
   return $result;
}

function realBasename($file) {
   return (is_path($file))?'':basename($file);
}

function is_path($file) {
   $lastChar = substr($file,-1);
   return ($lastChar=='/' || $lastChar=='\\');
}

function filelist($path,$base='',$condition='',$recursive = FALSE,$headingslash = TRUE) {
 $files=array();
 $handle=opendir($base.$path);
 if($handle) {
    while($entry=readdir($handle)) {
       $filename=$path.'/'.$entry;
       if($entry == '.' || $entry == '..') { 
	      continue; 
	   }
       if(is_dir($base.$filename)) {
          if($recursive) {
             $subdir = filelist($filename,$base,$condition,$recursive,$headingslash);
             if($subdir && sizeof($subdir)) {
                $files=array_merge($files,$subdir);
             }
             unset($subdir);
          }
       } else {
          array_push($files,(($headingslash)?$filename:substr($filename,1)));
       }
    }
    closedir($handle);
 }
 if(!sizeof($files)) { 
    $files = FALSE; 
 }
 return $files;
}


function abs_path(&$path) {
   if($path==='') { 
      $path='.'; 
   }
   $path=unify_path($path);
  
   if((strlen($path)>0 && $path{0}!='/') && !(strlen($path)>1 && $path{1}==':') ) { 
      $curdir=getcwd();
      if($curdir) {
         $path=$curdir.'/'.$path;
      }
   }
   $folder=explode('/',unify_path($path));
   for($i=0;$i<count($folder);$i++) {
      if($folder[$i]=='.' || $folder[$i]=='..') {
         if($folder[$i]=='..') { 
		    unset($folder[$i]); 
			$i--; 
         }
         unset($folder[$i]); 
		 $i--;
         $folder = array_slice($folder,0,count($folder));
      }
      if($i<0) { 
	     return FALSE; 
	  }
   }
  
  $path = implode('/',$folder);
  return TRUE;
}

function getAbsPath($path) {
 if(!abs_path($path)) { 
    $path=''; 
 }
 return $path;
}

function isWritable($file) {
 return (file_exists($file))?is_writable($file):(file_exists(dirname($file))?is_writable(dirname($file)):FALSE);
}

?>