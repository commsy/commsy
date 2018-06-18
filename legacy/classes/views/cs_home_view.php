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

$this->includeClass(VIEW);
include_once('classes/cs_list.php');
include_once('functions/curl_functions.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_home_view extends cs_view {

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;

   /**
    * bool - is the list shortened?
    */
   var $_is_list_shortened = false;

   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;

   var $_cols = 1;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_title = 'title';
   }

   function _setColspan($count){
      $this->_cols = $count;
   }

   function _getColspan(){
      return $this->_cols;
   }

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    */
    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

    /**
     * set wheather the list is shortened or not
     *
     * @param bool $shortened			is list shortened?
     */
    function setListShortened($shortened = false) {
       if($shortened) {
          $this->_is_list_shortened = true;
       }
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    */
    function getCountAll () {
       return $this->_count_all;
    }

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list){
       $this->_list = $list;
    }

   /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function getList (){
       return $this->_list;
    }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
	  $html = '';

	  // hack for configuration index
	  if ( $this->_environment->getCurrentModule() == 'configuration'
	       and $this->_environment->getCurrentFunction() == 'index'
		 ) {
		 $html .= BRLF;
	  }
	  // hack for home index at portal
	  if ( $this->_environment->getCurrentModule() == 'home'
		   and $this->_environment->getCurrentFunction() == 'index'
		   and $this->_environment->inPortal()
		 ) {
		 $html .= BR.BRLF;
	  }

      $html .= LF.'<!-- BEGIN OF HOME VIEW -->'.LF;

      // Content
      $color = $this->_environment->getCurrentContextItem()->getColorArray();
      $browser = $this->_environment->getCurrentBrowser();
      $style='';
      if (($browser == 'MSIE' or $browser == 'safari') and isset($color['tabs_background'])) {
         $style=' style="border-left:1px solid '.$color['tabs_background'].';"';
      }elseif(isset($color['tabs_background'])){
         $style=' style="border-left:2px solid '.$color['tabs_background'].';"';
      }
      $html .= '<div><table '.$style.' class="list" summary="Layout">'.LF;
      $html .= $this->_getContentAsHTML();
      $html .= '</table></div>'.LF;
      $html .= '<!-- END OF HOME VIEW -->'.LF;
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/error_functions.php');
      trigger_error('Method must be overridden in subclass', E_USER_ERROR);
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML() {
      $i =0;
      $html = '';
      $list = $this->getList();
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<tr class="list"><td class="odd" colspan="'.$this->_getColspan().'">'.$this->_translator->getMessage('COMMON_NO_NEW_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item,$i);
            $i++;
            unset($current_item);
            $current_item = $list->getNext();
         }
         if($this->_is_list_shortened) {
            $html .= $this->_getListShortenedLink();
         }
      }
      unset($list);
      return $html;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML($item) {
      unset($item);
      include_once('functions/error_functions.php');
      trigger_error('Method must be overridden in subclass', E_USER_ERROR);
   }

   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    */
   function _getItemChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         unset($noticed_manager);
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      unset($item);
      unset($current_user);
      return $info_text;
   }

/** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    */
   function _getItemStepChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $step_list = $item->getStepItemList();
         $step_item = $step_list->getFirst();
         $new = false;
         $changed = false;
         $date = "0000-00-00 00:00:00";
         while ( $step_item ) {
            $noticed = $noticed_manager->getLatestNoticed($step_item->getItemID());
            if ( empty($noticed) ) {
               if ($date < $step_item->getModificationDate() ) {
                   $new = true;
                   $changed = false;
                   $date = $step_item->getModificationDate();
               }
            } elseif ( $noticed['read_date'] < $step_item->getModificationDate() ) {
               if ($date < $step_item->getModificationDate() ) {
                   $new = false;
                   $changed = true;
                   $date = $step_item->getModificationDate();
               }
            }
            unset($step_item);
            $step_item = $step_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_STEP').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_STEP').']</span>';
         } else {
            $info_text = '';
         }
         unset($noticed_manager);
         unset($step_list);
      } else {
         $info_text = '';
      }
      unset($item);
      unset($current_user);
      return $info_text;
   }

   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    */
   function _getItemAnnotationChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $annotation_list = $item->getItemAnnotationList();
         $anno_item = $annotation_list->getFirst();
         $new = false;
         $changed = false;
         $date = "0000-00-00 00:00:00";
         while ( $anno_item ) {
            $noticed = $noticed_manager->getLatestNoticed($anno_item->getItemID());
            if ( empty($noticed) ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = true;
                   $changed = false;
                   $date = $anno_item->getModificationDate();
               }
            } elseif ( $noticed['read_date'] < $anno_item->getModificationDate() ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = false;
                   $changed = true;
                   $date = $anno_item->getModificationDate();
               }
            }
            unset($anno_item);
            $anno_item = $annotation_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_ANNOTATION').']</span>';
         } else {
            $info_text = '';
         }
         unset($noticed_manager);
         unset($annotation_list);
      } else {
         $info_text = '';
      }
      unset($item);
      unset($current_user);
      return $info_text;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=TRUE){
      $retour='';
      $file_list='';
      $files = $item->getFileList();
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                   $this->_with_slimbox = true;
                   // jQuery
                   //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   // jQuery
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         unset($file);
         $file = $files->getNext();
      }
      unset($files);
      unset($user);
      return $retour.$file_list;
   }
}
?>