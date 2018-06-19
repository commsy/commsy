<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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
 *  generic upper class for CommSy detail views
 */
class cs_detail_view extends cs_view {

   /**
    * array - an array of item ids to browse
    */
   var $_browse_ids = array();
   var $_rubric_connections = array();
   var $_sub_rubric_connections = array();

   var $_annotation_list = null;

   var $_openCreatorInfo = null;

   /**
    * int - position in browsing list
    */
   var $_position = -1;

   var $_horizontal_line_number = 2;
   /**
    * item - containing the item to display
    */
   var $_item = NULL;

   /**
    * subitems - cs_list containing the item to display below the actual item (e.g. sections)
    */
   var $_subitems = NULL;

   var $_display_title = true;

   var $_with_slimbox = false;

   /** constructor: cs_detail_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $creatorInfoStatus = array();
      if ( isset($params['creator_info_status']) ) {
         $creatorInfoStatus = $params['creator_info_status'];
      }
      cs_view::__construct($params);
      $this->_openCreatorInfo = $creatorInfoStatus;
      $context_item = $this->_environment->getCurrentContextItem();
   }

   function setAnnotationList($annotation_list) {
      $this->_annotation_list = $annotation_list;
   }

   function setExtraHorizontalLineNumbers($count) {
      $this->_horizontal_line_number = 2+$count;
   }

   /**
    * Set an array with the ids of all items shown in the last list view the
    * user saw to enable browsing within the detail views of those items.
    */
   function setBrowseIDs ($browse_ids) {
      $this->_browse_ids = array_values((array)$browse_ids);  // Re-Index array, starting at 0
   }

   function getBrowseIDs () {
      return $this->_browse_ids;
   }

   /**
    * Set the position of the current item in the browsing array.
    * Cannot be determined automatically, if the same item appears
    * multiple time, e.g. if ordered by group.
    */
   function setPosition ($pos) {
      $this->_position = (int)$pos;
   }

   function getPosition () {
      return $this->_position;
   }

   /**
    * Set an array of connected rubrics to be shown in the network
    * navigation area on the right side. Set for the main item and
    * subitems seperately.
    */
   function setRubricConnections ($item) {
      $user_manager = $this->_environment->getUserManager();
      $context_id = $this->_environment->getCurrentContextID();
      if ( !$this->_environment->inPortal()
           and !$this->_environment->inServer()
           and $this->_environment->getCurrentModule() != 'account'
         ) {
         $user_manager->getRoomUserByIDsForCache($context_id);
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      }
      $first = array();
      $secon = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none'
              and $context_item->withRubric($link_name[0])
              and $link_name[0] != CS_USER_TYPE
              and $link_name[0] != CS_MYROOM_TYPE
              and !$this->_environment->isPlugin($link_name[0])
            ) {
            $rubric_connections[] = $link_name[0];
         }
      }
      $this->_rubric_connections = $rubric_connections;
   }

   function getRubricConnections () {
      return $this->_rubric_connections;
   }

   function setSubItemRubricConnections ($rc) {
      $this->_sub_rubric_connections = $rc;
   }

   function getSubItemRubricConnections () {
      return $this->_sub_rubric_connections;
   }

   /**
    * Set the cs_item and optionally a list of subitems (also
    * of type cs_item) to display.
    */
   function setItem ($item){
      $this->_item = $item;
   }

   function getItem () {
      return $this->_item;
   }

   function setSubItemList ($subitems) {
      $this->_subitems = $subitems;
   }

   function getSubItemList () {
      return $this->_subitems;
   }

   function _getDetailAnnotationActionsAsHTML ($item= NULL) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $annotated_item = $this->getItem();
      $annotated_item_type = $annotated_item->getItemType();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
      $html .= '<div class="right_box_main" >'.LF;
      if ( isset($item) ){
         if ($item->mayEdit($current_user) and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['mode'] = 'annotate';
            $html .= '> '. ahref_curl( $this->_environment->getCurrentContextID(),
                                          'annotation',
                                          'edit',
                                          $params,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).BRLF;// ANNOTATIONS_EDIT
            unset($params);
         } else {
            $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_EDIT_ITEM').'</span>'.BRLF;
         }
         if ( $item->mayEdit($current_user) and $this->_with_modifying_actions  ) {
           $params = $this->_environment->getCurrentParameterArray();
           $params['action'] = 'delete';
           $params['annotation_iid'] = $item->getItemID();
           $params['iid'] = $annotated_item->getItemID();
           $params['annotation_action'] = 'delete';
           if ( !($this->_environment->getCurrentBrowser() =='MSIE'
                  and $this->_environment->getCurrentBrowserVersion() != '7.0')
              ){
                 $anchor = 'anchor'.$item->getItemID();
              }else{
                 $anchor = '';
              }
           $html .= '> '. ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'detail',
                                          $params,
                                          $this->_translator->getMessage('COMMON_DELETE_ITEM'),
                                          '',
                                          '',
                                          $anchor).BRLF;
           unset($params);
         } else {
            $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_DELETE_ITEM').'</span>'.BRLF;
         }
      }
      if ( $current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         if ($annotated_item_type == CS_MATERIAL_TYPE) {
            $params['version'] = $annotated_item->getVersionID();
         }
         $params['ref_iid'] = $annotated_item->getItemID();
         $params['mode'] = 'annotate';
         $params['iid'] = 'NEW';
         $html .= '> '. ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'annotation',
                                    'edit',
                                    $params,
                                    $this->_translator->getMessage('COMMON_CREATE_ANNOTATION')).BRLF; // ANNOTATIONS_NEW
                                    unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_CREATE_ANNOTATION').'</span>'.BRLF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }


   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      if ( $item->mayEdit($current_user) ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $html .= '> '. ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'edit',
                                          $params,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_EDIT_ITEM').'</span>'.BRLF;
      }
      $html .= $this->_getAdditionalActionsAsHTML($item);
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
      $params['download']='zip';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_DOWNLOAD')).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function getScrollableContent($text,$item,$width,$width_link = true){
      $html = '';
      if (empty($width)){
         $session = $this->_environment->getSession();
         $left_menue_status = $session->getValue('left_menue_status');
         if ($left_menue_status != 'disapear') {
            if ($this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE){
               $width = '540';
            }else{
               $width = '530';
            }
         }else{
            $width = '660';
         }
      }
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] != 'print'){
      $params['mode']='print';
      $anchor = '';
      if ($item->getType()=='section' or $item->getType()=='annotation' or $item->getType()=='discarticle'){
         $anchor = 'anchor'.$item->getItemID();
      }
      $link = '&gt; '.ahref_curl($this->_environment->getCurrentContextID(),
                                 $this->_environment->getCurrentModule(),
                                 'detail',
                                 $params,
                                 $this->_translator->getMessage('COMMON_LIST_WHOLE_CONTENT'),
                                 '',
                                 'help',
                                 $anchor,
                                 '',
                                 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=500\');"',
                                 '',
                                 ''
                                 );
      $link = addslashes($link);
      $link = str_replace('</','COMMSYDHTMLTAG',$link);
      $html .= '<div id="handle_width_'.$item->getItemID().'"><div id="inner_handle_width_'.$item->getItemID().'" class="handle_width">'.$this->_show_images($text,$this->_item,$width_link).'</div></div>'.LF;
      $html .= '<script type="text/javascript"> handleWidth("handle_width_'.$item->getItemID().'","'.$width.'","'.$link.'");</script>';

      }else{
         $html .= $text;
      }
      return $html;
   }

   function _getAdditionalActionsAsHTML($item){
      $html = '';
      return $html;
   }


   function C ($item) {
      $html  = '';
      return $html;
   }




   function _getForwardBoxAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= '<div class="index_forward_links" style="white-space:nowrap; text-align:center;">'.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" >'.LF;
      if ( $current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         $html .= '> '. ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $this->_translator->getMessage('COMMON_NEW_ITEM')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_NEW_ITEM').'</span>'.BRLF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }



   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=10, $maxsize=20, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getBuzzwordColorLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=30, $maxsize=70, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }



   function _getBuzzwordBoxAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $buzzword_list = $item->getBuzzwordList();
      $buzzword_entry = $buzzword_list->getFirst();
      $item_id_array = array();
      while($buzzword_entry){
         $item_id_array[] = $buzzword_entry->getItemID();
         $buzzword_entry = $buzzword_list->getNext();
      }
      if ( isset($item_id_array[0]) ){
         $links_manager = $this->_environment->getLinkManager();
         $count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array,'buzzword');
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_BUZZWORDS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<div>'.LF;
      if ($buzzword_list ->isEmpty()) {
         $html .= '   <span style="margin-left:3px; margin-right:3px;"><a><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></a></span>'.LF;
      }else{
         $buzzword_entry = $buzzword_list->getFirst();
         while($buzzword_entry){
            $count = 0;
            if ( isset($count_array[$buzzword_entry->getItemID()]) ){
                $count = $count_array[$buzzword_entry->getItemID()];
            }
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword_entry->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $buzzword_entry->getName().LF;
            $title .= '</span> ';
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $title,$title).LF;
           $buzzword_entry = $buzzword_list->getNext();
         }
      }
      $html .= '</div>'.LF;
/*      $html .= '<div style="width:100%; text-align:right; padding-right:2px; padding-top:3px; font-size:8pt;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_ATTACH_LINK')).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_ATTACH').'</span>'.LF;
      }
      $html .= '</div>'.LF; */
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      unset($current_context);
      return $html;
   }

   function getTagColorLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=40, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function _getTagBoxAsHTML($item){
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAGS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;

      $tag_list = $item->getTagList();
      $text = '';
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      $tag_manager = $this->_environment->getTagManager();
      $tag_item = $tag_list->getFirst();
      if ( isset ($tag_item) ){
         $params = $this->_environment->getCurrentParameterArray();
         while( $tag_item ){
            $text .= '<div style="margin-bottom:5px;">';
            $count_all = 1;
            $shown_tag_array = $tag2tag_manager->getFatherItemIDArray($tag_item->getItemID());
            $i = 1;
            if( !empty($shown_tag_array) ) {
               $count_all = count($shown_tag_array);
               $shown_tag_array = array_reverse($shown_tag_array);
               foreach( $shown_tag_array as $shown_tag ){
                  $father_tag_item = $tag_manager->getItem($shown_tag);
                  $count = $count_all - $i + 1;
                  $ebene = $i-1;
                  $font_size = round(13 - (($count*0.2)+$count));
                  $font_weight = 'normal';
                  $font_style = 'normal';
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
                  $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
                  if (($ebene*15) <= 30){
                     $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
                  }else{
                     $text .= '<div style="padding-left:40px; color:'.$color.'; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
                  }
                  $params['seltag'] = 'yes';
                  if ( isset($father_tag_item) ) {
                     $params['seltag_'.($count_all-$i)] = $father_tag_item->getItemID();
                  }
                  $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $father_tag_item->getTitle(),
                                $father_tag_item->getTitle(),
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:'.$color.'"').LF;
                  $text .= '- '.$title_link;
                  $text .= '</div>';
                  $i++;
               }
            }
            $params['seltag'] = 'yes';
            $params['seltag_'.($count_all-1)] = $tag_item->getItemID();
            $count = $count_all - $i + 1;
            $ebene = $i-1;
            $font_size = 13;
            $font_weight = 'normal';
            $font_style = 'normal';
            $font_color = 20 + $this->getTagColorLogarithmic($count);
            $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             'index',
                             $params,
                             $tag_item->getTitle(),
                             $tag_item->getTitle(),
                             '',
                             '',
                             '',
                             '',
                             '',
                             'style="color:'.$color.'"').LF;
            $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
            $text .= '- '.$title_link;
            $text .= '</div>';
            $text .= '</div>';
            $tag_item = $tag_list->getNext();
         }

      }
      if ( empty($text) ){
         $html .= '   <span style="margin-left:3px; margin-right:3px;"><a><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></a></span>'.LF;
      }else{
         $html .= $text;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }



   /** get detail view as HTML
    * this method returns the detail view in HTML-Code
    *
    * @returns string detail view as HMTL
    */
   function asHTML () {
      $item = $this->getItem();
      $html  = LF.'<!-- BEGIN OF DETAIL VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="width:100%;">'.LF;
      $rubric = $this->_environment->getCurrentModule();
      $current_context = $this->_environment->getCurrentContextItem();
      $detail_box_conf = $current_context->getDetailBoxConf();

      if($rubric == CS_DISCUSSION_TYPE){
         $html .= '<h2 class="pagetitle">'.$this->_getTitleAsHTML();
      }elseif ($rubric != CS_USER_TYPE ){
         $html .= '<h2 class="pagetitle">'.$this->_text_as_html_short($item->getTitle());
      }else{
        $html .= '<h2 class="pagetitle">'.$item->getFullName();
      }
      $html .= '</h2>'.LF;
      $html .='</div>'.LF;

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $title_string = '';
         $desc_string = '';
         $config_text = '';
         $size_string = '';
         $current_context = $this->_environment->getCurrentContextItem();
         $html .='<div style="float:right; width:27%; margin-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div id="commsy_panels">'.LF;

         $html .='<div style="margin-bottom:1px;">'.LF;
         $html .= $this->_getForwardBoxAsHTML($item);
         $html .='</div>'.LF;

         $title_string .= '"'.$this->_translator->getMessage('COMMON_ACTIONS').'"';
         $desc_string .= '""';
         $size_string .= '"10"';
         if ( strstr($detail_box_conf,'detailactions_tiny') ){
            $config_text .= 'false';
         } else {
            $config_text .= 'true';
         }
         $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
         $html .= $this->_getDetailActionsAsHTML($item);
         $html .='</div>'.LF;
         if ( $rubric != CS_GROUP_TYPE and $rubric != CS_PROJECT_TYPE and $rubric != CS_TOPIC_TYPE and $rubric != CS_INSTITUTION_TYPE){
            if ( $current_context->withBuzzwords()
                 and !strstr($detail_box_conf,'detailbuzzwords_nodisplay')
               ){
               $title_string .= ',"'.$this->_translator->getMessage('COMMON_BUZZWORDS').'"';
               $desc_string .= ',""';
               $size_string .= ',"10"';
               if ( strstr($detail_box_conf,'detailbuzzwords_short') ){
                  $config_text .= ',true';
               } else {
                  $config_text .= ',false';
               }
               $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
               $html .= $this->_getBuzzwordBoxAsHTML($item);
               $html .='</div>'.LF;
            }
            if ( $current_context->withTags()
                   and !strstr($detail_box_conf,'detailtags_nodisplay')
               ){
               $title_string .= ',"'.$this->_translator->getMessage('COMMON_TAGS').'"';
               $desc_string .= ',""';
               $size_string .= ',"10"';
               if ( strstr($detail_box_conf,'detailtags_short') ){
                  $config_text .= ',true';
               } else {
                  $config_text .= ',false';
               }
               $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
               $html .= $this->_getTagBoxAsHTML($item);
               $html .='</div>'.LF;
            }
            if ( $current_context->withWorkflow()
                 and !strstr($detail_box_conf,'detailbuzzwords_nodisplay')
               ){
               $title_string .= ',"'.$this->_translator->getMessage('COMMON_BUZZWORDS').'"';
               $desc_string .= ',""';
               $size_string .= ',"10"';
               if ( strstr($detail_box_conf,'detailbuzzwords_short') ){
                  $config_text .= ',true';
               } else {
                  $config_text .= ',false';
               }
               $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
               $html .= $this->_getWorkflowBoxAsHTML($item);
               $html .='</div>'.LF;
            }
         }
############SQL-Statements reduzieren
         if ( !strstr($detail_box_conf,'detailnetnavigation_nodisplay') ){
            $title_string .= ',"'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'"';
            $desc_string .= ',""';
            $size_string .= ',"10"';
            if ( strstr($detail_box_conf,'detailnetnavigation_short') ){
               $config_text .= ',true';
            } else {
               $config_text .= ',false';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->_getAllLinkedItemsAsHTML($item);
            $html .='</div>'.LF;
         }
#         $html .='<div style="margin-bottom:1px;">'.LF;
#         $html .= $this->_getPluginInfosForNetNavigationAsHTML();
#         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
      }
      if ( (isset($_GET['mode']) and $_GET['mode']=='print') ){
         $html .='<div class="infoborder" style="width:100%; margin-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div class="infoborder_display_content"  style="margin-top:5px; vertical-align:bottom;">'.LF;
      }
      $html .='<div style="margin-bottom:10px;">'.LF;

      if ($item->isNotActivated()){
         $formal_data1 = array();
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_RIGHTS');

         $activating_date = $item->getActivatingDate();
         if (strstr($activating_date,'9999-00-00')){
            $title = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
         }else{
            $title = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
         }
         $temp_array[] = $title;
         $formal_data1[] = $temp_array;
         $html .= $this->_getFormalDataAsHTML($formal_data1);
      }


      $html .= $this->_getContentAsHTML();
      $html .='</div>'.LF;
      $html .='<div class="infoborder" style="clear:both; margin-top:5px; margin-bottom:25px; padding-top:10px; padding-bottom:10px; vertical-align:top;">';
      $mode = 'short';
      if (in_array($item->getItemID(),$this->_openCreatorInfo)) {
         $mode = 'long';
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= $this->_getCreatorInformationAsHTML($item, 3,$mode).LF;
      }
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      if ($this->_environment->getCurrentModule() != 'user' or !$this->_environment->inPrivateRoom() ){
############SQL-Statements reduzieren
         $html .= $this->_getSubItemsAsHTML($item);
      }
      if ($rubric != CS_GROUP_TYPE
      and $rubric != CS_USER_TYPE
      and $rubric != CS_DISCUSSION_TYPE
      and $this->_environment->getCurrentModule() !='account'){
         $html .= $this->_getAnnotationsAsHTML();
      } elseif ($rubric == CS_DISCUSSION_TYPE and !$item->isClosed() and $this->_with_modifying_actions ) {
         $html .= $this->_getDiscussionFormAsHTML();
      }
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
         $html .= '<script type="text/javascript">'.LF;
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE and $current_browser == 'msie' and !strstr($current_browser_version,'7.')){
            $html .= 'preInitCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'));'.LF;
         }else{
            if(isset($title_string)
               and isset($desc_string)
               and isset($config_text)
               and isset($size_string)){
               $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'),Array(),null,null);'.LF;
            }
         }
         $html .= '</script>'.LF;
      return $html;
   }

   function _getSubItemsAsHTML($item){
      $html ='';
      $html = '<!-- BEGIN OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      $subitems = $this->getSubItemList();
      $count = 0;
      if ( isset($subitems) and !$subitems->isEmpty() ) {
         $count=$subitems->getCount();
         $current_item = $subitems->getFirst();
         $pos_number = 1;
         while ( $current_item ) {
            $html .='<div style="width:100%; margin-top:30px;">'.LF;
            $html .='<div style="width:100%; vertical-align:bottom;">'.LF;
            $html .= '<a id="anchor'.$current_item->getItemID().'" name="anchor'.$current_item->getItemID().'"></a>'.LF;
            $html .= '<div style="float:right; text-align:right; vertical-align:bottom; padding-top:5px; padding-right: 5px;">';
            if ( !isset($this->_sub_item_pos_number) ){
               $this->_sub_item_pos_number = 1;
            }else{
               $this->_sub_item_pos_number =  $this->_sub_item_pos_number+1;
            }
            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $html .= $this->_getBrowsingIconsAsHTML($current_item, $this->_sub_item_pos_number,$count);
            }
            $html .= '</div>';
            $html .= '<h3 class="subitemtitle">'.$this->_getSubItemTitleAsHTML($current_item, $pos_number);
            $html .= '</h3>'.LF;
            $html .='</div>'.LF;
            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $html .='<div style="float:right; width:27%; margin-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
               $html .='<div style="margin-bottom:10px;">'.LF;
               $html .= $this->_getSubItemDetailActionsAsHTML($current_item);
               $html .='</div>'.LF;
               $html .='</div>'.LF;
            }

            if ( (isset($_GET['mode']) and $_GET['mode']=='print') ){
               $html .='<div class="infoborder" style="width:100%; margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
            }else{
               $html .='<div class="infoborder_display_content" style="margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
            }
            $html .='<div style="margin-bottom:10px; ">'.LF;
            $html .= $this->_getSubItemAsHTML($current_item, $pos_number).LF;
            $html .='</div>'.LF;
            $html .='<div style="margin-top:5px; margin-bottom:0px; padding-top:10px; padding-bottom:50px; vertical-align:top;">';


            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $mode = 'short';
               if (!$item->isA(CS_USER_TYPE)) {
                  $mode = 'short';
                  if (in_array($current_item->getItemId(),$this->_openCreatorInfo)) {
                     $mode = 'long';
                  }
                  $html .='<div style="border-top:0px solid black; margin-top:5px; margin-bottom:0px; padding-bottom:0px; vertical-align:top;">';
                  $html .= $this->_getCreatorInformationAsHTML($current_item, 6,'long').LF;
                  $html .='</div>'.LF;
               }
               $html .='</div>'.LF;
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
               $html .='</div>'.LF;
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
            }
            $html .='</div>'.LF;
            $current_item = $subitems->getNext();
            $pos_number++;
         }
      }
      $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      return $html;
   }



   function _getAnnotationsAsHTML () {
      $html = '</div>'.LF.LF;
      $html .= '</div>'.LF.LF;
      $html .= '<!-- BEGIN OF ANNOTATION VIEW -->'.LF.LF;
      $html .='<div class="detail_sub_items_title">'.LF;
      $html .= '<span class="sub_item_pagetitle">'.$this->_translator->getMessage('COMMON_ANNOTATIONS');
      $html .= '</span>'.LF;
      if ( !empty($this->_annotation_list) ){
         $count = $this->_annotation_list->getCount();
         if ($count == 1){
            $html .= '<span class="sub_item_description"> ('.$this->_translator->getMessage('COMMON_ONE_ANNOTATION');
         }else{
            $html .= '<span class="sub_item_description"> ('.$this->_translator->getMessage('COMMON_X_ANNOTATIONS',$count);
         }
      }else{
         $html .= '<span class="sub_item_description"> ('.$this->_translator->getMessage('COMMON_NO_ANNOTATIONS');
      }
      $html .= ')</span>'.LF;
      $html .='</div>'.LF;
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ){
         $html .='<div class="sub_item_main">'.LF;
      }else{
         $html .='<div class="sub_item_main" style="background-color:white;">'.LF;
      }
      $html .='<div>'.LF;
      $html .= '<a name="annotations"></a>'.LF;
      $html .='<div style="width:100%;">'.LF;
      if ( !empty($this->_annotation_list) ){
         $annotation_item = $this->_annotation_list->getFirst();
      }
      if ( empty($annotation_item) ){
         if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ){
            $html .='<div style="float:right; width:27%; margin-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
            $html .='<div style="margin-bottom:10px;">'.LF;
            $html .= $this->_getDetailAnnotationActionsAsHTML();
            $html .='</div>'.LF;
            $html .='</div>'.LF;
            $html .='<div class="content_display_width" style="margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
         }else{
            $html .='<div style="width:100%; margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
         }
         $html .='</div>'.LF;
         $html .='<div style="clear:both;">'.LF;
         $html .='</div>'.LF;
      }else{
         $annotation_item = $this->_annotation_list->getFirst();
         while( $annotation_item ){
            $html .='<div style="width:100%; margin-top:10px;">'.LF;
            $html .= '<a id="anchor'.$annotation_item->getItemID().'" name="anchor'.$annotation_item->getItemID().'"></a>'.LF;
            $html .= '<h3 class="annotationtitle">'.$this->_text_as_html_short($annotation_item->getTitle());
            $html .= '</h3>'.LF;
            $html .= '</div>';
            if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ){
               $html .='<div style="float:right; width:27%; margin-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
               $html .='<div style="margin-bottom:10px;">'.LF;
               $html .= $this->_getDetailAnnotationActionsAsHTML($annotation_item);
               $html .='</div>'.LF;
               $html .='</div>'.LF;
               $html .='<div class="infoborder_display_content" style="margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
            }else{
               $html .='<div class="infoborder" style="width:100%; margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
            }
            $html .= $this->_getAnnotationContentAsHTML($annotation_item);
            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $mode = 'short';
               if (in_array($annotation_item->getItemID(),$this->_openCreatorInfo)) {
                  $mode = 'long';
               }
               $html .='<div style="border-top:0px solid black; margin-top:20px; margin-bottom:0px; padding-bottom:50px; vertical-align:top;">';
               $html .= $this->_getCreatorInformationAsHTML($annotation_item, 0,'long').LF;
               $html .='</div>'.LF;
            }
            $html .='</div>'.LF;
            $html .='<div style="clear:both;">'.LF;
            $html .='</div>'.LF;
            $annotation_item = $this->_annotation_list->getNext();
         }
      }
      $html .='</div>'.LF;
      $html .= '<!-- END OF ANNOTATION VIEW -->'.LF.LF;
      return $html;
}



   function _getContentAsHTML() {
      $item = $this->getItem();
      $html ='';
      if ( isset($item) ) {
         $html .= $this->_getItemAsHTML($item);
      } else {
         $html .= '<!-- No item set! -->'.LF;
      }
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
      return $html;
   }


   function _getAllLinkedItemsAsHTML ($spaces=0) {
      $connections = $this->getRubricConnections();
      $item = $this->getItem();
      $current_context = $this->_environment->getCurrentContextItem();
      $path_counter = 0;
      $path_entry_counter = 0;
      if ($current_context->withPath()){
         $topic_link_items = $item->getLinkItemList(CS_TOPIC_TYPE);
         $path_counter = $topic_link_items->getCount();
         $link_item = $topic_link_items->getFirst();
         while($link_item){
            if (isset($link_item) and !empty($link_item)){
               $topic_item = $link_item->getLinkedItem($item);
               if ($topic_item->isPathActive()){
                  $path_item_list = $topic_item->getPathItemList();
                  $temp_path_counter = $path_item_list->getCount();
                  if ($temp_path_counter > $path_entry_counter){
                     $path_entry_counter = $temp_path_counter;
                  }
               }
            }
            $link_item = $topic_link_items->getNext();
         }
      }
      $link_items = $item->getLatestLinkItemList(10);
      $count_max_entries = $link_items->getCount();
      $count_rubrics = count($connections);
      $rubric_height = ($count_rubrics + $path_counter) * 20;
      if ($path_entry_counter > $count_max_entries){
         $entry_height = 25 + ($path_entry_counter * 15);
      }else{
         $entry_height = $count_max_entries * 15;
      }
      $height = 40 - $count_rubrics;
      $final_height = $height + $entry_height + $rubric_height;
      $html = '';
      $html .= '<div id="netnavigation'.$item->getItemID().'" style="height:'.$final_height.'px;">'.LF;
      $html .= '<div class="netnavigation" >'.LF;

      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'</div>';
      $html .= '         </noscript>';

      $title_string ='"'.$this->_translator->getMessage('COMMON_NETNAVIGATION_LATEST_ENTRIES');
      $link_items = $item->getLatestLinkItemList(10);
      $title_string .= ' ('.$link_items->getCount().')"';
      $expanded_string ='false';
      $html .='		<div class="netnavigation_panel">     '.LF;
      $html .= '         <noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_NETNAVIGATION_LATEST_ENTRIES').' ('.$link_items->getCount().')</div>';
      $html .= '         </noscript>';

      $html .='				<div><ul style="list-style-type: circle; font-size:8pt;">'.LF;
      if ($link_items->isEmpty()) {
         $html .= '   <li><a><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></a></li>'.LF;
      } else {
         $link_item = $link_items->getFirst();
         while($link_item){
            $link_creator = $link_item->getCreatorItem();
            if ( isset($link_creator) and !$link_creator->isDeleted() ) {
               $fullname = $link_creator->getFullname();
            } else {
               $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
            }
          // Create the list entry
            $linked_item = $link_item->getLinkedItem($item);  // Get the linked item
            if ( isset($linked_item) ) {
               $fragment = '';    // there is no anchor defined by default
               $type = $linked_item->getType();
               if ($type =='label'){
                  $type = $linked_item->getLabelType();
               }
               $link_created = $this->_translator->getDateInLang($link_item->getCreationDate());
               $text = '';
               switch ( mb_strtoupper($type, 'UTF-8') )
               {
                  case 'ANNOUNCEMENT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('COMMON_ONE_USER');
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
                     break;
               }
               $link_creator_text = $text.' - '.$this->_translator->getMessage('COMMON_LINK_CREATOR').' '.
                                    $fullname.', '.
                                    $link_created;
               switch ( $type ) {
                  case CS_DISCARTICLE_TYPE:
                     $linked_iid = $linked_item->getDiscussionID();
                     $fragment = $linked_item->getItemID();
                     $discussion_manager = $this->_environment->getDiscussionManager();
                     $linked_item = $discussion_manager->getItem($linked_iid);
                     break;
                  case CS_SECTION_TYPE:
                     $linked_iid = $linked_item->getLinkedItemID();
                     $fragment = $linked_item->getItemID();
                     $material_manager = $this->_environment->getMaterialManager();
                     $linked_item = $material_manager->getItem($linked_iid);
                     break;
                  default:
                     $linked_iid = $linked_item->getItemID();
               }
               $html .= '   <li>';
               $params = array();
               $params['iid'] = $linked_iid;
               $module = Type2Module($type);
               $user = $this->_environment->getCurrentUser();
               if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                   $activating_date = $linked_item->getActivatingDate();
                   if (strstr($activating_date,'9999-00-00')){
                      $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                   }else{
                      $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($linked_item->getActivatingDate()).')';
                   }
                   $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       chunkText($linked_item->getTitle(),27),
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'class="disabled"',
                                       '',
                                       '',
                                       true);
                  unset($params);
               }else{
                  $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       chunkText($linked_item->getTitle(),27),
                                       $link_creator_text,
                                       '_self',
                                       $fragment);
                  unset($params);
               }


               $html .= '</li>'.LF;
            }
            $link_item = $link_items->getNext();
         }
      }
      $html .= '</ul></div>'.LF;
      $html .= '</div>'.LF;

      $counter = 1;
      foreach ( $connections as $connection ) {
         $link_items = $item->getLinkItemList($connection);
         $count = $link_items->getCount();
         if ( $connection != CS_USER_TYPE){
            $html .='		<div class="netnavigation_panel">     '.LF;
            $context = $this->_environment->getCurrentContextItem();
            if ($connection != CS_INSTITUTION_TYPE or $context->withRubric(CS_INSTITUTION_TYPE)) {
               $text = '';
               switch ( mb_strtoupper($connection, 'UTF-8') )
               {
                  case 'ANNOUNCEMENT':
                     $text .= $this->_translator->getMessage('ANNOUNCEMENTS');
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('DATES');
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('DISCUSSIONS');
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('GROUPS');
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('INSTITUTIONS');
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('MATERIALS');
                     break;
                  case 'MYROOM':
                     $html .= $this->_translator->getMessage('MYROOMS');
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('PROJECTS');
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('TODOS');
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('TOPICS');
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('USERS');
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view(692) ';
                     break;
               }
               $title_string .= ',"'.$text;
               $title_string .= ' ('.$link_items->getCount().')"';
               $expanded_string .= ',false';
               $html .= '         <noscript>';
               $html .= '<div class="netnavigation_title">'.$text.'('.$link_items->getCount().')</div>';
               $html .= '         </noscript>';
               $html .= $this->_getLinkedItemsAsHTML($item, $link_items, $connection,
               $this->_is_perspective($connection),
                                      true,
                                      true);
            }
            $html .='			</div> ';
            $counter++;
         }
      }
      $show_entry = '0';

      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->withPath()){
         $topic_link_items = $item->getLinkItemList(CS_TOPIC_TYPE);
         $link_item = $topic_link_items->getFirst();
         while($link_item){
            if (isset($link_item) and !empty($link_item)){
               $topic_item = $link_item->getLinkedItem($item);
               if ($topic_item->isPathActive()){
                  $path_item_list = $topic_item->getPathItemList();
                  $in_list = $path_item_list->inList($item);
                  if ($in_list){
                     $title = $topic_item->getTitle();
                     $length = mb_strlen($title);
                     if ( $length > 22 ) {
                        $title = mb_substr($title,0,22).'...';
                     }
                     $params['iid'] = $topic_item->getItemID();
                     $noscript_title = ahref_curl($this->_environment->getCurrentContextID(),CS_TOPIC_TYPE,'detail',$params,$title);
                     $title = addslashes(ahref_curl($this->_environment->getCurrentContextID(),CS_TOPIC_TYPE,'detail',$params,$title));
                     $html .='		<div class="netnavigation_panel">     '.LF;
                     $html .= '         <noscript>';
                     $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('TOPIC_PATH').': '.$noscript_title.'</div>';
                     $html .= '         </noscript>';
                     $html .= $this->_getPathItemsAsHTML($topic_item,$item->getItemID(),$path_item_list);
                     $title_string .= ',"'.$this->_translator->getMessage('TOPIC_PATH').': '.$title.'"';
                     $parameter_array = $this->_environment->getCurrentParameterArray();
                     if (isset($parameter_array['path']) and $parameter_array['path'] == $topic_item->getItemID()){
                        $show_entry = $counter;
                     }else{
                        $counter++;
                     }
                  }
               }
            }
            $link_item = $topic_link_items->getNext();
         }
         $item = $this->getItem();
         $type = $item->getItemType();
         if ($type == CS_TOPIC_TYPE and $item->isPathActive()){
            $show_entry = '-1';
         }
      }
      $html .='		<!-- END OF MENU -->';
      $html .='      </div>';
      $html .='      </div>';
      $html .= '<script type="text/javascript">'.LF;
      $title_string = str_replace('</','&COMMSYDHTMLTAG&',$title_string);
      $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),'.$show_entry.',"'.$item->getItemID().'");'.LF;
      $html .= '</script>'.LF;
      return $html;
   }

   function _getPathItemsAsHTML($topic_item,$item_id,$path_item_list){
      $html  ='<div>'.LF;
      $html .='<ol style="list-style-type: decimal; list-style-position:inside; font-size:8pt; padding-left:0px; margin-left:0px; margin-top:2px; padding-bottom:2px;">  '.LF;
      $path_item_list = $topic_item->getPathItemList();
      $path_item = $path_item_list->getFirst();
      while($path_item){
         $path_item_id = $path_item->getItemID();
         $path_item_type = $path_item->getItemType();
         if ($path_item_id == $item_id){
            $html .='<li style="padding-left:5px;"  class="path_list">'.LF;
            $html .= '<a title="'.$path_item->getTitle().'">'.chunkText($path_item->getTitle(),25).'</a>';
         }else{
            $html .='<li style="padding-left:5px;">'.LF;
            $params = array();
            $params['iid'] = $path_item_id;
            $params['path'] = $topic_item->getItemID();

            $user = $this->_environment->getCurrentUser();
            if ($path_item->isNotActivated() and !($path_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                $activating_date = $path_item->getActivatingDate();
                if (strstr($activating_date,'9999-00-00')){
                   $link_creator_text = $path_item->getTitle().' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                }else{
                   $link_creator_text = $path_item->getTitle().' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($path_item->getActivatingDate()).')';
                }
                $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     type2Module($path_item_type),
                                     'detail',
                                     $params,
                                     chunkText($path_item->getTitle(),25),
                                     $link_creator_text,
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     'class="disabled"',
                                     '',
                                     '',
                                     true);
                unset($params);
            }else{
               $html .= ahref_curl($this->_environment->getCurrentContextID(),type2Module($path_item_type),'detail',$params,chunkText($path_item->getTitle(),25),$path_item->getTitle());
               unset($params);
            }
         }
         $html .='</li>'.LF;
         $path_item = $path_item_list->getNext();
      }
      $html .='</ol>'.LF;
      $html .=' </div>'.LF;
      $html .=' </div>';
      return $html;
   }

   function _getLinkedItemsAsHTML ($item, $link_items, $connection, $is_perspective=false, $always=false, $attach_link=false) {
      $current_context = $this->_environment->getCurrentContextItem();
      $user = $this->_environment->getCurrentUserItem();
      $mod = $this->_with_modifying_actions;
      $module = Type2Module($connection);
      $html  ='<div>'.LF;
      $html .='<ul style="list-style-type: circle; font-size:8pt;">  '.LF;
      if (isset($link_items) and $link_items->isEmpty()) {
         $html .= '   <li><a><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></a></li>'.LF;
      } else {
            $count = $link_items->getCount();
            if ( !$is_perspective and $count > 10 ) {  // Only show up to five items
               $limit = 9;
               $count_shown = 1;
            } else {
               $limit = 0;
               $count_shown = 0;
            }
            $link_item = $link_items->getFirst();
            while ( $link_item and $count_shown <= $limit ) {

               // Get link creator

               $link_creator = $link_item->getCreatorItem();
               if ( isset($link_creator) and !$link_creator->isDeleted() ) {
                  $fullname = $link_creator->getFullname();
               } else {
                  $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
               }
               $link_created = $this->_translator->getDateInLang($link_item->getCreationDate());
               $link_creator_text = $this->_translator->getMessage('COMMON_LINK_CREATOR').' '.
                                    $fullname.', '.
                                    $link_created;

               // Create the list entry
               $linked_item = $link_item->getLinkedItem($item);  // Get the linked item
               if ( isset($linked_item) ) {
                  $fragment = '';    // there is no anchor defined by default
                  switch ( $connection ) {
                     case CS_DISCARTICLE_TYPE:
                        $linked_iid = $linked_item->getDiscussionID();
                        $fragment = $linked_item->getItemID();
                        $discussion_manager = $this->_environment->getDiscussionManager();
                        $linked_item = $discussion_manager->getItem($linked_iid);
                        break;
                     case CS_SECTION_TYPE:
                        $linked_iid = $linked_item->getLinkedItemID();
                        $fragment = $linked_item->getItemID();
                        $material_manager = $this->_environment->getMaterialManager();
                        $linked_item = $material_manager->getItem($linked_iid);
                        break;
                     default:
                        $linked_iid = $linked_item->getItemID();
                  }
                  $html .= '   <li>';
                  $params = array();
                  $params['iid'] = $linked_iid;
                  $user = $this->_environment->getCurrentUser();
                  if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                      $activating_date = $linked_item->getActivatingDate();
                      if (strstr($activating_date,'9999-00-00')){
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                      }else{
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($linked_item->getActivatingDate()).')';
                      }
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $module,
                                          'detail',
                                          $params,
                                          chunkText($linked_item->getTitle(),27),
                                          $link_creator_text,
                                          '_self',
                                          $fragment,
                                          '',
                                          '',
                                          '',
                                          'class="disabled"',
                                          '',
                                          '',
                                          true);
                     unset($params);
                  }else{
                     $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $module,
                                          'detail',
                                          $params,
                                          chunkText($linked_item->getTitle(),27),
                                          $link_creator_text,
                                          '_self',
                                          $fragment);
                     unset($params);
                  }
                  $html .= '</li>'.LF;
               }

               // Show all linked perspectives
               if ( $limit > 0 ) {
                  $count_shown++;
               }
               $link_item = $link_items->getNext();
            }
            if ( $limit > 0 ) {
               $html .= '   <li>';
               $params = array();
               $params['ref_iid'] = $item->getItemID();
               $params['mode'] = 'attached';
               $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                    $module,
                                    'index',
                                    $params,
                                    $this->_translator->getMessage('RUBRIC_ALL_ATTACHMENTS', $link_items->getCount()));
               unset($params);
               $html .= '</li>'.LF;
            }
      }
      $html .='</ul> ';
      $html .='				<div style="text-align:right; border-top:0px solid black; padding-bottom:5px; font-size:8pt; padding-right: 3px; font-size:8pt;">'.LF;
      if ( $attach_link ) {
         if ( $user->isUser() and $mod ) {
               $params = array();
               $params['ref_iid'] = $item->getItemID();
               $params['mode'] = 'detailattach';
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $module,
                                         'index',
                                         $params,
                                         $this->_translator->getMessage('COMMON_ATTACH_LINK')).LF;
            unset($params);
         } else {
            $html .= $this->_translator->getMessage('COMMON_ATTACH_LINK').LF;
         }
      }
      $html .='				</div>'.LF;
      $html .=' </div>';
      return $html;
   }



   function _getBrowsingIconsAsHTML($current_item, $pos_number, $count){
     return '';
   }


   function _getItemAsHTML ($item) {
      include_once('functions/error_functions.php');
      trigger_error('cs_detail_view->_getItemAsHTML must be overwritten in subclass', E_USER_ERROR);
   }

   function _getSubItemTitleAsHTML ($subitem, $pos_number) {
      $html = '';
      if ( isset($subitem) ) {
         if ($subitem->isA(CS_USER_TYPE)) {
            $html .= $this->_translator->getMessage('USER_PREFERENCES').LF;
            if ( !empty($this->_sub_item_title_description) ) {
               $html .= ' <span style="font-weight: normal; font-size: small;">('.$this->_text_as_html_short($this->_sub_item_title_description).')</span>'.LF;
            }
         } else {
            $html .= $this->_text_as_html_short($subitem->getTitle());
         }
      } else {
         $html .= 'NO ITEM';
      }
      return $html;
   }

   function _getSubItemAsHTML ($subitem, $pos_number, $spaces=0) {
      include_once('functions/error_functions.php');
      trigger_error('cs_detail_view->_getSubItemAsHTML must be overwritten in subclass', E_USER_ERROR);
   }


   /**
    * Internal methods for printing out connected rubrics.
    * Generally, these methods need not be overridden.
    */
   function _is_perspective ($rubric) {
      $in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_INSTITUTION_TYPE)) ;
      if ($rubric == CS_INSTITUTION_TYPE) {
         $context = $this->_environment->getCurrentContextItem();
         $in_array = $context->withRubric(CS_INSTITUTION_TYPE);
      }
      return $in_array;
   }

   function _has_attach_link ($rubric) {
      return $this->_is_perspective($rubric) or $rubric ==CS_COMMUNITY_TYPE;
   }

   function _is_always_visible ($rubric) {
      return $this->_is_perspective($rubric) or $rubric ==CS_COMMUNITY_TYPE;
   }

   function _getPluginInfosForNetNavigationAsHTML () {
      $html = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getUnderNetNavigationAsHTML')) {
               $retour = $plugin_class->getUnderNetNavigationAsHTML();
               if (isset($retour)) {
                  $html .= $retour;
               }
            }
         }
      }
      return $html;
   }

  function _getRubricInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.$room->getUsageInfoHeaderForRubric($act_rubric).'</div>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      if ($act_user->isModerator()){
         $array = $this->_environment->getCurrentParameterArray();
         $array['back_mod']=$this->_environment->getCurrentModule();
         $array['back_fct']=$this->_environment->getCurrentFunction();
         $html .= '<div style="width:100%; text-align:center;">'
               .'<span class="desc_usage">'.ahref_curl($this->_environment->getCurrentContextID(), 'context', 'info_text_edit', $array,$this->_translator->getMessage('COMMON_EDIT'), '', '', '', '', '')
               .'</span></div>';
      }else{
         $html .= '<div style="width:100%; text-align:center;">'
               .'<span class="disabled" style="font-size: 8pt;">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'
               .'</div>';
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }



   function _getForwardLinkAsHTML () {
      $pos       = $this->getPosition();  // zero-based!
      $ids       = $this->getBrowseIDs(); // zero-based!
      $count_all = count($ids);
      // Determine the position if it is not (correctly) given
      if ( $pos < 0 || $pos >= $count_all ) {
         if ( empty($ids) ) {
            $pos = -1;
         } else {
            $item = $this->getItem();
            if ( isset($item) ) {
               $pos = array_search($item->getItemID(), $ids);
               if ( $pos === NULL or $pos === false ) {
                  $pos = -1;
               }
            } else {
               $pos = -1;
            }
         }
         $this->setPosition($pos);
      }

      // prepare browsing
      if ( $pos > 0 ) { // can I browse to the left / start?
         $browse_left = $ids[$pos-1];
         $browse_start = $ids[0];
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $pos >= 0 and $pos < $count_all-1 ) { // can I browse to the right / end?
         $browse_right = $ids[$pos+1];
         $browse_end = $ids[$count_all-1];
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing arrows to left
      $html = '';
      if ( $browse_start > 0 ) {
         $image = '<span class="bold">&lt;&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_start;
         $params['pos'] = 0;
         $html .= '   '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $image = '<span class="bold">&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_left;
         $params['pos'] = $pos-1;
         $html .= '   '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;</span>'.LF;
      }
      $html .= '|';
      // Show position
      if ( empty($ids) ) {
         $html .= '<span class="bold">&nbsp;'.$this->_translator->getMessage('COMMON_ENTRY').' 1 / 1</span>'.LF;
      } else {
         $html .= '<span class="bold">&nbsp;'.$this->_translator->getMessage('COMMON_ENTRY').' '.($pos+1).' / '.$count_all.'</span>'.LF;
      }
      $html .= '|';

      // create HTML for browsing arrows to left
      if ( $browse_right > 0 ) {
         $image = '<span class="bold">&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_right;
         $params['pos'] = $pos+1;
         $html .= '   '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $image = '<span class="bold">&gt;&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_end;
         $params['pos'] = $count_all-1;
         $html .= '   '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;&gt;</span>'.LF;
      }

      return /*$this->_text_as_html_short(*/$html/*)*/;
   }



   /**
    * Internal method for showing the creator or modificator
    * of an item or subitem.
    */
   function _getCreatorInformationAsHTML ($item, $spacecount=0, $mode = 'short') {
      $html  = '';
      $environment = $this->_environment;
      $context = $environment->getCurrentContextItem();
      $user = $environment->getCurrentUserItem();
      $formal_data = array();
      // Modificator
      $modificator = $item->getModificatorItem();

      // Calculate number / percentage of users who read this item
      if ( $context->isProjectRoom()
           and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                 CS_DISCARTICLE_TYPE,
                                                 CS_ANNOTATION_TYPE)) ) {
         $reader_manager = $environment->getReaderManager();
         $user_manager = $environment->getUserManager();
         $user_list = $user_manager->getAllRoomUsersFromCache($environment->getCurrentContextID());
         $user_count = $user_list->getCount();
         $read_count = 0;
         $read_since_modification_count = 0;
         $current_user = $user_list->getFirst();
         $id_array = array();
         while ( $current_user ) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
         }
         $reader_manager->getLatestReaderByUserIDArray($id_array,$item->getItemID());
         $current_user = $user_list->getFirst();
         while ( $current_user ) {
            $current_reader = $reader_manager->getLatestReaderForUserByID($item->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
               if ( $current_reader['read_date'] >= $item->getModificationDate() ) {
                  $read_count++;
                  $read_since_modification_count++;
               } else {
                  $read_count++;
               }
            }
            $current_user = $user_list->getNext();
         }
         $read_percentage = round(($read_count/$user_count) * 100);
         $read_since_modification_percentage = round(($read_since_modification_count/$user_count) * 100);
      }
      if ( $environment->inProjectRoom() ) {
         if ( isset($modificator) and $modificator->isUser() and !$modificator->isDeleted() ){
            $params = array();
            $params['iid'] = $modificator->getItemID();
            $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                    CS_USER_TYPE,
                                    'detail',
                                    $params,
                                    $modificator->getFullname());
         } elseif ( isset($modificator) and !$modificator->isDeleted() ) {
            $temp_html = '<span class="disabled">'.$modificator->getFullname().'</span>';
         } else {
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      } elseif ( ($user->isUser() and isset($modificator) and  $modificator->isVisibleForLoggedIn())
                 || (!$user->isUser() and isset($modificator) and $modificator->isVisibleForAll())
                 || ( isset($modificator) and $environment->getCurrentUserID() == $modificator->getItemID()) ) {
         $params = array();
         $params['iid'] = $modificator->getItemID();
         if( !$modificator->isDeleted() ){
            if ( !$this->_environment->inPortal() ){
               $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $modificator->getFullname());
            }else{
               $temp_html = '<span class="disabled">'.$modificator->getFullname().'</span>';
            }
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      } else {
         if(isset($modificator) and !$modificator->isDeleted()){
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest() ) {
               $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_USER_NOT_VISIBLE').'</span>';
            } else {
               $temp_html = '<span class="disabled">'.$modificator->getFullname().'</span>';
            }
            unset($current_user_item);
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      }
      if ($item->isNotActivated()){
         $title = '&nbsp;<span class="creator_information_key">'.$this->_translator->getMessage('COMMON_CREATED_BY').':</span> '.$temp_html.', '.$this->_translator->getDateTimeInLangWithoutOClock($item->getCreationDate());
      }else{
         $title = '&nbsp;<span class="creator_information_key">'.$this->_translator->getMessage('COMMON_LAST_MODIFIED_BY').':</span> '.$temp_html.', '.$this->_translator->getDateTimeInLangWithoutOClock($item->getModificationDate());
      }
      $html .='&nbsp;<img id="toggle'.$item->getItemID().'" src="images/more.gif"/>';
      $html .= $title;
      $html .= '<div id="creator_information'.$item->getItemID().'">'.LF;
      $html .= '<div class="creator_information_panel">     '.LF;
      $html .= '<div>'.LF;
      $html .= '<table class="creator_info" summary="Layout">'.LF;


      // Read count (for improved awareness)
      if ( $context->isProjectRoom()
              and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                    CS_DISCARTICLE_TYPE,
                                                    CS_ANNOTATION_TYPE))
         ) {
         $html .= '   <tr>'.LF;
         $html .= '      <td></td>'.LF;
         $html .= '      <td class="key" style="padding-left:8px;">'.LF;
         $html .= '         '.$this->_translator->getMessage('COMMON_READ_SINCE_MODIFICATION').':&nbsp;'.LF;
         $html .= '      </td>'.LF;
         $html .= '      <td class="value">'.LF;
         if ( $read_since_modification_count == 1 ) {
            $html .= ' '.$read_since_modification_count.'&nbsp;'.$this->_translator->getMessage('COMMON_NUMBER_OF_MEMBERS_SINGULAR').''.LF;
         } else {
            $html .= '       '.$read_since_modification_count.'&nbsp;'.$this->_translator->getMessage('COMMON_NUMBER_OF_MEMBERS').''.LF;
         }
         $html .= '      </td>'.LF;
         $html .= '   </tr>'.LF;
      }
      // Creator
      $creator = $item->getCreatorItem();
      if ( $environment->inProjectRoom() ) {
         if ( isset($creator) and $creator->isUser() and !$creator->isDeleted()){
            $params = array();
            $params['iid'] = $creator->getItemID();
            $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $creator->getFullname());
         } elseif ( isset($creator) and !$creator->isDeleted()){
            $temp_html = '<span class="disabled">'.$creator->getFullname().'</span>';
         } else {
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      } elseif ( $user->isUser() and isset($creator) and ($creator->isVisibleForLoggedIn())
                    || (!$user->isUser() and $creator->isVisibleForAll()) ) {
         $params = array();
         $params['iid'] = $creator->getItemID();
         if( !$creator->isDeleted() ){
            if ( !$this->_environment->inPortal() ){
               $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $creator->getFullname());
            }else{
               $temp_html = '<span class="disabled">'.$creator->getFullname().'</span>';
            }
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      } else {
         if(isset($creator) and !$creator->isDeleted()){
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest() ) {
               $temp_html = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
            } else {
               $temp_html = $creator->getFullname();
            }
            unset($current_user_item);
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      }
      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_CREATED_BY').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.$temp_html.', '.$this->_translator->getDateTimeInLang($item->getCreationDate()).LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      // All users who ever edited this item
      $link_modifier_item_manager = $environment->getLinkModifierItemManager();
      $user_manager = $environment->getUserManager();
      $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());
      $modifier_array = array();
      foreach($modifiers as $modifier_id) {
         $modificator = $user_manager->getItem($modifier_id);
         //Links only at accessible contact pages
         if ( $environment->inProjectRoom() ) {
            $params = array();
            if (isset($modificator) and !empty($modificator) and $modificator->isUser() and !$modificator->isDeleted()){
               $params['iid'] = $modificator->getItemID();
               $temp_text = ahref_curl($this->_environment->getCurrentContextID(),
                                  'user',
                                  'detail',
                                  $params,
                                  $modificator->getFullname());
            }elseif(isset($modificator) and  !$modificator->isDeleted()){
                $temp_text = '<span class="disabled">'.$modificator->getFullname().'</span>';
            }else{
                $temp_text = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
            }
            $modifier_array[] = $temp_text;
         } elseif ( ($user->isUser() and isset($modificator) and  $modificator->isVisibleForLoggedIn())
                       || (!$user->isUser() and isset($modificator) and $modificator->isVisibleForAll())
                       || (isset($modificator) and $environment->getCurrentUserID() == $modificator->getItemID()) ) {
            $params = array();
            $params['iid'] = $modificator->getItemID();
            if(!$modificator->isDeleted()){
               if ( !$this->_environment->inPortal() ){
                  $modifier_array[] = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $modificator->getFullname());
               }else{
                  $modifier_array[] = '<span class="disabled">'.$modificator->getFullname().'</span>';
               }
            }else{
               $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
            }
            unset($params);
         } else {
            if(isset($modificator) and !$modificator->isDeleted()){
               $current_user_item = $this->_environment->getCurrentUserItem();
               if ( $current_user_item->isGuest() ) {
                  $modifier_array[] = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
               } else {
                  $modifier_array[] = $modificator->getFullname();
               }
               unset($current_user_item);
            }else{
               $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
            }
         }
      }
      $modifier_array = array_unique($modifier_array);
      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_ALL_MODIFIERS').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.implode(', ',$modifier_array);
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      // Reference number
      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_REFNUMBER').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.$item->getItemID();
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '</table>'.LF;

      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().'",false)</script>';

      //Read percentage gauge (for improved awareness)
      if ( $context->isProjectRoom()
           and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                 CS_DISCARTICLE_TYPE,
                                                 CS_ANNOTATION_TYPE))
         ) {
         $html .= '<table class="gauge-wrapper" summary="Layout"><tr>'.LF;
         $html .= '   <td width="50%">'.$this->_translator->getMessage('COMMON_READ').':</td>'.LF;
         $html .= '   <td width="50%">'.LF;
         $html .= '      <div class="gauge">'.LF;
         if ( $read_percentage >= 5 ) {
            $html .= '         <div class="gauge-bar" style="width:'.$read_percentage.'%;">'.$read_count.'</div>'.LF;
         } else {
            $html .= '         <div class="gauge-bar" style="width:'.$read_percentage.'%">&nbsp;</div>'.LF;
         }
         $html .= '      </div>'.LF;
         $html .= '   </td>'.LF;
         $html .= '</tr></table>'.LF;
      }
         $title = str_replace('</','&COMMSYDHTMLTAG&',$title);

      return $html;
   }


   /**
    * Internal method used for formatting tabular (formal) data.
    */
   function _getFormalDataAsHTML($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" summary="Layout"';
      if ( $clear ) {
         $html .= 'style="clear:both;padding-bottom:10px;"';
      }else{
         $html .= 'style="padding-bottom:10px;"';
      }
      $html .= '>'.LF;
      foreach ($data as $value) {
         $html .= $prefix.'   <tr>'.LF;
         $html .= $prefix.'      <td class="key">'.LF;
         if ( !empty($value[0]) ) {
            $html .= $prefix.'         '.$value[0].':&nbsp;'.LF;
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td class="value">'.LF;
         if ( !empty($value[1]) ) {
            if ( !empty($value[0])) {
               $html .= $prefix.'         '.$value[1].LF;
            }
         }
         $html .= $prefix.'      </td>'.LF;
         $html .= $prefix.'   </tr>'.LF;
      }
      $html .= $prefix.'</table>'.LF;
      return $html;
   }




   function _withAttachedUsers($item){
      return true;
   }

   function getTitle () {
     $retour  = '';
     $retour .= $this->_getTitleAsHTML();
     $this->_display_title = false;
     return $retour;
   }

   function _getAnnotationContentAsHTML($item) {
      $user = $this->_environment->getCurrentUser();
      $annotated_item = $this->getItem();


      $html  = LF.'<!-- BEGIN OF ANNOTATION ITEM -->'.LF;
      $html .= '   <div class="item" style="margin-left:3px;">'.LF;
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
         $html .= $this->getScrollableContent($desc,$item,'',true);
      }
      // Show info about the version the annotation refers to
      $current_version = $annotated_item->getVersionID();
      $annotated_version = $item->getAnnotatedVersionID();
      if ( $current_version > $annotated_version ) {
         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_OLDER_VERSION').')';
      } elseif ( $current_version < $annotated_version ) {
         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_NEWER_VERSION').')';
      } else {
         $text = '';
      }
      if ( !empty ($text) ) {
         $html .= '<p class="disabled" style="margin-left:3px;">'.$text.'</p>'.LF;
      }
      $html .= '   </div>'.LF;

      // Files
      $formal_data = array();
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }
      if ( !empty($formal_data) ) {
         $html .= '<div style="margin: 0px; padding: 0px;">'.$this->_getFormalDataAsHTML($formal_data).'</div>';
         $html .= BRLF;
      }

      $html .= '<!-- END OF ANNOTATION ITEM -->'.LF.LF;
      return $html;
   }

   public function _getFilesForFormalData ($item) {
      $files = array();

      $file_list = $item->getFileList();
      if ( !$file_list->isEmpty() ) {
         $files = array();
         $file = $file_list->getFirst();
         while( $file ) {
            if ( !(isset($_GET['mode']) and $_GET['mode']=='print')
                 or ( isset($_GET['download'])
                      and $_GET['download'] == 'zip'
                    )
               ) {
               if ( ( !isset($_GET['download'])
                      or $_GET['download'] != 'zip'
                    )
                    and
                       in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))
                  ) {
                  $this->_with_slimbox = true;
                  $file_string = '<a href="'.$file->getUrl().'" rel="lightbox[gallery'.$item->getItemID().']">'.
                  $file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName())).'</a> ('.$file->getFileSize().' KB)';
               }else{
                  $file_string = '<a href="'.$file->getUrl().'" target="blank">'.
                  $file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName())).'</a> ('.$file->getFileSize().' KB)';
               }
            }else{
               $file_string = $file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName()));
            }
            $files[] = $file_string;
            $file = $file_list->getNext();

         }
      }
      return $files;
   }


}
?>