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

class misc_list2zip extends misc_2zip {

   private $_environment = NULL;
   private $_item_id_array = NULL;
   private $_module = '';
   private $_view_mode = 'print';
   private $_zip_filename_with_folder = '';

   public function __construct ($params) {
      parent::__construct($params);
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
   }

   public function setItemIDArray ( $value ) {
      $this->_item_id_array = $value;
   }

   public function setModule ( $value ) {
      $this->_module = $value;
   }

   private function _getOutput ( $array ) {
      $retour = '';
      if ( !empty($array) ) {
         $page = $this->_getPageObject();
         $only_show_array = $array;
         include_once('functions/misc_functions.php');
         $index_page = 'pages/'.$this->_module.'_index.php';
         if ( file_exists($index_page) ) {
            $environment = $this->_environment;
            $class_factory = $this->_environment->getClassFactory();
            $session = $this->_environment->getSessionItem();
            #$current_user = $this->_environment->getCurrentUserItem();
            #$current_context = $this->_environment->getCurrentContextItem();
            unset($_POST);
            include($index_page);
            unset($environment);
            unset($class_factory);
            unset($session);
            #unset($current_user);
            #unset($current_context);
         }

         //Put page into string
         $page->setPrintableView();
         if ( $this->_view_mode == 'print' ) {
            $this->_environment->setCurrentParameter('mode','print');
            $this->_environment->setCurrentParameter('download','zip');
            $_GET['mode'] = 'print';
            $_GET['download'] = 'zip';
         }
         $retour .= $page->asHTMLFirstPart();
         $retour .= $page->asHTMLSecondPart();
         $retour .= $page->asHTML();
      }
      return $retour;
   }

   private function _saveDetailPages ( $id_array, $folder ) {
      $retour = true;
      if ( !empty($id_array) ) {
         $class_factory = $this->_environment->getClassFactory();
         foreach ( $id_array as $id ) {
            $item2zip = $class_factory->getClass(MISC_ITEM2ZIP,array('environment' => $this->_environment));
            $item2zip->setItemID($id);
            $item2zip->setFolder($folder);
            $item2zip->setWithoutZip();
            $item2zip->setFilename2ID();
            $item2zip->execute();
         }
         unset($class_factory);
      }
      return $retour;
   }

   public function execute () {
      $folder = $this->_makeTempFolder();
      if ( $folder ) {
         // get HTML output
         $output = $this->_getOutput($this->_item_id_array);

         //String replacements
         $output = str_replace('commsy_print_css.php?cid='.$this->_environment->getCurrentContextID(),'stylesheet.css', $output);

         // now detail pages
         $this->_saveDetailPages($this->_item_id_array,$folder);

         // links on index page
         $output = $this->_replaceLinksToFiles($output,$folder);

         //create HTML-File
         $filename = $folder.'/index.html';
         $handle = fopen($filename, 'a');
         fwrite($handle, $output);
         fclose($handle);
         unset($output);

         // CSS
         $this->_copyCSS($folder);

         // create ZIP File
         $this->_zip_filename_with_folder = $this->_createZIP($folder);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not make temp folder - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
   }

   public function _replaceLinksToFiles ( $retour, $directory ) {
      $reg_exp = '~\<a\s{1}href=\"([^"]*)\"~u';
      preg_match_all($reg_exp, $retour, $matches_array);
      $link_list = array();
      if ( !empty($matches_array[1]) ) {
         foreach ( $matches_array[1] as $link ) {
            if ( stristr($link,'detail')
                 or stristr($link,'getFile')
               ) {
               $link_list[] = $link;
            }
         }
      }

      $iids = array();

      if ( !empty($matches_array[1]) ) {
         if ( !is_dir($directory.'/images') ) {
            mkdir($directory.'/images', 0777);
         }
      }

      foreach ( $link_list as $link ) {
         if ( stristr($link,'getFile') ) {
            $name = str_replace('commsy.php/','',$link);
            $name = substr($name,0,strpos($name,'?'));
            include_once('functions/text_functions.php');
            $name = toggleUmlaut($name);
            $retour = str_replace($link,$name,$retour);
         } else {
            $reg_exp = '~iid=([0-9]*)~u';
            $iid_array = array();
            preg_match_all($reg_exp, $link, $iid_array);
            if ( !empty($iid_array[1][0]) ) {
               $retour = str_replace($link,$iid_array[1][0].'.html',$retour);
            }
         }
      }

       // img src
      $matches_array = array();
      $reg_exp = '~src=\"([^"]*)\"~u';
      preg_match_all($reg_exp, $retour, $matches_array);
      $link_list = array();
      if ( !empty($matches_array[1]) ) {
         foreach ( $matches_array[1] as $link ) {
            if ( stristr($link,'getFile')
                 and stristr($link,'picture')
               ) {
               $link_list[] = $link;
            }
         }
      }

      foreach ( $link_list as $link ) {
         $img_name = '';
         $name = substr($link,strpos($link,'?')+1);
         $name_array = explode('&',$name);
         foreach ( $name_array as $param ) {
            if ( stristr($param,'picture=') ) {
               $img_name = substr($param,strpos($param,'=')+1);
            }
         }

         if ( !empty($img_name) ) {
            if ( !file_exists($directory.'/images'.$img_name) ) {
               $disc_manager = $this->_environment->getDiscManager();
               $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
               $disc_manager->setContextID($this->_environment->getCurrentContextID());
               $orig_img_file = $disc_manager->getFilePath();
               unset($disc_manager);
               $orig_img_file .= $img_name;
               if ( file_exists($orig_img_file) ) {
                  copy($orig_img_file,$directory.'/images/'.$img_name);
               }
            }
            $retour = str_replace($link,'images/'.$img_name,$retour);
         }
      }

      return $retour;
   }

   public function getZipFilenameWithFolder () {
      return $this->_zip_filename_with_folder;
   }

   public function send () {
      header('Content-type: application/zip');
      header('Content-Disposition: attachment; filename="'.basename($this->_zip_filename_with_folder).'"');
      readfile($this->_zip_filename_with_folder);
      exit();
   }
}
?>