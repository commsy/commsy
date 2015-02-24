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

function return_bytes ($size_str)
{
    switch (substr ($size_str, -1))
    {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}

// Get the translator object
$translator = $environment->getTranslationObject();

if ( !empty($_GET['iid']) ) {


      $current_context_item = $environment->getCurrentContextItem();
      $current_user_item = $environment->getCurrentUserItem();
      $user_id = $current_user_item->getUserID();
      $is_external_allowed = false;
      $manager = $environment->getLinkItemFileManager();
      $manager->setFileIDLimit($_GET['iid']);
      $manager->select();
      $list = $manager->get();
      if ( isset($list) and  $list->isNotEmpty() ) {
         $item = $list->getFirst();
         $item_id = $item->getLinkedItemID();
         $item_manager = $environment->getItemManager();
         $item_item = $item_manager->getItem($item_id);
	     $item_type = $item_item->getItemType();
         if ($item_type == 'section'){
		    $section_manager = $environment->getSectionManager();
		    $section_item = $section_manager->getItem($item_item->getItemID());
		    $material_id = $section_item->getLinkedItemID();
		    $material_manager = $environment->getMaterialManager();
		    $material_item = $material_manager->getItem($material_id);
            $is_external_allowed = $material_item->mayExternalSee($current_user_item);
		 }elseif ($item_type == 'discarticle'){
		    $discarticle_manager = $environment->getDiscussionArticleManager();
		    $discarticle_item = $discarticle_manager->getItem($item_item->getItemID());
			$discussion_item = $discarticle_item->getLinkedItem();
            $is_external_allowed = $discussion_item->mayExternalSee($current_user_item);
		 }elseif ($item_type == 'step'){
		    $step_manager = $environment->getStepManager();
		    $step_item = $step_manager->getItem($item_item->getItemID());
			$step_item = $step_item->getLinkedItem();
            $is_external_allowed = $step_item->mayExternalSee($current_user_item);
		 }else{
            $is_external_allowed = $item_item->mayExternalSee($current_user_item);
		 }
      }


   $send_file = false;

   // security
   $current_context_item = $environment->getCurrentContextItem();
   $current_user_item = $environment->getCurrentUserItem();
   if ( $current_user_item->isUser() or $is_external_allowed) {
      $send_file = true;
   } elseif ( $current_context_item->isOpenForGuests() ) {
      $send_file = true;
      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->resetLimits();
      $link_item_file_manager->setFileIdLimit($_GET['iid']);
      $link_item_file_manager->select();
      $link_list = $link_item_file_manager->get();
      $link_item = $link_list->getFirst();
      while ( $link_item ) {
         $linked_item = $link_item->getLinkedItem();
         if ( isset($linked_item)
              and $linked_item->isA(CS_MATERIAL_TYPE)
              and !$linked_item->isDeleted()
              and !$linked_item->isPublished()
              and !$current_context_item->isMaterialOpenForGuests()
            ) {
            $send_file = false;
            break;
         }
         unset($linked_item);
         unset($link_item);
         $link_item = $link_list->getNext();
      }
      unset($link_list);
   } elseif ( $current_context_item->isHomepageLinkActive() ) {
      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->resetLimits();
      $link_item_file_manager->setFileIdLimit($_GET['iid']);
      $link_item_file_manager->select();
      $link_list = $link_item_file_manager->get();
      $link_item = $link_list->getFirst();
      while ( $link_item ) {
         $linked_item = $link_item->getLinkedItem();
         if ( isset($linked_item) and $linked_item->isA(CS_HOMEPAGE_TYPE)) {
            $send_file = true;
            break;
         }
         unset($linked_item);
         unset($link_item);
         $link_item = $link_list->getNext();
      }
      unset($link_list);
   }elseif ( $item->mayExternalSee($current_user_item) ) {
      $send_file = true;
   }

   unset($current_context_item);
   unset($current_user_item);

   if ( $send_file ) {
      # File Download
      $file_manager = $environment->getFileManager();
      $file = $file_manager->getItem($_GET['iid']);
      if ( isset($file) ) {

         # logging
         include_once('include/inc_log.php');

         $file->setContextID($environment->getCurrentContextID());
         if ( $file->isOnDisk() ) {
            // old style: problems with large files
            #header('Content-type: '.$file->getMime());
            // der IE kann damit nicht bei https umgehen, alle anderen Browser schon
            // header('Pragma: no-cache');
            #header('Expires: 0');
            #@readfile($file->getDiskFileName());

         	// List of mime-types that should not be forced to be downloaded, but handled by the browser.
         	$no_force_download = array('text/plain',
         	                           'image/jpeg',
         	                           'image/gif',
         	                           'image/png',
         	                           'audio/wav',
         	                           'audio/mpeg',
         	                           'video/mp4',
         	                           'video/x-msvideo',
         	                           'video/quicktime',
         	                           'video/mpeg',
         	                           'application/pdf',
         	                           'application/x-shockwave-flash');

            // see: http://de.php.net/manual/de/function.readfile.php (25.10.2010)
            $realpath = $file->getDiskFileName();
            $mtime = ($mtime = filemtime($realpath)) ? $mtime : gmtime();
            $size = intval(sprintf("%u", filesize($realpath)));
            // Maybe the problem is we are running into PHPs own memory limit, so:
            if (intval($size + 1) > return_bytes(ini_get('memory_limit')) && intval($size * 1.5) <= 1073741824) { //Not higher than 1GB
               ini_set('memory_limit', intval($size * 1.5));
            }
            // Maybe the problem is Apache is trying to compress the output, so:
            global $c_webserver;
            if(isset($c_webserver) and $c_webserver != 'lighttpd'){
            	if(function_exists('apache_setenv')) {
            		@apache_setenv('no-gzip', 1);
            	} else {
            		@ini_set('output_buffering', 'Off');
            	}
            	
            	@ini_set('zlib.output_compression', 'Off');
            }
            // Maybe the client doesn't know what to do with the output so send a bunch of these headers:

            #if(!in_array($file->getMime(), $no_force_download)){
	            header("Content-Type: application/force-download");
	            header('Content-Type: application/octet-stream');
            #}
            
            $add_content_type_header = true;
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Macintosh') !== false) {
               $is_safari = false;
               if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
                  // add content type headers
               } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                  // don't add content type headers
                  $is_safari = true;
               } else {
                  // add content type headers
               }
               if ($is_safari) {
                  if ((strpos($file->getDisplayName(), 'docx') !== false) ||
                      (strpos($file->getDisplayName(), 'dotx') !== false) ||
                      (strpos($file->getDisplayName(), 'xlsx') !== false) ||
                      (strpos($file->getDisplayName(), 'xltx') !== false) ||
                      (strpos($file->getDisplayName(), 'pptx') !== false) ||
                      (strpos($file->getDisplayName(), 'potx') !== false) ||
                      (strpos($file->getDisplayName(), 'ppsx') !== false)) {
                     $add_content_type_header = false;
                  }
               }
            }
            if ($add_content_type_header) {
               header('Content-Type: '.$file->getMime());
            }

            if(!in_array($file->getMime(), $no_force_download)){
	            if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE") != false) {
	               header("Content-Disposition: attachment; filename=\"" . urlencode($file->getDisplayName()) . '"; modification-date="' . date('r', $mtime) . '"');
	            } else {
                  if($environment->getCurrentBrowserVersion() == 11) {
                     // header("Content-Disposition: attachment; filename*=\"" . $file->getDisplayName() . '"; modification-date="' . date('r', $mtime) . '"');
                  } else {
                     header("Content-Disposition: attachment; filename=\"" . $file->getDisplayName() . '"; modification-date="' . date('r', $mtime) . '"');
                  }
	               
	            }
            }

            // Set the length so the browser can set the download timers
            header("Content-Length: " . (string)$size);
            // If it's a large file we don't want the script to timeout, so:
            set_time_limit(300);
            // If it's a large file, readfile might not be able to do it in one go, so:
            $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
            if ($size > $chunksize) {
               $handle = fopen($realpath, 'rb');
               $buffer = '';
               while (!feof($handle)) {
                  $buffer = fread($handle, $chunksize);
                  echo($buffer);
                  flush();
               }
               fclose($handle);
            } else {
               @readfile($realpath);
            }
            // Exit successfully. We could just let the script exit
            // normally at the bottom of the page, but then blank lines
            // after the close of the script code would potentially cause
            // problems after the file download.
            exit();
         } else {
            include_once('functions/error_functions.php');
            trigger_error("material_getfile: File ".$file->getDiskFileName()." does not seem to be on disk
            <br />environment reports context id ".$environment->getCurrentContextID()."
            <br />file item reports room id ".$file->getContextID(), E_USER_ERROR);
         }
         exit();
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('FILE_ERROR_GET_FILE_NOT_EXISTS'));
         $page->add($errorbox);
         $page->setWithoutLeftMenue();
      }
   } else {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('FILE_ERROR_GET_FILE'));
      $page->add($errorbox);
      $page->setWithoutLeftMenue();
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error("material_getfile: Have no valid Item ID", E_USER_ERROR);
}
?>