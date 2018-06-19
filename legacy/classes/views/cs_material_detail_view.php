<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy material detail-views
 */
class cs_material_detail_view extends cs_detail_view {

   var $_section_list=NULL;
   var $_version_list=NULL;
   var $_version_id=NULL;
   var $_sub_item_pos_number = NULL;

   /** array of ids in clipboard*/
   var $_clipboard_id_array=array();


   /** constructor: cs_material_detail_view
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_detail_view::__construct($params);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function _getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   /** set the content of the list view
    * this method sets the item to be displayed
    * @param cs_item  $material     cs_material_item to display
    * @param integer  version_id    version of the item to display
    *
    * @author CommSy Development Group
    */
   function setVersionList($version_list, $version_id = NULL){
      $this->_version_list = $version_list;

      // Get the version to display
      if ( is_null($version_id) ) {
         $this->_item = $version_list->getFirst();
      } else {
         $version = $version_list->getFirst();
         while ( $version and
                 $version_id != $version->getVersionID() ) {
            $version = $version_list->getNext();
         }
         if ( $version and
              $version_id == $version->getVersionID() ) {
            $this->_version_id = $version_id;
            $this->_item = $version;
         } else {
            $this->_item = $version_list->getFirst();
         }
      }
      // Set subitems
      $section_list = $this->_item->getSectionList();
      if ( !$section_list->isEmpty() ) {
         $this->setSubItemList($section_list);
      }
   }


   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param cs_item  $item      CommSy item to display
    *
    * @author CommSy Development Group
    */
   function setItem ($item){
      include_once('functions/error_functions.php');trigger_error("setItem called, but setVersionList must be called for material_detail_view", E_USER_ERROR);
   }


   function _getDetailItemActionsAsHTML($item){
    $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // edit
      $html .= $this->_getEditAction($item,$current_user);

      if ( $item->mayEdit($current_user) and $this->_with_modifying_actions ) {
         $params['iid'] = 'NEW';
         $params['ref_iid'] = $item->getItemID();
         $params['ref_vid'] = $item->getVersionID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                   'section',
                                   'edit',
                                   $params,
                                   $image,$this->_translator->getMessage('MATERIAL_SECTION_ADD')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('MATERIAL_SECTION_ADD')).' "class="disabled">'.$image.'</a>'.LF;
      }

      // delete
      $html .= $this->_getDeleteAction($item,$current_user);

      return $html.'&nbsp;&nbsp;&nbsp;';
   }


   function _getAdditionalActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // wiki
      $html .= $this->_getWikiAction($item,$current_user,$current_context);
      // wordpress
      $html .= $this->_getWordpressAction($item,$current_user,$current_context);

      //workflow
      $html .= $this->_getWorkflowReadAction($item,$current_user,$current_context);

      return $html;
   }

   function _getBrowsingIconsAsHTML($current_item, $pos_number, $count){
      $html ='';
      $i =0;
      if ( $pos_number == 1 ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" border="0"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" border="0"/>';
         }
         $html .= '<a href="#top">'.$image.'</a>'.LF;
      }elseif ( $pos_number > 1 ) {
         $i = $pos_number-1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" border="0"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" border="0"/>';
         }
         $html .= '<a href="#anchor'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '<span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_left_grey2.gif" alt="&lt;" border="0"/></span>'.LF;
         } else {
            $html .= '<span class="disabled"><img src="images/commsyicons/16x16/browse_left_grey2.png" alt="&lt;" border="0"/></span>'.LF;
         }
      }
      if ( $pos_number < $count) {
         $i = $pos_number+1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_right2.gif" alt="&gt;" border="0"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_right2.png" alt="&gt;" border="0"/>';
         }
         $html .= '<a href="#anchor'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '<span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_right_grey2.gif" alt="&gt;" border="0"/></span>'.LF;
         } else {
            $html .= '<span class="disabled"><img src="images/commsyicons/16x16/browse_right_grey2.png" alt="&gt;" border="0"/></span>'.LF;
         }
      }
      return $html;
   }


   function _getSubItemDetailActionsAsHTML ($subitem) {
      $user = $this->_environment->getCurrentUserItem();
      $item = $this->getItem();
      $creator = $subitem->getCreatorItem();
      if ( isset($creator) ) {
         $creator_uid = $creator->getUserID();
      } else {
         $creator_uid = '';
      }
      $current_uid = $user->getUserID();
      //get Version ID of current version
      $material_manager = $this->_environment->getMaterialManager();
      //Receives current material by default
      $current_material = $material_manager->getItem($item->getItemId());
      $version_of_current_material = $current_material->getVersionID();
      $html = '';
      if ( $version_of_current_material == $subitem->getVersionID() and // showing the current version
         $item->mayEdit($user) and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $subitem->getItemID();
         $params['ref_vid'] = $item->getVersionID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'section',
                                    'edit',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_SECTION_EDIT')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_SECTION_EDIT').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_SECTION_EDIT').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_SECTION_EDIT')).' "class="disabled">'.$image.'</a>'.LF;
      }
      if ( $item->mayEdit($user) and $this->_with_modifying_actions ) {
         $params['iid'] = 'NEW';
         $params['ref_iid'] = $item->getItemID();
         $params['ref_vid'] = $item->getVersionID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                   'section',
                                   'edit',
                                   $params,
                                   $image,$this->_translator->getMessage('MATERIAL_SECTION_ADD')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('MATERIAL_SECTION_ADD')).' "class="disabled">'.$image.'</a>'.LF;
      }
      if ( $version_of_current_material == $subitem->getVersionID() and // showing the current version
           $item->mayEdit($user) and $this->_with_modifying_actions ) {
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         $params['section_iid'] = $subitem->getItemID();
         $params['iid'] = $item->getItemID();
         $params['ref_vid'] = $item->getVersionID();
         $params['section_action'] = 'delete';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'detail',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('SECTION_DELETE'),
                                          '',
                                          'anchor'.$subitem->getItemID()).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW').' "class="disabled">'.$image.'</a>'.LF;
      }
/*      if ( $version_of_current_material == $subitem->getVersionID() and // showing the current version
           $item->mayEdit($user) and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         $params['ref_iid'] = $item->getItemID();
         $params['ref_vid'] = $item->getVersionID();
         $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         $actionCurl = ahref_curl( $this->_environment->getCurrentContextID(),
                                   'section',
                                   'edit',
                                   $params,
                                   $image,
                                   $this->_translator->getMessage('MATERIAL_SECTION_ADD'));
         $html .= $actionCurl.LF;
         unset($params);
      } else {
         $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_SECTION_ADD').'"/>';
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('MATERIAL_SECTION_ADD')).' "class="disabled">'.$image.'</a>'.LF;
      }*/
      return $html;
   }


   function _getPluginInfosForMaterialDetailAsHTML () {
      $html = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getMaterialDetailAsHTML')) {
               $retour = $plugin_class->getMaterialDetailAsHTML();
               if (isset($retour)) {
                  $html .= $retour;
               }
            }
         }
      }
      return $html;
   }


   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param cs_material_item material     the single list entry
    */
   function _getItemAsHTML($item, $version_id=NULL, $with_links=TRUE) {
      $context_item = $this->_environment->getCurrentContextItem();
      $html = LF.'<!-- BEGIN OF MATERIAL ITEM DETAIL -->'.LF;

      $html .= $this->_getPluginInfosForMaterialDetailAsHTML();

      $formal_data1 = array();
      $bib_kind = $item->getBibKind() ? $item->getBibKind() : 'none';
      $biblio ='';
      // Author, Year
      $temp_array = array();
/*      $author = $item->getAuthor();
      if ($bib_kind =='none' and !empty($author)){
         $temp_array[0]  = $this->_translator->getMessage('MATERIAL_AUTHORS');
         $temp_array[1]  = $this->_text_as_html_short($item->getAuthor());
         $formal_data1[] = $temp_array;
         $temp_array = array();
         $old_bibtext = $item->getBibliographicValues();
         if( !empty($old_bibtext) ){
            $temp_array[0]  = $this->_translator->getMessage('MATERIAL_PUBLISHING_DATE');
            $temp_array[1]  = $this->_text_as_html_long($item->getPublishingDate());
            $formal_data1[] = $temp_array;
            $temp_array = array();
            $temp_array[0]  = $this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC');
            $temp_array[1]  = $this->_text_as_html_long($item->getBibliographicValues());
            $formal_data1[] = $temp_array;
            $temp_array = array();
         }
      }else
      */
      if($bib_kind =='common'){
         $author = $item->getAuthor();
         $formal_data1 = array();
         if (!empty($author)){
            $temp_array[0]  = $this->_translator->getMessage('MATERIAL_AUTHORS');
            $temp_array[1]  = $this->_text_as_html_short($this->_compareWithSearchText($item->getAuthor()));
            $formal_data1[] = $temp_array;
         }
         $pub_date = $item->getPublishingDate();
         if (!empty($pub_date)){
            $temp_array[0]  = $this->_translator->getMessage('MATERIAL_PUBLISHING_DATE');
            $temp_array[1]  = $this->_text_as_html_short($this->_compareWithSearchText($item->getPublishingDate()));
            $formal_data1[] = $temp_array;
         }
         if ( !empty($formal_data1) ) {
            $html .= $this->_getFormalDataAsHTML($formal_data1);
            if ( isset($html_script) and !empty($html_script) ) {
               $html .= $html_script;
            }
         }
         $formal_data1 = array();
         $temp_array = array();
         $biblio = $item->getBibliographicValues();
      }elseif($bib_kind =='website'){
         $biblio = $item->getAuthor().',';
      }
      /** Start Dokumentenverwaltung **/
      elseif($bib_kind =='document'){
         $biblio = '';
      }
      /** Ende Dokumentenverwaltung **/

      else{
         $biblio = $item->getAuthor().' ('.$item->getPublishingDate().'). ';
      }
      if($bib_kind !='common'){
         // Bibliographic
         switch ( $bib_kind ) {
            case 'book':
            case 'collection':
               $biblio .= $item->getAddress().': '.$item->getPublisher();
               if ( $item->getEdition() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_EDITION', $item->getEdition());
               }
               if ( $item->getSeries() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_SERIES', $item->getSeries());
               }
               if ( $item->getVolume() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_VOLUME', $item->getVolume());
               }
               if ( $item->getISBN() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_ISBN', $item->getISBN());
               }
               $biblio .= '.';
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
              if( $item->getURLDate() ) {
              $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
              }
              $biblio .= '.';
               }
               break;
            case 'incollection':
               $editor = $item->getEditor();
               if ( !empty($editor) ) {
                  $biblio .= $this->_translator->getMessage('MATERIAL_BIB_IN').': ';
                  $biblio .= $this->_translator->getMessage('MATERIAL_BIB_EDITOR', $item->getEditor()).': ';
               }
               $biblio .= $item->getBooktitle().'. ';
               $biblio .= $item->getAddress().': '.$item->getPublisher();
               if ( $item->getEdition() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_EDITION', $item->getEdition());
               }
               if ( $item->getSeries() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_SERIES', $item->getSeries());
               }
               if ( $item->getVolume() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_VOLUME', $item->getVolume());
               }
               if ( $item->getISBN() ) {
                  $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_ISBN', $item->getISBN());
               }
               $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'.';
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
              if( $item->getURLDate() ) {
              $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
              }
              $biblio .= '.';
               }
               break;
            case 'article':
               $biblio .= $this->_translator->getMessage('MATERIAL_BIB_IN').': '.
                      $item->getJournal();
               if ( $item->getVolume() ) {
                  $biblio .= ', '.$item->getVolume();
                  if ( $item->getIssue() ) {
                     $biblio .= ' ('.$item->getIssue().')';
                  }
               } elseif ( $item->getIssue() ) {
                  $biblio .= ', '.$item->getIssue();
               }
               $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'. ';

               $bib2 = '';
               if ( $item->getAddress() ) {
                  $bib2 .= $item->getAddress();
               }
               if ( $item->getPublisher() ) {
                  $bib2 .= $bib2 ? ', ' : '';
                  $bib2 .= $item->getPublisher();
               }
               if ( $item->getISSN() ) {
                  $bib2 .= $bib2 ? ', ' : '';
                  $bib2 .= $item->getISSN();
               }
               $bib2 .= $bib2 ? '. ' : '';

               $biblio .= $bib2 ? $bib2 : '';
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
        if( $item->getURLDate() ) {
           $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
        }
        $biblio .= '.';
               }
               break;
            case 'inpaper':
               $biblio .= $this->_translator->getMessage('MATERIAL_BIB_IN').': '.
                      $item->getJournal();
               if ( $item->getIssue() ) {
                  $biblio .= ', '.$item->getIssue();
               }
               $biblio .= ', '.$this->_translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'. ';

               $bib2 = '';
               if ( $item->getAddress() ) {
                  $bib2 .= $item->getAddress();
               }
               if ( $item->getPublisher() ) {
                  $bib2 .= $bib2 ? ', ' : '';
                  $bib2 .= $item->getPublisher();
               }
               $bib2 .= $bib2 ? '. ' : '';

               $biblio .= $bib2 ? $bib2 : '';
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
        if( $item->getURLDate() ) {
           $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
             }
             $biblio .= '.';
               }
               break;
            case 'thesis':
               {
                  $temp_Thesis_Kind = mb_strtoupper($item->getThesisKind(), 'UTF-8');
                  switch ( $temp_Thesis_Kind )
                  {
                     case 'BACHELOR':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_BACHELOR').'. ';
                        break;
                     case 'DIPLOMA':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_DIPLOMA').'. ';
                        break;
                     case 'DISSERTATION':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_DISSERTATION').'. ';
                        break;
                     case 'EXAM':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_EXAM').'. ';
                        break;
                     case 'KIND':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_KIND').'. ';
                        break;
                     case 'KIND_DESC':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_KIND_DESC').'. ';
                        break;
                     case 'MASTER':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_MASTER').'. ';
                        break;
                     case 'OTHER':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_OTHER').'. ';
                        break;
                     case 'POSTDOC':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_POSTDOC').'. ';
                        break;
                     case 'TERM':
                        $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_TERM').'. ';
                        break;
                     default:
                        $biblio  .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_material_detail_view(446) ';
                        break;
                  }
               }
               $biblio .= $item->getAddress().': '.$item->getUniversity();
               if ( $item->getFaculty() ) {
                  $biblio .= ', '.$item->getFaculty();
               }
               $biblio .= '.';
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
                  if( $item->getURLDate() ) {
                     $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
                  }
                  $biblio .= '.';
               }
               break;
            case 'website':
               $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
               if( $item->getURLDate() ) {
                  $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
               }
               $biblio .= '.';
               break;
            case 'manuscript':
               $biblio .= $item->getBibliographicValues();
               if ( $item->getAddress() ) {
                  $biblio .= ' '.$item->getAddress();
                  $biblio .= '.';
               }
               if ( $item->getURL() ) {
                  $biblio .= ' '.$this->_translator->getMessage('MATERIAL_BIB_URL', $item->getURL());
                  if( $item->getURLDate() ) {
                     $biblio .= ' ('.$this->_translator->getMessage('MATERIAL_BIB_URL_DATE', $item->getURLDate()).')';
                  }
                  $biblio .= '.';
               }
               break;
      		/** Start Dokumentenverwaltung **/
            case 'document':
                $formal_data_bib = array();
                $html .= $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_ADMINISTRATION_INFO');
        		if ( $item->getDocumentEditor() ) {
                	$temp_array = array();
         			$temp_array[] = $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR');
         			$temp_array[] = $item->getDocumentEditor();
         			$formal_data_bib[] = $temp_array;
         		}
               	if ( $item->getDocumentMaintainer() ) {
                	$temp_array = array();
         			$temp_array[] = $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER');
         			$temp_array[] = $item->getDocumentMaintainer();
         			$formal_data_bib[] = $temp_array;
               	}
               	if ( $item->getDocumentReleaseNumber() ) {
                	$temp_array = array();
         			$temp_array[] = $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER');
         			$temp_array[] = $item->getDocumentReleaseNumber();
         			$formal_data_bib[] = $temp_array;
               	}
               	if ( $item->getDocumentReleaseDate() ) {
                	$temp_array = array();
         			$temp_array[] = $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE');
         			$temp_array[] = $item->getDocumentReleaseDate();
         			$formal_data_bib[] = $temp_array;
               	}
      			if ( !empty($formal_data_bib) ) {
         			$html .= $this->_getFormalDataAsHTML($formal_data_bib);
			    }
               	break;
      		/** Ende Dokumentenverwaltung **/
            case 'none':
            default:
               $biblio .= $item->getBibliographicValues();
         }
      }
      $biblio_pur = strip_tags($biblio);
      $biblio_pur = str_ireplace('&nbsp;','',$biblio_pur);
      $biblio_pur = trim($biblio_pur);
      if ( $bib_kind != 'none'
           and !empty($biblio_pur)
         ) {
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC');
         if ( !empty($biblio) ) {
            $temp_array[]  = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($biblio)));
         } else {
            $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>';
         }
         $formal_data1[] = $temp_array;
      }

      if ( $item->issetBibTOC() ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('COMMON_TABLE_OF_CONTENT');
         $temp_array[] = '<a href="'.$item->getBibTOC().'" target="blank">'.chunkText($item->getBibTOC(),60).'</a>';
         $formal_data1[] = $temp_array;
      }
      if ( $item->issetBibURL() ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('BELUGA_LINK');
         $temp_array[] = '<a href="'.$item->getBibURL().'" target="blank">'.chunkText($item->getBibURL(),60).'</a>';
         $formal_data1[] = $temp_array;
      }
      if ( $item->issetBibAvailibility() ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('BELUGA_AVAILABILITY');

         $link = $item->getBibAvailibility();
         $temp_array[] = $link;
         $formal_data1[] = $temp_array;
      }
      if($context_item->isWikiActive()){
         if ($item->isExportToWiki()) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI_LINK');
            $temp_array[] = $item->getExportToWikiLink();
            $formal_data1[] = $temp_array;
         }
      }
     if($context_item->isWordpressActive()){
         if ($item->isExportToWordpress()) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('MATERIAL_EXPORT_TO_WORDPRESS_LINK');
            $temp_array[] = $item->getExportToWordpressLink();
            $formal_data1[] = $temp_array;
         }
      }

      // Sections
      $this->_section_list = $item->getSectionList();
      if ( !$this->_section_list->isEmpty() ){
         $temp_array = array();
         $temp_array[]   = $this->_translator->getMessage('MATERIAL_ABSTRACT');
         $description = $item->getDescription();
         if ( !empty($description) ) {
            $temp_string = $this->_text_as_html_long($this->_cleanDataFromTextArea($this->_compareWithSearchText($description)));
            $temp_array[]   =  '<div class="handle_width">'.$this->_show_images($temp_string,$this->_item, $with_links).'</div>'.'<br/><br/>';
         } else {
            $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>';
         }
         $formal_data1[]  = $temp_array;
         $sections = array();
         $i = 1;
         $section = $this->_section_list->getFirst();
         while ( $section ) {
            // files
            $fileicons = $this->_getItemFiles( $section,true);
            if ( !empty($fileicons) ) {
               $fileicons = '&nbsp;'.$fileicons;
            }

            $section_title = $this->_text_as_html_short($this->_compareWithSearchText($section->getTitle()));
            if( $with_links and !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
               $section_title = '<a href="#anchor'.$section->getItemID().'">'.$section_title.'</a>'.$fileicons.LF;
            }
            $sections[] = $section_title;
            $section = $this->_section_list->getNext();
            $i++;
         }
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_SECTIONS');
         $temp_array[] = implode(BRLF, $sections).'<br/><br/>';
         $formal_data1[] = $temp_array;
      }

      // Files
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data1[] = $temp_array;
      }

      // World-public status
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->isCommunityRoom() and $current_context->isOpenForGuests() ) {
         $temp_array = array();
         $world_public = $item->getWorldPublic();
         if ( $world_public == 0 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_0');
         } elseif ( $world_public == 1 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_1');
         } elseif ( $world_public == 2 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_2');
         }
         $temp_array[0] = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH');
         $temp_array[1] = $public_info;
         $formal_data1[] = $temp_array;
      }
      $version_mode = 'long';
      $iid = 0;
      $params = $this->_environment->getCurrentParameterArray();
      if (isset($params['iid'])){
         $iid = $params['iid'];
      }
      $params = array();
      $params = array();
      $params = $this->_environment->getCurrentParameterArray();
      $show_versions = 'false';
      if (isset($params[$iid.'version_mode']) and $params[$iid.'version_mode']=='long'){
          $show_versions = 'true';
      }
      $params[$iid.'version_mode']='long';

      // Versions
      $versions = array();
      if ( !$this->_version_list->isEmpty() ) {
         $version = $this->_version_list->getFirst();
         if ( $version->getVersionID() == $this->_item->getVersionID() ) {
            $title = '&nbsp;&nbsp;'.$this->_translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
         } else {
           $params = array();
           $params[$iid.'version_mode'] = 'long';
           $params['iid'] = $version->getItemID();
           $title = '&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
           unset($params);
         }
         $version = $this->_version_list->getNext();
         $current_user = $this->_environment->getCurrentUserItem();
         $is_user = $current_user->isUser();
         while ( $version ) {
            if ( !$with_links
                 or ( !$is_user
                      and $this->_environment->inCommunityRoom()
                      and !$version->isWorldPublic()
                    )
                 or $item->getVersionID() == $version->getVersionID()
               ) {
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
            } else {
               $params = array();
               $params[$iid.'version_mode'] = 'long';
               $params['iid'] = $version->getItemID();
               $params['version_id'] = $version->getVersionID();
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
               unset($params);
            }
            $version = $this->_version_list->getNext();
         }
         $count = $this->_version_list->getCount();
         if ( !empty($versions) and $count > 1 ) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('MATERIAL_VERSION');
            $html_string ='&nbsp;<img id="toggle'.$item->getItemID().$item->getVersionID().'" src="images/more.gif"/>';
            $html_string .= $title;
            $html_string .= '<div id="creator_information'.$item->getItemID().$item->getVersionID().'">'.LF;
            $html_string .= '<div class="creator_information_panel">     '.LF;
            $html_string .= '<div>'.LF;
            if ($show_versions == 'true'){
               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",true)</script>';
            }else{
               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",false)</script>';
            }
            if($with_links) {
               $html_string .= implode(BRLF, $versions);
            } else {
               $version_count = count ($versions);
               $html_string .= "$version_count. ".$versions[0];
            }
            $html_string .= '</div>'.LF;
            $html_string .= '</div>'.LF;
            $html_string .= '</div>'.LF;
            $temp_array[] = $html_string;
            $formal_data1[] = $temp_array;
         }
      }
      if ( !empty($formal_data1) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data1);
         if ( isset($html_script) and !empty($html_script) ) {
            $html .= $html_script;
         }
      }

      if ( $this->_section_list->isEmpty() ) {
         // Description
         $desc = $item->getDescription();
         if ( !empty($desc) ) {
            $temp_string = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
            $html .= $this->getScrollableContent($temp_string,$item,'',$with_links);
         }
      }

      $html  .= '<!-- END OF material ITEM DETAIL -->'.LF.LF;
      return $html;
   }


   function _getPrintableSubItemAsHTML($item, $anchor_number) {
      return $this->_getSubItemAsHTML($item, $anchor_number, FALSE);
   }

   function _getSubItemAsHTML($item, $anchor_number, $with_links=TRUE){
      $html = '';
      $section_description = $item->getDescription();
      if ( !empty($section_description) ) {
         $section_description = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($section_description)));
         $section_description = $this->_show_images($section_description, $item, $with_links);
         $html .= $this->getScrollableContent($section_description,$item,'',$with_links);
      }

      // files
      $formal_data = array();
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
      }

      return $html;
   }

   function getInfoForHeaderAsHTML () {
      $retour = parent::getInfoForHeaderAsHTML();
      $dublin_core_array = $this->_item->getDublinCoreArray();
      if (!empty($dublin_core_array)){
         $retour .='   <!-- Begin Dublin Core Data (Material) -->'."\n";
      }
      foreach ($dublin_core_array as $key => $value) {
         //since a quotationmark " in the dublin core array is taken literally and makes some problems, we substitute quot with two apostrophs: " => ''
         $retour .= '   <meta name="'.$key.'" content="'.str_replace("\"","''",$value).'"/>'."\n";
      }
      if (!empty($dublin_core_array)){
         $retour .='   <!-- End Dublin Core Data (Material) -->'."\n";
      }
      return $retour;
   }


   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
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
             }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $file_list;
   }

   function _getAdditionalDropDownEntries() {
      $action_array = array();
      $current_context = $this->_environment->getCurrentContextItem();

      foreach($this->_dropdown_rubrics_new as $rubric){
         if ( $current_context->isOpen()) {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image_import = 'images/commsyicons_msie6/22x22/'.$this->_dropdown_image_array[$rubric].'.gif';
            } else {
               $image_import = 'images/commsyicons/22x22/'.$this->_dropdown_image_array[$rubric].'.png';
            }
            $params = array();
            $params['iid'] = 'NEW';
            $params['linked_item'] = $this->_item->getItemID();
            $href_import = curl($this->_environment->getCurrentContextID(),
                               $rubric,
                               'edit',
                               $params);
            $text_import = $this->_translator->getMessage($this->_dropdown_message_array[$rubric]);
            if ( !empty($text_import)
                 and !empty($image_import)
                 and !empty($href_import)
               ) {
               $temp_array = array();
               $temp_array['dropdown_image']  = "new_icon";
               $temp_array['text']  = $text_import;
               $temp_array['image'] = $image_import;
               $temp_array['href']  = $href_import;
               $action_array[] = $temp_array;
               unset($temp_array);
            }
         }
      }

      unset($current_context);
      return $action_array;
   }
}
?>