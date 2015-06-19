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

$this->includeClass(MISC_2ZIP);

class misc_item2zip extends misc_2zip {

   private $_environment = NULL;
   private $_item_id = NULL;
   private $_view_mode = 'print';
   private $_zip_mode = true;
   private $_filename = 'index';
   private $_folder_existing = NULL;

   public function __construct ($params) {
      parent::__construct($params);
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
         ini_set('max_execution_time', 120);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
   }

   public function setWithoutZIP () {
      $this->_zip_mode = false;
   }

   public function setFilename2ID () {
      $this->_filename = $this->_item_id;
   }

   public function setFolder ( $value ) {
      if ( is_dir($value) ) {
         $this->_folder_existing = $value;
      }
   }

   public function setItemID ( $value ) {
      $this->_item_id = $value;
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

   public function _getOutput ( $item_id ) {
      $retour = '';
      $item_manager = $this->_environment->getItemManager();
      $item_type = $item_manager->getItemType($item_id);
      if ( !empty($item_type) ) {
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
         include_once('functions/misc_functions.php');
         $detail_page = 'pages/'.type2module($item_type).'_detail.php';
         if ( file_exists($detail_page) ) {
            $session = $this->_environment->getSessionItem();
            $current_user = $this->_environment->getCurrentUserItem();
            $current_context = $this->_environment->getCurrentContextItem();
            $this->_environment->setCurrentParameter('iid',$item_id);
            $_GET['iid'] = $item_id;
            include($detail_page);
            unset($current_user);
            unset($session);
            unset($current_context);
         }

         //Put page into string
         $page->setPrintableView();
         if ( $this->_view_mode == 'print' ) {
            $this->_environment->setCurrentParameter('mode','print');
            $_GET['mode'] = 'print';
            $this->_environment->setCurrentParameter('download','zip');
            $_GET['download'] = 'zip';
         }
         $retour .= $page->asHTMLFirstPart();
         $retour .= $page->asHTMLSecondPart();
         $retour .= $page->asHTML();
      }
      return $retour;
   }

   private function _replaceLinksToFiles ( $retour, $directory ) {
      $reg_exp = '~\<a\s{1}href=\"([^"]*)\"~u';
      preg_match_all($reg_exp, $retour, $matches_array);
      $i = 0;
      $iids = array();
      $thumb_array = array();

      if ( !empty($matches_array[1])
           and !is_dir($directory.'/images')
         ) {
         mkdir($directory.'/images', 0777);
      }

      foreach($matches_array[1] as $match) {
         $new = parse_url($match,PHP_URL_QUERY);
         $out = '';
         parse_str($new,$out);

         $index = '';
         if ( isset($out['amp;iid']) ) {
            $index = $out['amp;iid'];
         } elseif( isset($out['iid']) ) {
            $index = $out['iid'];
         }

         if ( !empty($index)
              and $index == $this->_item_id
              and stristr($match,'#anchor')
            ) {
            $thumb_name = substr($match,strpos($match,'#'));
            $retour = str_replace($match, $thumb_name, $retour);
         } elseif (isset($index) ) {
            $filemanager = $this->_environment->getFileManager();
            $file = $filemanager->getItem($index);
            if ( isset($file) ) {
               $icon = $directory.'/images/'.$file->getIconFilename();
               $filearray[$i] = $file->getDiskFileName();
               if ( file_exists(realpath($file->getDiskFileName())) ) {
                  include_once('functions/text_functions.php');
                  copy($file->getDiskFileName(),$directory.'/'.toggleUmlaut($file->getFilename()));
                  $retour = str_replace($match, toggleUmlaut($file->getFilename()), $retour);
                  copy('htdocs/images/'.$file->getIconFilename(),$icon);

                  // thumbs
                  $thumb_name = $file->getFilename() . '_thumb';
                  $thumb_disk_name = $file->getDiskFileName() . '_thumb';
                  if ( file_exists(realpath($thumb_disk_name)) ) {
                     copy($thumb_disk_name,$directory.'/images/'.$thumb_name);
                     $retour = str_replace($match, $thumb_name, $retour);
                     $thumb_array[basename($thumb_disk_name)] = $thumb_name;
                  }
               }
            }
         }
         $i++;
      }

      // img src
      $matches_array = array();
      $reg_exp = '~src=\"([^"]*)\"~u';
      preg_match_all($reg_exp, $retour, $matches_array);

      $link_list = array();
      if ( !empty($matches_array[1]) ) {
         foreach ( $matches_array[1] as $link ) {
            if ( ( stristr($link,'getFile')
                   and stristr($link,'picture')
                 ) or stristr($link,'images/disc1')
               ) {
               $link_list[] = $link;
            }
         }
      }

      foreach ( $link_list as $link ) {
         $img_name = '';
         if ( stristr($link,'picture=') ) {
            $name = substr($link,strpos($link,'?')+1);
            $name_array = explode('&',$name);
            foreach ( $name_array as $param ) {
               if ( stristr($param,'picture=') ) {
                  $img_name = substr($param,strpos($param,'=')+1);
               }
            }
         } elseif ( stristr($link,'images/disc1') ) {
            $img_name = str_replace('images/','',$link);
         }

         if ( !empty($img_name) ) {
            if ( !empty($thumb_array[$img_name]) ) {
               $retour = str_replace($link,'images/'.$thumb_array[$img_name],$retour);
            } else {
               if ( !file_exists($directory.'/images/'.$img_name) ) {
                  $orig_img_file = '';
                  if ( stristr($link,'picture=') ) {
                     $disc_manager = $this->_environment->getDiscManager();
                     $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
                     $disc_manager->setContextID($this->_environment->getCurrentContextID());
                     $orig_img_file = $disc_manager->getFilePath();
                     unset($disc_manager);
                     $orig_img_file .= $img_name;
                  } elseif ( stristr($link,'images/disc1') ) {
                     $orig_img_file  = 'htdocs/images/';
                     $orig_img_file .= $img_name;
                  }
                  if ( !empty($orig_img_file)
                       and file_exists($orig_img_file)
                     ) {
                     copy($orig_img_file,$directory.'/images/'.$img_name);
                  }
               }
               $retour = str_replace($link,'images/'.$img_name,$retour);
            }
         }
      }

      return $retour;
   }

   public function _copyCSS ($folder) {
      // CSS
      $csstarget = $folder.'/stylesheet.css';
      $csssrc = 'htdocs/commsy_print_css.php';

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
      // create ZIP File
      global $symfonyContainer;
      $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
      
      if ( !isset($export_temp_folder) ) {
         $export_temp_folder = 'var/temp/zip_export';
      }
      $item_manager = $this->_environment->getItemManager();
      $item_type = $item_manager->getItemType($this->_item_id);
      if ( isset($this->_item_id) ) {
         $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$item_type.'_'.$this->_item_id.'.zip';
      } else {
         $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$this->_environment->getCurrentModule().'_'.$this->_environment->getCurrentFunction().'.zip';
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
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNNG);
      }
   }

   public function execute () {
      if ( !empty($this->_folder_existing) ) {
         $folder = $this->_folder_existing;
      } else {
         $folder = $this->_makeTempFolder();
      }
      if ( $folder ) {
         // get HTML output
         $output = $this->_getOutput($this->_item_id);

         //String replacements
         $output = str_replace('commsy_print_css.php?cid='.$this->_environment->getCurrentContextID(),'stylesheet.css', $output);
         $output = $this->_replaceLinksToFiles($output,$folder);

         //create HTML-File
         $filename = $folder.'/'.$this->_filename.'.html';
         $handle = fopen($filename, 'a');
         fwrite($handle, $output);
         fclose($handle);
         unset($output);

         // CSS
         $this->_copyCSS($folder);

         // create ZIP File
         if ( $this->_zip_mode ) {
            $this->_createZIP($folder);
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not make temp folder - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
   }
}
?>