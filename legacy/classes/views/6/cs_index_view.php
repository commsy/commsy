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
include_once('functions/date_functions.php');
include_once('functions/misc_functions.php');

/**
 *  generic upper class for CommSy list-views
 */
class cs_index_view extends cs_view {

   var $_clipboard_id_array=array();
   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;
   var $_selected_group = NULL;
   var $_available_groups = NULL;
   var $_selected_tag_array = array();
   var $_selected_buzzword = NULL;
   var $_available_buzzwords = NULL;
   var $_include_mootools = false;

   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = NULL;

   /**
    * string - with search_text as keys
    */
   var $_search_text = NULL;

   var $_show_buzzwords_box = false;
   var $_show_tag_box = false;

   /*
    * array containing all search expressions to be highlighted
    */
   var $_search_array = array();

   /**
    * string - the current sort key
    */
   var $_sort_key = NULL;

   var $_with_checkboxes = true;
   /**
    * array - array of possible sort keys
    */
   var $_sort_keys = NULL;

   /**
    * int - id of item, all shown entries are linked to
    */
   var $_linked_to = NULL;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;
   var $_count_all_shown = NULL;

   /**
    * string - containing the title of the list view
    */
   var $_title = NULL;

   /**
    * array - containing the actions of the list view
    */
   var $_actions = NULL;

   var $_action_title = '';
   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;
   var $_list_of_read_entry_ids = NULL;

   /**
    * string - containing a ahref mark i.e. "http://www.commsy.net/index.html#fragment"
    */
   var $_fragment = NULL;

   var $_checked_ids = array();
   var $_dontedit_ids = array();
   var $_has_checkboxes = false;
   var $_ref_iid = 0;
   var $_ref_user = 0;
   var $_ref_item = 0;
   var $_is_attached_list = false;
   var $_display_title = true;
   var $_with_form_fields = true;
   var $_clipboard_mode = false;
   var $_last_sort_criteria = -1;
   var $_count_headlines = 0;
   var $_additional_selects = false;
   var $_attribute_limit = Null;
   var $_activation_limit = 2;

   var $_colspan = 4;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $this->_with_form_fields = true;
      if ( !empty($params['with_form_fields']) ) {
         $this->_with_form_fields = $params['with_form_fields'];
      }
      cs_view::__construct($params);
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->withTags() ){
         $this->_show_tag_box = true;
      }
      if ( $current_context->withBuzzwords() ){
         $this->_show_buzzwords_box = true;
      }
   }

   /** set title of the list view
    * this method sets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    */
    function setTitle ($value) {
       $this->_title = (string)$value;
    }

   /** get title of the list view
    * this method gets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    */
    function getTitle () {
       $this->_display_title = false;
       return $this->_title;
    }

  // @segment-begin 63086  setClipboardIDArray($cia)/getClipboardIDArray()
  function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }
   // @segment-end 63086

  // @segment-begin 9157 ?setClipboardMode()
  function setClipboardMode() {
      $this->_clipboard_mode = true;
   }
   // @segment-end 9157

   function setColspan($span){
      $this->_colspan = $span;
   }

   function setActivationLimit($limit){
      $this->_activation_limit = $limit;
   }

   // @segment-begin 91360  setFrom($from)/getFrom()-beginning-counter-of-list
   /** set from counter of the list view
    * this method sets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    */
    function setFrom ($from) {
       $this->_from = (int)$from;
    }

   /** get from counter of the list view
    * this method gets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    */
    function getFrom (){
       return $this->_from;
    }
    // @segment-end 91360

   // @segment-begin 46784  setInterval($interval)/getInterval()-shown-interval-of-list-view
   /** set interval counter of the list view
    * this method sets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    */
    function setInterval ($interval) {
       $this->_interval = (int)$interval;
    }

   /** get interval counter of the list view
    * this method gets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    */
    function getInterval () {
       return $this->_interval;
    }
    // @segment-end 46784

   // @segment-begin 49781  setCountAll($count_all)/getCountAll()-lenght-of-whole-list
   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    */
    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    */
    function getCountAll () {
       return $this->_count_all;
    }
    // @segment-end 49781

   // @segment-begin 17374  setCountAllShown($count_all)/getCountAllShown()-lenght-of-whole-shown-list
   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    */
    function setCountAllShown ($count_all) {
       $this->_count_all_shown = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    */
    function getCountAllShown () {
       return $this->_count_all_shown;
    }
    // @segment-end 17374

   /** set description of the list view
    * this method sets the shown description of the list view
    *
    * @param int  $this->_description          description of the shown list
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
    }

   // @segment-begin 96199  setList($list)/getList()-whole-entries-of-list-view
   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list) {
       $this->_list = $list;
    }

   /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function getList () {
       return $this->_list;
    }
    // @segment-end 96199

    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       return $this->_search_text;
    }

    // @segment-begin 8397  setSearchText($search_tex)-sets:_search_text/_search_array
    /** set the value of the search box
    * this method sets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function setSearchText ($search_text){
       $this->_search_text = $search_text;
       $literal_array = array();
       $search_array = array();

       //find all occurances of quoted text and store them in an array
       preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
       //delete this occurances from the original string
       $search_text = preg_replace('~("(.+?)")~u','',$search_text);

       $search_text = preg_replace('~-(\w+)~u','',$search_text);

       //clean up the resulting array from quots
       $literal_array = str_replace('"','',$literal_array[2]);
       //clean up rest of $limit and get an array with entrys
       $search_text = str_replace('  ',' ',$search_text);
       $search_text = trim($search_text);
       $split_array = explode(' ',$search_text);

       //check which array contains search limits and act accordingly
       if ($split_array[0] != '' AND count($literal_array) > 0) {
          $search_array = array_merge($split_array,$literal_array);
       } else {
          if ($split_array[0] != '') {
             $search_array = $split_array;
          } else {
             $search_array = $literal_array;
          }
       }
       $this->_search_array = $search_array;
    }
    // @segment-end 8397

    // @segment-begin 96650  getSortKey()/setSortKey($sort_key)
    /** set the value of the sort box
     * this method sets the sort key of the list
     *
     * @param string  $this->_sort_key
     */
    function setSortKey ($sort_key) {
       $this->_sort_key = (string)$sort_key;
    }

    function setAttributeLimit ($attribute_limit) {
       $this->_attribute_limit = (string)$attribute_limit;
    }


    function getSortKey () {
       return $this->_sort_key;
    }
    // @segment-end 96650

   // @segment-begin 81876  setSelectedGroup($value)/getSelectedGroup()
   function setSelectedGroup ($value) {
       $this->_selected_group =$value;;
   }

   function getSelectedGroup () {
      return $this->_selected_group;
   }
   // @segment-end 81876

   function setAvailableGroups ($group_list) {
      $this->_available_groups = $group_list;
   }

   function getAvailableGroups () {
      return $this->_available_groups;
   }

   function setSelectedInstitution ($institution_id) {
      $this->_selected_institution = (int)$institution_id;
   }

   function getSelectedInstitution () {
      return $this->_selected_institution;
   }

   function setAvailableInstitutions ($institution_list) {
      $this->_available_institutions = $institution_list;
   }
   function getAvailableInstitutions () {
      return $this->_available_institutions;
   }

   function setSelectedTopic ($topic_id) {
      $this->_selected_topic = (int)$topic_id;
   }

   function getSelectedTopic () {
      return $this->_selected_topic;
   }

   function setAvailableTopics ($topic_list) {
      $this->_available_topics = $topic_list;
   }

   function getAvailableTopics () {
      return $this->_available_topics;
   }

    /** set the actions of the list
    * this method sets the actions of the list
    *
    * @param array  $this->_action_array
    */
    function addAction($action){
       $this->_actions[] = $action;
    }

   // @segment-begin 60854  ?setActionTitle($title)
   function setActionTitle($title){
      $this->_action_title = $title;
   }
   // @segment-end 60854

    // @segment-begin 40867  getFragment()/setFragment($value)

    function setFragment ($value) {
       $this->_fragment = (string)$value;
    }

    function getFragment () {
       return (string)$this->_fragment;
    }
    // @segment-end 40867

   // @segment-begin 91772  ??setHasCheckboxes_($mode)
   function setHasCheckboxes ($mode) {
      $this->_has_checkboxes = $mode;
   }
   // @segment-end 91772

   // @segment-begin 51410  ??hasCheckboxes_(),see#91772
   function hasCheckboxes () {
      return (boolean)$this->_has_checkboxes;
   }
   // @segment-end 51410

   // @segment-begin 21229  setCheckedIDs($ids),and-getCheckedIDs()
   function setCheckedIDs ($ids) {
      $this->_checked_ids = $ids;
   }

   function getCheckedIDs () {
      return $this->_checked_ids;
   }
   // @segment-end 21229

   // @segment-begin 51566  getDontEditIDs(),setDontEditIDs($ids)
   function setDontEditIDs ($ids) {
      $this->_dontedit_ids = $ids;
   }

   function getDontEditIDs () {
      return $this->_dontedit_ids;
   }
   // @segment-end 51566

   // @segment-begin 23075  setRefIID_($id)/getRefIID()
   function setRefIID ($id) {
      $this->_ref_iid = $id;
   }

   function getRefIID () {
      return $this->_ref_iid;
   }
   // @segment-end 23075

   // @segment-begin 51160  setRefUser_($id)/getRefUser()
   function setRefUser ($id) {
      $this->_ref_user = $id;
   }

   function getRefUser () {
      return $this->_ref_user;
   }
   // @segment-end 51160

   // @segment-begin 70607  setRefItem($item)/getRefItem()
   function setRefItem ($item) {
      $this->_ref_item = (object)$item;
   }

   function getRefItem () {
     if ($this->_ref_item == '0'){
        $item_manager = $this->_environment->getItemManager();
        $ref_item_type = $item_manager->getItemType($this->getRefIID());
        $ref_item_manager = $this->_environment->getManager($ref_item_type);
        $ref_item = $ref_item_manager->getItem($this->getRefIID());
        $this->_ref_item = $ref_item;
      }
      return $this->_ref_item;
   }
   // @segment-end 70607

   // @segment-begin 21021  setisAttachedList()/isAttachedList()
   function setisAttachedList () {
      $this->_is_attached_list = true;
   }

   function isAttachedList () {
      return $this->_is_attached_list;
   }
   // @segment-end 21021


   // @segment-begin 35732  _getGetParamsAsArray()-cs_index_view-params_as_array
   function _getGetParamsAsArray() {
      $params = array();
      if ( $this->hasCheckboxes() ) {
         $params['ref_iid'] = $this->getRefIID();
         $params['ref_user'] = $this->getRefUser();
         $params['mode'] = $this->_has_checkboxes;
      } elseif ( $this->isAttachedList() ) {
         $params['ref_iid'] = $this->getRefIID();
         $params['ref_user'] = $this->getRefUser();
         $params['mode'] = 'attached';
      }
      $params['from'] = $this->_from;
      $params['interval'] = $this->_interval;
      $params['sort'] = $this->_sort_key;
      $params['search'] = rawurlencode($this->_search_text);
      if ( $this->_environment->inProjectRoom() ) {
         $params['selgroup'] = $this->getSelectedGroup();
      } else {
         $params['selinstitution'] = $this->getSelectedInstitution();
      }
      $params['seltopic'] = $this->getSelectedTopic();
      $params['selbuzzword'] = $this->getSelectedBuzzword();
      $tag_array = $this->_getSelectedTagArray();
      if ( !empty($tag_array) ){
         foreach($tag_array as $key => $tag_id){
            $params['seltag_'.$key] = $tag_id;
         }
      }
      return $params;
   }
   // @segment-end 35732

   function setSelectedBuzzword ($buzzword_id) {
      $this->_selected_buzzword = (int)$buzzword_id;
   }

   function getSelectedBuzzword () {
      return $this->_selected_buzzword;
   }

   function setAvailableBuzzwords ($buzzword_list) {
      $this->_available_buzzwords = $buzzword_list;
   }

   function getAvailableBuzzwords () {
      return $this->_available_buzzwords;
   }

   function getBuzzwordBoxAsHTML(){
      $current_user = $this->_environment->getCurrentUserItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $left_menue_status !='disapear' ) {
        $width = '190';
      } else {
        $width = '225';
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_BUZZWORD_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $buzzword = $this->_available_buzzwords->getFirst();
      if (!$buzzword){
         $html .= '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      $params = $this->_environment->getCurrentParameterArray();
      $selected_id = '';
      if ( isset($params['selbuzzword']) and !empty($params['selbuzzword']) ){
         $selected_id = $params['selbuzzword'];
      }
      while ($buzzword){
         $count = $buzzword->getCountLinks();
         if ($count > 0 and true){
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword->getItemID();
            $temp_text = '';
            $is_selected = false;
            if ( !empty($selected_id ) and $selected_id == $params['selbuzzword'] ) {
               $style_text  = ' style="margin-left:2px; margin-right:2px;';
               $style_text .= ' font-weight:bold;';
               $style_text .= ' color: black;';
               if ($font_size < 14) {
                  $style_text .= ' font-size:16px;"';
               } else {
                  $style_text .= 'font-size:'.$font_size.'px;"';
               }
               $is_selected = true;
            } else {
               $style_text  = 'style="margin-left:2px; margin-right:2px;';
               $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $style_text .= 'font-size:'.$font_size.'px;"';
            }
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $buzzword->getName().LF;
            $title .= '</span> ';
            if (!$is_selected){
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                $this->_environment->getCurrentFunction(),
                                $params,
                                $title,$title).LF;
            }else{
              $html .= $title;
            }

         }
         $buzzword = $this->_available_buzzwords->getNext();
      }
      $html .= '<div style="width:'.$width.'px; text-align:right; padding-right:2px; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['module'] = $this->_environment->getCurrentModule();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      unset($session);
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

   function getTagSizeLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=8, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
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

   function getTagBoxAsHTML(){
      $current_user = $this->_environment->getCurrentUserItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $left_menue_status !='disapear' ) {
        $width = '190';
      } else {
        $width = '230';
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAG_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      $params = $this->_environment->getCurrentParameterArray();
      $selected_id = '';
      $father_id_array = array();
      $tag_array = $this->_getSelectedTagArray();
      $count = (count($tag_array));
      if ($count >0){
         $selected_id = $tag_array[0];
         $tag2tag_manager =  $this->_environment->getTag2TagManager();
         $father_id_array = $tag2tag_manager->getFatherItemIDArray($selected_id);
      }
      $html_text = '';
      $html_text .= $this->_getTagContentAsHTML($root_item,0,$selected_id, $father_id_array);
      if ( empty($html_text) ){
         $html_text .= '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      $html .= $html_text;
      $html .= '<div style="width:'.$width.'px; text-align:right; padding-right:2px; padding-top:5px; font-size:8pt;">';
      if ( ($current_user->isUser() and $this->_with_modifying_actions)
          and ($current_context->isTagEditedByAll() or $current_user->isModerator() ) ) {
         $params = array();
         $params['module'] = $this->_environment->getCurrentModule();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'tag','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.BRLF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }

   function _getTagContentAsHTML($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0) {
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $i = 0;
      while($i <= count($father_id_array)){
        if (isset($params['seltag_'.$i])){
           unset($params['seltag_'.$i]);
        }
        $i++;
      }
      $is_selected = false;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            if ($ebene == 1){
               $html.= '<div style="padding-bottom:5px;">'.LF;
            }else{
               $html.= '<div style="padding-bottom:0px;">'.LF;
            }
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               if ( empty($selected_id) ){
                  $tag2tag_manager = $this->_environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
#                  $font_size = 14 - $this->getTagSizeLogarithmic($count);
                  $font_size = round(13 - (($count*0.2)+$count));
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
               }else{
                  if ( in_array($id,$father_id_array) ){
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     foreach($id_array as $temp_id){
                        if ( !in_array($temp_id,$father_id_array) ){
                           $count ++;
                        }
                     }
                     if( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = 1;
                     }
#                     $font_size = 14;
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight = 'bold';
                     $font_style = 'normal';
                  }else{
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     $found = false;
                     if ( isset($id_array[0]) ){
                        foreach($id_array as $temp_id){
                           if ( !in_array($temp_id,$father_id_array) ){
                              $count ++;
                           }else{
                             $found = true;
                           }
                        }
                        if (!$found){
                           $count = $count + count($father_id_array);
                        }
                     }elseif( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = count($father_id_array);
                     }
#                     $font_size = 14 - $this->getTagSizeLogarithmic($count);
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight='normal';
                     $font_style = 'normal';
                  }
               }
               if ($current_item->getItemID() == $selected_id){
                  $is_selected = true;
                  $font_size = 14;
                  $font_color = 20;
                  $font_style = 'normal';
               }
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               if (($ebene*15) <= 30){
                  $html .= '<div style="padding-left:'.($ebene*15).'px; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
               }else{
                  $html .= '<div style="padding-left:40px; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
               }
               $title = $current_item->getTitle();
               if (!$is_selected){
                  $params['seltag_'.$ebene] = $current_item->getItemID();
                  if( isset($params['seltag']) ){
                     $i = $ebene+1;
                     while( isset($params['seltag_'.$i]) ){
                        unset($params['seltag_'.$i]);
                        $i++;
                     }
                  }
                  $params['seltag'] = 'yes';
                  $html .= '<span class="disabled" style="font-size:'.$font_size.'px;">'.LF;
                  $html .= '-';
                  $html .= '</span>'.LF;
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                $this->_environment->getCurrentFunction(),
                                $params,
                                $title,$title,'','','','','','style="color:'.$color.'"').LF;
               }else{
                  $html .= '<span class="disabled" style="font-size:'.$font_size.'px;">'.LF;
                  $html .= '-';
                  $html .= '</span>'.LF;
                  $html .= '<span style="font-weight:bold; font-style:'.$font_style.'; color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);">'.LF;
                  $html .= $title.LF;
                  $html .= '</span>'.LF;
               }
               $html .= '</div>';
               $html .= $this->_getTagContentAsHTML($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
            }
            $html.='</div>'.LF;
         }
      }
      return $html;
   }


   private function _getSelectedTagArray () {
      return $this->_selected_tag_array;
   }

   public function setSelectedTagArray ($array) {
      $this->_selected_tag_array = $array;
   }

   private function _getTagFormAsHTML ( $tag_list, $depth ) {
      $html = '';
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $left_menue_status !='disapear' ) {
        $width = '170';
      } else {
        $width = '210';
      }
      $width = $width - ($depth*15);
      $margin = $depth*15;
      if ($width < 50){
         $width = 50;
         if ( $left_menue_status !='disapear' ) {
            $margin = 110;
         }else{
            $margin = 150;
         }
      }
      $selected_id = '';
      if ( isset($tag_list) ) {
         $selected_tag_array = $this->_getSelectedTagArray();
         $buzzword = $tag_list->getFirst();
         while ( $buzzword ) {
            if ( in_array($buzzword->getItemID(),$selected_tag_array) ) {
               $selected_id = $buzzword->getItemID();
            }
            $buzzword = $tag_list->getNext();
         }
      }

      $html .= '   <select style="width: '.$width.'px; font-size:8pt; margin-left:'.$margin.'px; margin-bottom:5px;" name="seltag_'.$depth.'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '      <option value="0"';
      if ( empty($selected_id) ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
      if ( isset($tag_list) ) {
         $selected_tag_array = $this->_getSelectedTagArray();
         $buzzword = $tag_list->getFirst();
         while ( $buzzword ) {
            $html .= '      <option value="'.$this->_text_as_form($buzzword->getItemID()).'"';
            if ( in_array($buzzword->getItemID(),$selected_tag_array) ) {
               $html .= ' selected="selected"';
            }
            $html .= '>'.$this->_Name2SelectOption($buzzword->getTitle()).'</option>'.LF;
            $buzzword = $tag_list->getNext();
         }
      }
      if ( $depth == 0 ) {
         $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
         $html .= '      <option value="-1"';
         if ( $selected_id == -1 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
      }
      $html .= '   </select>'.LF;

      if ( !empty($selected_id) ) {
         $tag_manager = $this->_environment->getTagManager();
         $tag_item = $tag_manager->getItem($selected_id);
         if ( isset($tag_item) ) {
            $children_list = $tag_item->getChildrenList();
            if ( isset($children_list) and $children_list->isNotEmpty() ) {
               $html .= $this->_getTagFormAsHTML($children_list,$depth+1);
               unset($children_list);
            }
            unset($tag_item);
         }
         unset($tag_manager);
      }

      return $html;
   }

   function getInfoForHeaderAsHTML() {
      if ( $this->hasCheckboxes() ) {
         $html  = <<<EOD
<script type="text/javascript">
<!--
   function quark(elem) {
      var cookie_value = '';
      if (elem.checked)
         cookie_value = elem.name + '=1'
      else
         cookie_value = elem.name + '=0'
      document.cookie=cookie_value;
   }
-->
</script>
EOD;
      } else {
         $html = '';
      }
      return $html;
   }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      return $html;
   }

   function setAdditionalSelect(){
      $this->_additional_selects = true;
   }

   function _getAttachedItemInfoAsHTML(){
      $html ='';
      $ref_iid = $this->getRefIID();
      $ref_user = $this->getRefUser();
      if (!empty($ref_iid) or !empty($ref_user) ){
         $ref_item = $this->getRefItem();
         $ref_item_type = $ref_item->getItemType();
         if ($ref_item_type == 'user'){
            if ( $this->_environment->inCommunityRoom() ){
               $module = 'contact';
            } else {
               $module = 'user';
            }
            $html .='<span> '.$this->_translator->getMessage('MODIFIED_ITEMS_LISTVIEW_SEPERATOR').' ';
            $params = array();
            $params['iid'] = $ref_user;
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $module,
                                'detail',
                                $params,
                                $ref_item->getFullName(),
                                '',
                                '',
                                $this->getFragment()
                               );
            unset($params);
            $html .= '</span>'.LF;
         } elseif ( $ref_item_type == CS_ANNOTATION_TYPE ) {
            $ref_item2 = $ref_item->getLinkedItem();
            $module = type2module($ref_item2->getItemType());
            $html .='<span> '.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').' ';
            $params = array();
            $params['iid'] = $ref_item2->getItemID();
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $module,
                                'detail',
                                $params,
                                $ref_item->getTitle(),
                                '',
                                '',
                                $ref_item->getItemID()
                               );
            unset($params);
            $html .= '</span>'.LF;
         } elseif ( $ref_item_type == CS_SECTION_TYPE ) {
            $ref_item2 = $ref_item->getLinkedItem();
            $html .='<span> '.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').' ';
            $params = array();
            $params['iid'] = $ref_item2->getItemID();
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_MATERIAL_TYPE,
                                'detail',
                                $params,
                                $ref_item->getTitle(),
                                '',
                                '',
                                $ref_item->getItemID()
                               );
            unset($params);
            $html .= '</span>'.LF;
         } elseif ( $ref_item_type == CS_DISCARTICLE_TYPE ) {
            $ref_item2 = $ref_item->getLinkedItem();
            $html .='<span> '.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').' ';
            $params = array();
            $params['iid'] = $ref_item2->getItemID();
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DISCUSSION_TYPE,
                                'detail',
                                $params,
                                $ref_item->getTitle(),
                                '',
                                '',
                                $ref_item->getItemID()
                               );
            unset($params);
            $html .= '</span>'.LF;
         } else {
            $module = type2module($ref_item_type);
            $html .='<span> '.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').' ';
            $params = array();
            $params['iid'] = $ref_iid;
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $module,
                                'detail',
                                $params,
                                $ref_item->getTitle(),
                                '',
                                '',
                                $this->getFragment()
                               );
            unset($params);
            $html .= '</span>'.LF;
         }
      }
      return $html;
   }


   function _getRestrictionTextAsHTML(){
      $ref_user = $this->getRefUser();
      $ref_iid = $this->getRefIID();
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['seltag'])
       or isset($params['selbuzzword'])
       or isset($params['selgroup'])
       or isset($params['selinstitution'])
       or isset($params['seltopic'])
       or isset($params['search'])
       or isset($params['selactivatingstatus'])
       or isset($this->_activation_limit)
       or (!empty($ref_user) and isset($params['mode']) and $params['mode'] == 'attached')
       or (!empty($ref_iid) and isset($params['mode']) and $params['mode'] == 'attached')
       or $this->_additional_selects
       ){
         $html_text ='';
         if ( !empty($ref_user) ){
            $html_text .='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('MODIFIED_ITEMS_LISTVIEW_SEPERATOR').'</span> ';
            $ref_item = $this->getRefItem();
            $link_params = array();
            $link_params['iid'] = $ref_user;
            $title = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_USER_TYPE,
                                'detail',
                                $link_params,
                                chunkText($ref_item->getFullName(),15),
                                '',
                                '',
                                $this->getFragment()
                               );
            unset($link_params);
            $html_text .= '<span><a title="'.$ref_item->getFullName().'">'.$title.'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['ref_user']);
            unset($new_params['mode']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
         if ( isset($params['search']) and !empty($params['search']) ){
            $html_text .='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_SEARCH_RESTRICTION').':</span> ';
            $html_text .= '<span><a title="'.urldecode($params['search']).'">'.chunkText(urldecode($params['search']),13).'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['search']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
         $html .= $this->getAdditionalRestrictionTextAsHTML();
         if ( isset($params['selgroup']) and !empty($params['selgroup']) ){
            $html_text ='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_GROUP').':</span> ';
            if ($params['selgroup'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $group_manager = $this->_environment->getGroupManager();
               $group_item = $group_manager->getItem($params['selgroup']);
               $link_params = array();
               $link_params['iid'] = $group_item->getItemID();
               $html_text .=  ahref_curl($this->_environment->getCurrentContextID(),
                                CS_GROUP_TYPE,
                                'detail',
                                $link_params,
                                chunkText($group_item->getTitle(),17),$group_item->getTitle()).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selgroup']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
         if ( isset($params['selinstitution']) and !empty($params['selinstitution']) ){
            $html_text ='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_INSTITUTION').':</span> ';
            if ($params['selinstitution'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $institution_manager = $this->_environment->getInstitutionManager();
               $institution_item = $institution_manager->getItem($params['selinstitution']);
               $link_params = array();
               $link_params['iid'] = $institution_item->getItemID();
               $html_text .=  ahref_curl($this->_environment->getCurrentContextID(),
                                CS_INSTITUTION_TYPE,
                                'detail',
                                $link_params,
                                chunkText($institution_item->getTitle(),14),$institution_item->getTitle()).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selinstitution']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
         if ( isset($params['seltopic']) and !empty($params['seltopic']) ){
            $html_text ='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_TOPIC').':</span> ';
            if ($params['seltopic'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $topic_manager = $this->_environment->getTopicManager();
               $topic_item = $topic_manager->getItem($params['seltopic']);
               $link_params = array();
               $link_params['iid'] = $topic_item->getItemID();
               $html_text .=  ahref_curl($this->_environment->getCurrentContextID(),
                                CS_TOPIC_TYPE,
                                'detail',
                                $link_params,
                                chunkText($topic_item->getTitle(),17),$topic_item->getTitle()).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['seltopic']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
         if ( isset($params['selbuzzword'])  and !empty($params['selbuzzword']) ){
            $html_text  ='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_BUZZWORD_RESTRICTION').':</span> ';
            $buzzword_manager = $this->_environment->getBuzzwordManager();
            if ($params['selbuzzword'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $buzzword_item = $buzzword_manager->getItem($params['selbuzzword']);
               $html_text .= '<span><a title="'.$buzzword_item->getName().'">'.chunkText($buzzword_item->getName(),12).'</a></span>';
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selbuzzword']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;

            $html_text .='</div>';
            $html .= $html_text;
         }
         if ( isset($params['seltag']) ){
            $i = 0;
            while ( !isset($params['seltag_'.$i]) ){
               $i++;
            }
            $tag_manager = $this->_environment->getTagManager();
            $tag_item = $tag_manager->getItem($params['seltag_'.$i]);
            $html_text  ='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_TAG_RESTRICTION').':</span> ';
            $html_text .= '<span>'.chunkText($tag_item->getTitle(),12).'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['seltag_'.$i]);
            unset($new_params['seltag']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
      }
      return $html;
   }


   function _getIndexPageHeaderAsHTML(){
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      // @segment-end 16772
      // @segment-begin 61726 complete:asHTML():style_cell_1:1
     $html .='<div class="content_display_width">'.LF;
     $html .='<div>'.LF;
      // @segment-end 17331
      // @segment-begin 64852 asHTML():display_rubrik_title/rubrik_clipboard_title
      $tempMessage = '';
      switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ) {
         case 'ANNOUNCEMENT':
            $tempMessage = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
            break;
         case 'DATE':
            $tempMessage = $this->_translator->getMessage('DATE_INDEX');
            break;
         case 'DISCUSSION':
            $tempMessage = $this->_translator->getMessage('DISCUSSION_INDEX');
            break;
         case 'INSTITUTION':
            $tempMessage = $this->_translator->getMessage('INSTITUTION_INDEX');
            break;
         case 'GROUP':
            $tempMessage = $this->_translator->getMessage('GROUP_INDEX');
            break;
         case 'MATERIAL':
            $tempMessage = $this->_translator->getMessage('MATERIAL_INDEX');
            break;
         case 'MYROOM':
            $tempMessage = $this->_translator->getMessage('MYROOM_INDEX');
            break;
         case 'PROJECT':
            $tempMessage = $this->_translator->getMessage('PROJECT_INDEX');
            break;
         case 'TODO':
            $tempMessage = $this->_translator->getMessage('TODO_INDEX');
            break;
         case 'TOPIC':
            $tempMessage = $this->_translator->getMessage('TOPIC_INDEX');
            break;
         case 'USER':
            $tempMessage = $this->_translator->getMessage('USER_INDEX');
            break;
         default:
            $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_index_view(685) ');
            break;
      }
      if ($this->_clipboard_mode){
          $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('CLIPBOARD_HEADER').' ('.$tempMessage.')';
      }elseif ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('COMMON_ASSIGN').' ('.$tempMessage.')';
      }else{
          $html .= '<h2 class="pagetitle">'.$tempMessage;
      }

      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   // @segment-begin 16772  complete:asHTML():view_table_begin/style
   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .= $this->_getIndexPageHeaderAsHTML();

      if(!$this->_clipboard_mode and !(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div style="float:right; width:27%; padding-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;

         $current_context = $this->_environment->getCurrentContextItem();
         $list_box_conf = $current_context->getListBoxConf();
         $first_box = true;
         $title_string ='';
         $desc_string ='';
         $config_text ='';
         $size_string = '';
         $html .='<div id="commsy_panels">'.LF;
            $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
            $tempMessage = '';
            switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ) {
               case 'ANNOUNCEMENT':
                  $tempMessage = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
                  break;
               case 'DATE':
                  $tempMessage = $this->_translator->getMessage('DATE_INDEX');
                  break;
               case 'DISCUSSION':
                  $tempMessage = $this->_translator->getMessage('DISCUSSION_INDEX');
                  break;
               case 'INSTITUTION':
                  $tempMessage = $this->_translator->getMessage('INSTITUTION_INDEX');
                  break;
               case 'GROUP':
                  $tempMessage = $this->_translator->getMessage('GROUP_INDEX');
                  break;
               case 'MATERIAL':
                  $tempMessage = $this->_translator->getMessage('MATERIAL_INDEX');
                  break;
               case 'MYROOM':
                  $tempMessage = $this->_translator->getMessage('MYROOM_INDEX');
                  break;
               case 'PROJECT':
                  $tempMessage = $this->_translator->getMessage('PROJECT_INDEX');
                  break;
               case 'TODO':
                  $tempMessage = $this->_translator->getMessage('TODO_INDEX');
                  break;
               case 'TOPIC':
                  $tempMessage = $this->_translator->getMessage('TOPIC_INDEX');
                  break;
               case 'USER':
                  $tempMessage = $this->_translator->getMessage('USER_INDEX');
                  break;
               default:
                  $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_index_view(1455) ');
                  break;
            }
            $html .= $this->_getListInfosAsHTML($tempMessage);
            $html .='</div>'.LF;
         if ( !strstr($list_box_conf,'actions_nodisplay') ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_ACTIONS').'"';
            $desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            if ($this->_environment->getCurrentModule() != 'campus_search'){
               if ( strstr($list_box_conf,'actions_short') ){
                  $config_text .=$additional_text.'true';
               }else{
                  $config_text .=$additional_text.'false';
               }
            }else{
                  $config_text .=$additional_text.'false';
            }
            $html .= $this->_getListActionsAsHTML();
            $html .='</div>'.LF;
         }
         if ( !strstr($list_box_conf,'search_nodisplay') ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            // @segment-end 75369
            // @segment-begin 22698 asHTML:no_clipboard_mode+mode=""_or_mode><print:select-box-right-side
            if ($this->_environment->getCurrentModule() != 'campus_search'){
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'"';
            }else{
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('CAMPUS_SEARCH_INDEX').'"';
            }$desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            $parameter_array = $this->_environment->getCurrentParameterArray();
            if ($this->_environment->getCurrentModule() != 'campus_search'){
               if (isset($parameter_array['mode'])
                   and $parameter_array['mode']=='list_actions'
                   and ( (isset($parameter_array['search']) and $parameter_array['search']!='') or
                      (isset($parameter_array['selgroup']) and $parameter_array['selgroup']!='0') or
                      (isset($parameter_array['selinstitution']) and $parameter_array['selinstitution']!='0') or
                      (isset($parameter_array['seltopic']) and $parameter_array['seltopic']!='0') or
                      (isset($parameter_array['selactivatingstatus']) and $parameter_array['selactivatingstatus']!='0') or
                      (isset($parameter_array['selstatus']) and $parameter_array['selstatus']!='0')
                   )
                ){
                   $config_text .= $additional_text.'true';
               }elseif ( strstr($list_box_conf,'search_short') ){
                  $config_text .= $additional_text.'true';
               }else{
                  $config_text .= $additional_text.'false';
               }
            }else{
               $config_text .= $additional_text.'true';
            }
            $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
            $html .= $this->_getListSelectionsAsHTML();
            $html .= '</form>'.LF;
         }
         if ( $this->_show_buzzwords_box
              and !strstr($list_box_conf,'buzzwords_nodisplay')
              and ($_GET['mod']==CS_MATERIAL_TYPE
                 or $_GET['mod']==CS_ANNOUNCEMENT_TYPE
                 or $_GET['mod']==CS_DISCUSSION_TYPE
                 or $_GET['mod']==CS_TODO_TYPE
                 or $_GET['mod']==CS_DATE_TYPE
                 or $_GET['mod']== 'campus_search')
            ) {
            if ( $first_box ) {
               $first_box = false;
               $additional_text = '';
            } else {
               $additional_text = ',';
            }
            $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_BUZZWORD_BOX').'"';
            $desc_string .= $additional_text.'""';
            if ( isset($this->_available_buzzwords) ) {
               $size_string .= $additional_text.'"'.$this->_available_buzzwords->getCount().'"';
            } else {
               $size_string .= $additional_text.'"10"';
            }
            $parameter_array = $this->_environment->getCurrentParameterArray();
            if ( isset($parameter_array['selbuzzword']) and !empty($parameter_array['selbuzzword']) ) {
               $config_text .= $additional_text.'true';
            } elseif ( strstr($list_box_conf,'buzzwords_short') ){
               $config_text .= $additional_text.'true';
            } else {
               $config_text .= $additional_text.'false';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->getBuzzwordBoxAsHTML();
            $html .= '</div>'.LF;
         }
         if ( $this->_show_tag_box
              and !strstr($list_box_conf,'tags_nodisplay')
              and ($_GET['mod']==CS_MATERIAL_TYPE
                 or $_GET['mod']==CS_ANNOUNCEMENT_TYPE
                 or $_GET['mod']==CS_DISCUSSION_TYPE
                 or $_GET['mod']==CS_TODO_TYPE
                 or $_GET['mod']==CS_DATE_TYPE
                 or $_GET['mod']== 'campus_search')
            ) {
            if ( $first_box ) {
               $first_box = false;
               $additional_text ='';
            } else {
               $additional_text =',';
            }
            $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_TAG_BOX').'"';
            $desc_string .= $additional_text.'""';
            $tag_manager = $this->_environment->getTagManager();
            $tag_manager->setContextLimit($this->_environment->getCurrentContextID());
            $tag_manager->select();
            $size = $tag_manager->getCountAll();
            unset($tag_manager);
            if ( !empty($size) ) {
               $size_string .= $additional_text.'"'.$size.'"';
            } else {
               $size_string .= $additional_text.'"10"';
            }
            $parameter_array = $this->_environment->getCurrentParameterArray();
            if (isset($parameter_array['seltag']) and !empty($parameter_array['seltag']) ){
               $config_text .= $additional_text.'true';
            }elseif ( strstr($list_box_conf,'tags_short') ){
               $config_text .= $additional_text.'true';
            }else{
               $config_text .= $additional_text.'false';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->getTagBoxAsHTML();
            $html .= '</div>'.LF;
         }
         // @segment-end 22698
         // @segment-begin 32180 asHTML():no_clipboard_mode+mode=""_or_mode><print:??nothing_done
         // @segment-end 32180
         // @segment-begin 13018 asHTML():no_clipboard_mode+mode=""_or_mode><print:display_rubrik_info
         $user = $this->_environment->getCurrentUserItem();
         $room = $this->_environment->getCurrentContextItem();
         $rubric_info_array = $room->getUsageInfoArray();
         if (!is_array($rubric_info_array)) {
            $rubric_info_array = array();
         }


         $info_text = $this->_getRubricInfoAsHTML($this->_environment->getCurrentModule());
         if (!strstr($info_text, $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'))
             and !strstr($info_text, $this->_translator->getMessage('USAGE_INFO_COMING_SOON'))
         ){
         if ( !strstr($list_box_conf,'usage_nodisplay') ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $title_string .= $additional_text.'"'.$room->getUsageInfoHeaderForRubric($this->_environment->getCurrentModule()).'"';
            $desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            if ( strstr($list_box_conf,'usage_short') ){
               $config_text .= $additional_text.'true';
            }else{
               $config_text .= $additional_text.'false';
            }
            $html .= $this->_getRubricInfoAsHTML($this->_environment->getCurrentModule());
            $html .='</div>'.LF;
         }
         }

         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'),Array(),null,null);'.LF;
         $html .= '</script>'.LF;
      }
      // @segment-end 13018
      // @segment-begin 54331 asHTML():_clipboard_mode+mode><print:display_clipboard_actions=emty+rubric_info
      elseif(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div style="float:right; width:27%; padding-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="margin-bottom:1px;">'.LF;
         $html .= $this->_getRubricClipboardInfoAsHTML($this->_environment->getCurrentModule());
         $html .='</div>'.LF;
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='</div>'.LF;
         $html .='<div class="content_display_width" style="padding-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='</div>'.LF;
         $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      }
      // @segment-end 64852
      // @segment-begin 53574 asHTML():mode=""_or_mode><print:display_page_navigation
      // @segment-end 53574
      // @segment-begin 26184 asHTML():begin-form_entry_index
      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" method="post">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
      }
      // @segment-end 26184
      // @segment-begin 60742 asHTML():Titlebox-for-entry-index-with-sort-links
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      // @segment-end 60742
      // @segment-begin 46388 asHTML():display_index_entries(dependent_on:_clipboard_mode)
      if (!$this->_clipboard_mode){
         $html .= $this->_getContentAsHTML();
      }else{
         $html .= $this->_getClipboardContentAsHTML();
      }
      // @segment-end 46388
      // @segment-begin 18559 asHTML():action-box-under-entries-index
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= $this->_getTablefootAsHTML();
      }
      $html .= '</table>'.LF;
      $html .= '</form>'.LF;
      // @segment-end 54331
      // @segment-begin 54817 asHTML():view_table_end/return
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }






   // @segment-end 54817

   function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
     $params = $this->_environment->getCurrentParameterArray();
     $params['mode']='print';
     $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              'index',
                              $params,$this->_translator->$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')
                             ).BRLF;
     $html .= '</div>'.LF;
     $html .= '</div>'.LF;

     return $html;
   }

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= '<div class="index_forward_links" style="white-space:nowrap;">'.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" >'.LF;

      $html .= '<div class="infocolor">'.$this->_getIntervalLinksFirstLineAsHTML().' '.$this->_getIntervalLinksSecondLineAsHTML().'</div>'.LF;
      $connection = $this->_environment->getCurrentModule();
      $text = '';
      switch ( mb_strtoupper($connection, 'UTF-8') ){
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
            $text .= $this->_translator->getMessage('COMMON_ROOMS');
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
            $text .= $this->_translator->getMessage('COMMON_USER_INDEX');
            break;
         case 'ACCOUNT':
            $text .= $this->_translator->getMessage('COMMON_ACCOUNTS');
            break;
         default:
            $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(1762) ';
            break;
      }
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ALL_LIST_ENTRIES',$text).':</span> '.$this->_count_all.''.BRLF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_LIST_SHOWN_ENTRIES').' </span>';
      $html .= '<span class="index_description">'.$this->_getDescriptionAsHTML().'</span>'.BRLF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }

   function _getClipboardActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      return $html;
   }


  function _getRubricInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$room->getUsageInfoHeaderForRubric($act_rubric).'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

  function _getRubricClipboardInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.$room->getUsageInfoHeaderForRubric($act_rubric).'</div>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

  function _getListSelectionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $html  = '';
      // Search / select form
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['seltag']) ){
         $html .= '   <input type="hidden" name="seltag" value="'.$params['seltag'].'"/>'.LF;
      }
      if ( isset($params['selbuzzword']) ){
         $html .= '   <input type="hidden" name="selbuzzword" value="'.$params['selbuzzword'].'"/>'.LF;
      }
      if ( isset($params['selgroup']) ){
         $html .= '   <input type="hidden" name="selgroup" value="'.$params['selgroup'].'"/>'.LF;
      }
      if ( isset($params['selinstitution']) ){
         $html .= '   <input type="hidden" name="selinstitution" value="'.$params['selinstitution'].'"/>'.LF;
      }
      if ( isset($params['seltopic']) ){
         $html .= '   <input type="hidden" name="seltopic" value="'.$params['seltopic'].'"/>'.LF;
      }
      if ( isset($params['attribute_limit']) ){
         $html .= '   <input type="hidden" name="attribute_limit" value="'.$params['attribute_limit'].'"/>'.LF;
      }
      if ( $this->hasCheckboxes() ) {
         $html .= '   <input type="hidden" name="mode" value="'.$this->_text_as_form($this->_has_checkboxes).'"/>'.LF;
      }
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
      }
      if ( $this->isAttachedList() ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
         $html .= '   <input type="hidden" name="mode" value="attached"/>'.LF;
      }
      $session = $this->_environment->getSession();
      if ( !$session->issetValue('cookie')
           or $session->getValue('cookie') == '0' ) {
         $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
      }
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
         $width = '124';
      } else {
        $width = '157';
      }
      $html .= '<input style="width:'.$width.'px; font-size:10pt; margin-bottom:5px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
      $html .= '<input style="margin-bottom:5px; font-size:10pt; width:55px;" name="option" value="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'" type="submit"/>'.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      return $html;
   }



   // @segment-begin 68626 _getViewActionsAsHTML()-actions-for-action-box-under-annoucement-index
   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($user->isModerator()){
            $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }
      }else{
         $html .= '   <option value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   // @segment-end 68626




   function _getComplexFormAsHTML() {
      return '';
   }


  /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      // @segment-begin 39076 _getDescriptionAsHTML():count_all>count_all_shown:5_possible_messages_like"shown...(count_all)"
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('COMMON_NO_ENTRIES');
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('COMMON_ONE_ENTRY');
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('COMMON_X_ENTRIES', $count_all_shown);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('COMMON_X_FROM_Z', $count_all_shown);
         } else {
            if ( $from + $interval -1 <= $count_all ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z',
                                                          $from,
                                                          $to,
                                                          $count_all_shown
                                                         );
         }
      // @segment-end 96579

      // @segment-begin 24649 _getDescriptionAsHTML():add_description=(numbers_of_displayed_entries+amount_all_entries)_to_return
      $html ='';

      if ( !empty($description) ) {
         $html .= $description;
      }
      // @segment-end 24649
      // @segment-begin 88089 _getDescriptionAsHTML():call_getAttachedItemInfoAsHTML():display_attached_info_under_numbers_of_displayed_entries+amount_all_entries


      return /*$this->_text_as_html_short(*/ $html /*)*/;
   }
   // @segment-end 88089

   function _getTableheadAsHTML () {
   }


   function _getTablefootAsHTML () {
   }




   function _getForwardLinkAsHTML () {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
      }
      unset($params['select']);
      if ($interval > 0) {
         if ($count_all_shown != 0) {
            $num_pages = ceil($count_all_shown / $interval);
         } else {
            $num_pages = 1;
         }
         $act_page  = ceil(($from + $interval - 1) / $interval);
      } else {
         $num_pages = 1;
         $act_page  = 1;
      }

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all_shown ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all_shown - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing icons
      $html = '';
      if ( $browse_start > 0 ) {
         $params['from'] = $browse_start;
         $image = '<span class="bold">&lt;&lt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module,
                                         $this->_function,
                                         $params, $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $params['from'] = $browse_left;
         $image = '<span class="bold">&lt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module, $this->_function,
                                         $params, $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;</span>'.LF;
      }
      $html .= '|';
      $html .= '<span class="bold">&nbsp;'.$this->_translator->getMessage('COMMON_PAGE').' '.$act_page.' / '.$num_pages.'&nbsp;</span>'.LF;
      $html .= '|';
      if ( $browse_right > 0 ) {
         $params['from'] = $browse_right;
         $image = '<span class="bold">&gt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module,
                                         $this->_function,
                                         $params,
                                         $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $params['from'] = $browse_end;
         $image = '<span class="bold">&gt;&gt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module, $this->_function,
                                         $params,
                                         $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;&gt;</span>'.LF;
      }

      return $html;
   }




   // @segment-begin 36250  _getIntervalLinksFirstLineAsHTML():interval_label
   function _getIntervalLinksFirstLineAsHTML() {
      $html = $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
      return $html;
   }
   // @segment-end 36250

   // @segment-begin 80830  _getIntervalLinksSecondLineAsHTML():interval_links
   function _getIntervalLinksSecondLineAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
      }
      unset($params['select']);
      $html  = '';
      if ( $this->_interval == 20 ) {
         $html .= '<span style="color:black">20</span>';
      } else {
         $params['interval'] = 20;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '20',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | <span style="color:black">50</span>';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '50',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | <span style="color:black">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL').'</span>';
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'),
                                   '',
                                   '',
                                   ''
                                  );
      }

      return $html;
   }
   // @segment-end 80830




   function _getIntervalLinksAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      $html = $this->_translator->getMessage('COMMON_PAGE_ENTRIES').': ';
      if ( $this->_interval == 10 ) {
         $html  .= '10';
      } else {
         $params['interval'] = 10;
         $html  .= ahref_curl($this->_environment->getCurrentContextID(),
                              $this->_module,
                              $this->_function,
                              $params,
                              '10',
                              '',
                              '',
                              ''
                             );
      }

      if ( $this->_interval == 20 ) {
         $html .= ' | 20';
      } else {
         $params['interval'] = 20;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '20',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | 50';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '50',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | '.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL');
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module, $this->_function,
                                   $params,
                                   $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'),
                                   '',
                                   '',
                                   ''
                                  );
      }

      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getContentAsHTML() {
      $html = '';
      $list = $this->_list;
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
         $this->_with_checkboxes = false;
      }
      if ( !isset($list) || $list->isEmpty() ) {
         return '<tr  class="list"><td class="odd" colspan="'.$this->_colspan.'" style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }
      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getClipboardContentAsHTML() {
      $html = '';
      $list = $this->_list;
      if ( !isset($list) || $list->isEmpty() ) {
         return '<tr><td style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }
      return $html;
   }


   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item, $pos = 0) {
   //must be overwritten
   }

   // @segment-begin 55311 _getItemModificator($item):modificator-of-entry;uses#53255,51609
   /** get the modificator-name of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modificator of an item in a standardized
    * manner.
    *
    * @return string modificator_fullname
    *
    * @author CommSy Development Group
    */
   function _getItemModificator($item){
      $modificator = $item->getModificatorItem();
      if ( isset($modificator) and !$modificator->isDeleted()){
         $current_user_item = $this->_environment->getCurrentUserItem();
         if ( $current_user_item->isGuest() and $modificator->isVisibleForLoggedIn()) {
            $fullname = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
         } else {
            $fullname = $modificator->getFullName();
         }
         unset($current_user_item);
      } else {
         $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
      }
      $fullname = $this->_compareWithSearchText($fullname);
      return $this->_text_as_html_short($fullname);
   }
   // @segment-end 55311

   // @segment-begin 82455 _getItemModificationDate($item):modification-date-of-entry;uses#53255,51609
   /** get the modification-date of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modification date of an item in a
    * standardized manner.
    *
    * @return string modification_date
    *
    * @author CommSy Development Group
    */
   function _getItemModificationDate($item){
      $moddate = $item->getModificationDate();
      if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00')){
         $mod_date = $this->_translator->getDateInLang($item->getModificationDate());
      } else {
         $mod_date = $this->_translator->getDateInLang($item->getCreationDate());
      }
      $mod_date = $this->_compareWithSearchText($mod_date);
      return $this->_text_as_html_short($mod_date);
   }
   // @segment-end 82455


   // @segment-begin 53255 _compareWithSearchText($value):format-string
   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    *
    * @author CommSy Development Group
    */
   function _compareWithSearchText($value){
      if ( !empty($this->_search_array) ){
         foreach ($this->_search_array as $search_text) {
            if ( mb_stristr($value,$search_text) ) {
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu','*$0*',$value);
            }
         }
      }
      return $value;
   }


   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    *
    * @author CommSy Development Group
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
            $step_item = $step_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_STEP').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_STEP').']</span>';
         } else {
            $info_text = '';
         }
      } else {
         $info_text = '';
      }
      return $info_text;
   }


   // @segment-end 53255
   // @segment-begin 50746 _getItemAnnotationChangeStatus($item):modification-state-of-item-annotation
   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    *
    * @author CommSy Development Group
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
            $anno_item = $annotation_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_ANNOTATION').']</span>';
         } else {
            $info_text = '';
         }
      } else {
         $info_text = '';
      }
      return $info_text;
   }


   // @segment-end 50746

   // This should go somewhere else (in functions ...)
   // this should be deleted !!! (TBD)
   function _getGetParamsFromArray($params) {
      $text = '';
      $first = true;
      foreach ( $params as $key => $val ) {
         if ( $first ) {
            $first = false;
         } else {
            $text .= '&';
         }
         $text .= $key.'='.$val;
      }
      return $text;
   }

   function _getGetParamsAsURL() {
      return $this->_getGetParamsFromArray($this->_getGetParamsAsArray());
   }

   function _getGetParamsAsHiddenfields($exclude=0) {
      $params = $this->_getGetParamsAsArray();
      if ( !empty($exclude) ) {
         foreach ( $exclude as $value ) {
            if ( isset($params[$value]) ) {
               unset($params[$value]);
            }
         }
      }
      $text = '';
      foreach ( $params as $key => $val ) {
         $text .= '<input type="hidden" name="'.$key.'" value="'.$this->_text_as_form($val).'"/>'.LF;
      }
      return $text;
   }

   function _Name2SelectOption ($name) {
     $length = 70;
     if ( mb_strlen($name)>$length ) {
         $name = chunkText($name,$length);
     }
     return $name;
   }

   // @segment-begin 86697  setAvailableRubric($rubric,$rubric_list)
   function setAvailableRubric($rubric,$rubric_list){
      switch($rubric){
         case CS_TOPIC_TYPE: $this->setAvailableTopics($rubric_list);break;
         case CS_INSTITUTION_TYPE: $this->setAvailableInstitutions($rubric_list);break;
         case CS_GROUP_TYPE: $this->setAvailableGroups($rubric_list);break;
      }
   }
   // @segment-end 86697

   // @segment-begin 28490  setSelectedRubric($rubric,$value)
   function setSelectedRubric($rubric,$value){
      switch($rubric){
         case CS_TOPIC_TYPE: $this->setSelectedTopic($value);break;
         case CS_INSTITUTION_TYPE: $this->setSelectedInstitution($value);break;
         case CS_GROUP_TYPE: $this->setSelectedGroup($value);break;
      }
   }
   // @segment-end 28490

   function getAvailableRubric($rubric){
      switch($rubric){
         case CS_TOPIC_TYPE: return $this->getAvailableTopics();break;
         case CS_INSTITUTION_TYPE: return $this->getAvailableInstitutions();break;
         case CS_GROUP_TYPE: return $this->getAvailableGroups();break;
      }
   }

   function getSelectedRubric($rubric){
      switch($rubric){
         case CS_TOPIC_TYPE: return $this->getSelectedTopic();break;
         case CS_INSTITUTION_TYPE: return $this->getSelectedInstitution();break;
         case CS_GROUP_TYPE: return $this->getSelectedGroup();break;
      }
   }


   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      return '';
   }

   function _getAdditionalFormFieldsAsHTML ($field_length=14.5) {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
         $width = '190';
      }else{
         $width = '220';
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      $html = '';
      $html .= $this->_getAdditionalRestrictionBoxAsHTML($field_length).LF;
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
               $list = $this->getAvailableRubric($link_name[0]);
               $selrubric = $this->getSelectedRubric($link_name[0]);
               $temp_link = mb_strtoupper($link_name[0], 'UTF-8');
               switch ( $temp_link )
               {
                  case 'GROUP':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_GROUP').'<br />'.LF;
                     break;
                  case 'INSTITUTION':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_INSTITUTION').'<br />'.LF;
                     break;
                  case 'TOPIC':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TOPIC').'<br />'.LF;
                     break;
                  default:
                     $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(1503) ';
                     break;
               }

               if ( isset($list)) {
                  $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
                  $html .= '      <option value="0"';
                  if ( !isset($selrubric) || $selrubric == 0 ) {
                     $html .= ' selected="selected"';
                  }
                  $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
                  $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
                  $sel_item = $list->getFirst();
                  while ( $sel_item ) {
                     $html .= '      <option value="'.$sel_item->getItemID().'"';
                     if ( isset($selrubric) and $selrubric == $sel_item->getItemID() ) {
                        $html .= ' selected="selected"';
                     }
                     $text = $this->_Name2SelectOption($sel_item->getTitle());
                     $html .= '>'.$text.'</option>'.LF;
                     $sel_item = $list->getNext();
                 }
                 $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
                 $html .= '      <option value="-1"';
                 if ( !isset($selrubric) || $selrubric == -1 ) {
                    $html .= ' selected="selected"';
                 }
                 $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
                 $html .= '   </select>'.LF;
             } else {
                $html.='';
             }
             $html .='</div>';
            }
         }
      }
     return $html;
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
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() or (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                   $this->_with_slimbox = true;
                   $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
           }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $retour.$file_list;
   }
}
?>