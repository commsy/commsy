<?php
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Joseacute; Manuel Gonzaacute;lez Vaacute;zquez
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
include_once('functions/development_functions.php');
set_time_limit(0);

if ( !isset($environment)
     and isset($this->_environment)
   ) {
   $environment = $this->_environment;
}

function listfilenames ($dir, $pos=2,$fileitem,$environment,$namearray) {
   $handle = @opendir($dir);
   while ( $file = @readdir($handle) ) {
      if ( preg_match("~^\.{1,2}$~u", $file) ) {
         continue;
      }
      $newfilename = mb_strtolower($file, 'UTF-8');
      if ( file_exists($dir.$file) ) {
         @rename($dir.$file,$dir.$newfilename);
      }
      $oldfilename = $file;
      $file = $newfilename;
      if ( is_dir($dir.$file) ) {
         $namearray = listfilenames($dir.$file."/", $pos + 3,$fileitem,$environment,$namearray);
      } else {
         $extension = mb_strtolower(mb_substr(strrchr($dir.$file,"."),1), 'UTF-8');
         if ( is_file($dir.$file) ) {
            $namearray['oldfilename'][] = $oldfilename;
            $namearray['filename'][] = $file;
            $namearray['dirname'][] = $dir;
         }
      }
   }
   @closedir($handle);
   return $namearray;
}

function replace_files ($dir, $pos=2,$fileitem,$environment,$namearray) {
   $handle = @opendir($dir);
   while ( $file = @readdir($handle) ) {
      if ( preg_match("~^\.{1,2}$~u", $file) ) {
         continue;
      }

      if ( is_dir($dir.$file) ) {
         replace_files($dir.$file."/", $pos + 3,$fileitem,$environment,$namearray);
      } else {
         $extension = mb_strtolower(mb_substr(strrchr($dir.$file,"."),1), 'UTF-8');
         if ( is_file($dir.$file) and ( $extension == "htm"
                                        or $extension == "html"
                                        or $extension == "js"
                                        or $extension == "xml"
                                        or $extension == "xslt"
                                        or $extension == "xsd"
                                        #or $extension == "css"
                                      )
            ) {
            $replacement = replacement($environment,$fileitem,$dir,$file,$namearray);
            $open = fopen($dir.$file,'w');
            fputs($open,$replacement);
            fclose($open);
            update_progress_bar(count_for_process($namearray));
         }
      }
   }
   @closedir($handle);
}

function replacement($environment,$file,$pfad,$datei,$namearray) {
   $filecontent = file_get_contents($pfad.$datei);

   #####################
   # utf8
   # ----
   # text must be in utf8
   # if not, we must convert to utf8
   # because preg_match misses targets
   # if text is not in utf8
   #
   #####################

   include_once('functions/text_functions.php');
   $filecontent = cs_utf8_encode2($filecontent);

   #####################
   # utf8
   #####################

   logToFile($pfad.$datei);
   $disc_manager = $environment->getDiscManager();
   $disc_manager->setPortalID($environment->getCurrentPortalID());
   $disc_manager->setContextID($environment->getCurrentContextID());
   $path_to_file = $disc_manager->getFilePath();
   unset($disc_manager);
   $path = $path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/';
   $linkpath = "";
   if ( $path != $pfad ) {
      $linkpath = str_replace($path,'',$pfad);
   }
   foreach ( $namearray['oldfilename'] as $index => $name ) {
      $pattern = "~[\\\./\d\wÄÖÜäöü_-]{0,}".$name."~isu";
      #$pattern = "~".$name."~isu";
      logToFile($pattern);
      #pr($pattern);
      preg_match_all($pattern, $filecontent, $current_treffer);
      #pr($current_treffer[0]);
      foreach ( $current_treffer[0] as $treffer ) {
         $trefferlowercase = mb_strtolower($treffer, 'UTF-8');
         $path_relative = '';
         if ( strlen($trefferlowercase) > (strlen($name)+1)
              and ( strstr($trefferlowercase,'/')
                    or strstr($trefferlowercase,'\\')
                  )
            ) {
            $path_relative = str_replace('\\','/',$treffer);
            $path_relative = str_replace($name,'',$path_relative);
            if ( $path_relative[0] == '.' ) {
               $path_relative = substr($path_relative,1);
            }
            if ( $path_relative[0] == '/'
                 or $path_relative[0] == '\\'
               ) {
               $path_relative = substr($path_relative,1);
            }
         }
         global $c_single_entry_point;
         if ( !isset($index)
              or !isset($namearray['filename'][$index])
            ) {
            $namearray_filename_index = '';
         } else {
            $namearray_filename_index = $namearray['filename'][$index];
         }
         if ( !empty($path_relative) ) {
            if ( !empty($linkpath)
                 and stristr($linkpath,$path_relative)
               ) {
               $path_relative = $linkpath;
            }
            $replacement = $c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=material&fct=showzip_file&iid='.$file->getFileID().'&file='.$path_relative.$namearray_filename_index;
         } else {
            $replacement = $c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=material&fct=showzip_file&iid='.$file->getFileID().'&file='.$linkpath.$namearray_filename_index;
         }
         if ( !mb_stristr($filecontent,$replacement) ) {
            $filecontent = str_replace($treffer, $replacement, $filecontent);
         }
      }
      if ( strstr($filecontent,"'".$name."'") ) {
         $trefferlowercase = mb_strtolower($name, 'UTF-8');
         $treffer = "'".$name."'";
         global $c_single_entry_point;
         if ( !isset($index)
              or !isset($namearray['filename'][$index])
            ) {
            $namearray_filename_index = '';
         } else {
            $namearray_filename_index = $namearray['filename'][$index];
         }
         $replacement = $c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=material&fct=showzip_file&iid='.$file->getFileID().'&file='.$linkpath.$namearray_filename_index;
         $filecontent = str_replace($treffer, "'".$replacement."'", $filecontent);
      }
   }
   return $filecontent;
}

function count_for_process ( $namearray ) {
   $retour = 0;
   if ( !empty($namearray['oldfilename']) ) {
      foreach ( $namearray['oldfilename'] as $name ) {
         $extension = mb_strtolower(mb_substr(strrchr($name,"."),1), 'UTF-8');
         if ( $extension == "htm"
              or $extension == "html"
              or $extension == "js"
              or $extension == "xml"
              or $extension == "xslt"
              or $extension == "xsd"
            ) {
            $retour++;
         }
      }
   }
   return $retour;
}

$zip = new ZipArchive;

$source_file = $file->getDiskFileName();
$disc_manager = $environment->getDiscManager();
$disc_manager->setPortalID($environment->getCurrentPortalID());
$disc_manager->setContextID($environment->getCurrentContextID());
$path_to_file = $disc_manager->getFilePath();
unset($disc_manager);
$target_directory = $path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/';

global $symfonyContainer;
$export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
if ( !isset($export_temp_folder) ) {
   $export_temp_folder = 'var/temp/zip_export';
}
$file->setHasHTML(1);
$res = $zip->open($source_file);
if ( $res === TRUE ) {
   if( $zip->extractTo($export_temp_folder,'index.htm') ) {
      $file->setHasHTML(2);
      $indexfile = "index.htm";
      unlink($export_temp_folder.'/index.htm');
   } elseif ( $zip->extractTo($export_temp_folder,'index.html') ) {
      $file->setHasHTML(2);
      $indexfile = "index.html";
      unlink($export_temp_folder.'/index.html');
   }
   $file->saveHasHTML();
   if($file->getHasHTML() == 2) {
      $zip->extractTo($target_directory);
   }
   $zip->close();
}
unset($zip);
if($file->getHasHTML() == 2) {
   $disc_manager = $environment->getDiscManager();
   $disc_manager->setPortalID($environment->getCurrentPortalID());
   $disc_manager->setContextID($environment->getCurrentContextID());
   $path_to_file = $disc_manager->getFilePath();
   unset($disc_manager);
   $pfad = $path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/';
   $namearray['oldfilename'] = array();
   $namearray['filename'] = array();
   $namearray['dirname'] = array();

   #######################################
   # process-bar 1
   #######################################
   include_once('functions/misc_functions.php');
   echo('<div class="process">'.LF);
   if ( !isset($translator) ) {
      $translator = $environment->getTranslationObject();
   }
   $text_converter = $environment->getTextConverter();
   echo($text_converter->encode(AS_HTML_SHORT,$translator->getMessage('FILE_ZIP_UPLOAD_PROCESS_TITLE',$file->getDisplayName())).BRLF);
   flush();
   #######################################
   # process-bar 1
   #######################################

   $namearray = listfilenames($pfad,2,$file,$environment,$namearray);

   #######################################
   # process-bar 2
   #######################################
   init_progress_bar(count_for_process($namearray),$translator->getMessage('FILE_ZIP_UPLOAD_PROCESS_DESC'));
   #######################################
   # process-bar 2
   #######################################

   replace_files($pfad,2,$file,$environment,$namearray);

   #######################################
   # process-bar 3
   #######################################
   echo('</div>'.LF);
   echo('<style type="text/css">'.LF.'div.process { display: none; }'.LF.'</style>'.LF);
   flush();
   #######################################
   # process-bar 3
   #######################################
}
?>