<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

class misc_2zip {

   private $_environment = NULL;
   private $_view_mode = 'print';

   public function __construct ($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
   }

   public function _makeTempFolder () {
      $retour = false;

      global $symfonyContainer;
      $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');

      if ( !isset($export_temp_folder) ) {
         $export_temp_folder = 'var/temp/zip_export';
      }

      $folder_name = time();
      $session_item = $this->_environment->getSessionItem();
      if ( isset($session_item) ) {
         $folder_name .= $session_item->getSessionID();
      }
      $folder_name = md5($folder_name);

      $directory = './'.$export_temp_folder.'/'.$folder_name;
      $disc_manager = $this->_environment->getDiscManager();
      if ($disc_manager->makeDirectoryR($directory)) {
         $retour = $directory;
      }
      return $retour;
   }

   public function _getCSS ( $file, $file_url ) {
      $out = fopen($file,'wb');
      if ( $out == false ) {
         include_once('functions/error_functions.php');
         trigger_error('can not open destination file. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      if ( function_exists('curl_init') ) {
         $ch = curl_init();
         curl_setopt($ch,CURLOPT_FILE,$out);
         curl_setopt($ch,CURLOPT_HEADER,0);
         curl_setopt($ch,CURLOPT_URL,$file_url);
         curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
         curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

         global $symfonyContainer;
         $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
         $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');
         
         if ( !empty($c_proxy_ip) ) {
            $proxy = $c_proxy_ip;
            if ( !empty($c_proxy_port) ) {
               $proxy = $c_proxy_ip.':'.$c_proxy_port;
            }
            curl_setopt($ch,CURLOPT_PROXY,$proxy);
         }
         curl_exec($ch);
         $error = curl_error($ch);
         if ( !empty($error) ) {
            include_once('functions/error_functions.php');
            trigger_error('curl error: '.$error.' - '.$file_url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
         }
         curl_close($ch);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      fclose($out);
   }

   public function _copyCSS ($folder) {
      // CSS
      $csstarget = $folder.'/stylesheet.css';
      $csssrc = 'htdocs/commsy_print_css.php';

      // commsy 7
      $current_context = $this->_environment->getCurrentContextItem();
      if ( !is_dir($folder.'/css') ) {
         mkdir($folder.'/css', 0777);
      }

      global $c_commsy_domain;
      global $c_commsy_url_path;
      $params = $this->_environment->getCurrentParameterArray();
      $url_to_style = $c_commsy_domain.$c_commsy_url_path.'/css/commsy_print_css.php?cid='.$this->_environment->getCurrentContextID();
      $this->_getCSS($folder.'/css/stylesheet.css',$url_to_style);
      unset($url_to_style);
   }

   public function _createZIP ($folder) {
      $retour = '';

      // create ZIP File
      global $symfonyContainer;
      $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');

      if ( !isset($export_temp_folder) ) {
         $export_temp_folder = 'var/temp/zip_export';
      }
      if ( isset($this->_item_id) ) {
         $item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($this->_item_id);
         $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$item_type.'_'.$this->_item_id.'.zip';
      } else {
      	$translator = $this->_environment->getTranslationObject();
      	if($this->_environment->getCurrentModule() == 'announcement'){
      	   $current_module = $translator->getMessage('ANNOUNCEMENT_EXPORT_ZIP');
      	} elseif($this->_environment->getCurrentModule() == 'material'){
            $current_module = $translator->getMessage('MATERIAL_EXPORT_ZIP');
         } elseif($this->_environment->getCurrentModule() == 'date'){
            $current_module = $translator->getMessage('DATE_EXPORT_ZIP');
         } elseif($this->_environment->getCurrentModule() == 'discussion'){
            $current_module = $translator->getMessage('DISCUSSION_EXPORT_ZIP');
         } elseif($this->_environment->getCurrentModule() == 'todo'){
            $current_module = $translator->getMessage('TODO_EXPORT_ZIP');
         } else {
      		$current_module = $this->_environment->getCurrentModule();
      	}
         
         $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$current_module.'_'.$this->_environment->getCurrentFunction().'_'.$this->_environment->getCurrentContextID().'_'.time().'.zip';
      }
      if ( file_exists(realpath($zipfile)) ) {
         unlink($zipfile);
      }

      if ( class_exists('ZipArchive') ) {
         include_once('functions/misc_functions.php');
         $zip = new ZipArchive();
         $filename = $zipfile;

         if ( $zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE ) {
            include_once('functions/error_functions.php');
            trigger_error('can not open zip-file '.$filename,E_USER_WARNNG);
         }
         $temp_dir = getcwd();
         chdir($folder);

         $zip = addFolderToZip('.',$zip);
         chdir($temp_dir);

         $zip->close();
         unset($zip);

         if ( empty($this->_folder_existing) ) {
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->removeDirectory($folder);
         }

         $retour = $filename;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNNG);
      }

      return $retour;
   }

   public function _getPageObject () {
      $retour = NULL;
      $class_factory = $this->_environment->getClassFactory();
      $params = array();
      $params['environment'] = $this->_environment;
      // only room now
      $page = $class_factory->getClass(PAGE_ROOM_VIEW,$params);
      // only room now
      unset($params);

      // title
      if ( $this->_environment->isOutputModeNot('XML') ) {
         $environment = $this->_environment;
         $page->setCurrentUser($this->_environment->getCurrentUserItem());

         // set title
         $context_item_current = $this->_environment->getCurrentContextItem();
         $translator = $this->_environment->getTranslationObject();
         $title = $context_item_current->getTitle();
         if ($context_item_current->isProjectRoom() and $context_item_current->isTemplate()) {
            $title .= ' ('.$translator->getMessage('PROJECTROOM_TEMPLATE').')';
         } elseif ($context_item_current->isClosed()) {
            $title .= ' ('.$translator->getMessage('PROJECTROOM_CLOSED').')';
         }

         $user = $this->_environment->getCurrentUserItem();
         if ( $context_item_current->isPrivateRoom() and $user->isGuest() ) {
            $page->setRoomName($translator->getMessage('COMMON_FOREIGN_ROOM'));
            $page->setPageName($translator->getMessage('COMMON_FOREIGN_ROOM'));
         } elseif ( $context_item_current->isPrivateRoom() ) {
            $page->setRoomName($translator->getMessage('COMMON_PRIVATEROOM'));
            $tempModule = mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8');
            $tempMessage = "";
            include_once('include/inc_commsy_php_case_pagetitle.php');
            $page->setPageName($tempMessage);
         } else {
            $page->setRoomName($title);
            $tempModule = mb_strtoupper($environment->getCurrentModule(), 'UTF-8');
            $tempMessage = "";
            include_once('include/inc_commsy_php_case_pagetitle.php');
            $page->setPageName($tempMessage);
         }
      }

      if ( !empty($page) ) {
         $retour = $page;
         unset($page);
      }
      return $retour;
   }
}
?>