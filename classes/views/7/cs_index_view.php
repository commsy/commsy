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
   var $_selected_user = NULL;
   var $_available_user = NULL;
   var $_selected_tag_array = array();
   var $_selected_buzzword = NULL;
   var $_available_buzzwords = NULL;
   var $_include_mootools = false;
   var $_show_netnavigation_box = true;
   var $_selected_color = NULL;
   var $_selected_status = NULL;
   public $_display_mode = NULL;

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

   var $_colspan = 5;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_index_view ($params) {
      $this->_with_form_fields = true;
      if ( !empty($params['with_form_fields']) ) {
         $this->_with_form_fields = $params['with_form_fields'];
      }
      $this->cs_view($params);
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

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function setClipboardMode() {
      $this->_clipboard_mode = true;
   }

   function setDisplayMode ( $value ) {
      $this->_display_mode = $value;
   }

   function setColspan($span){
      $this->_colspan = $span;
   }

   function setActivationLimit($limit){
      $this->_activation_limit = $limit;
   }

   function getActivationLimit(){
      return $this->_activation_limit;
   }

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

   /** set description of the list view
    * this method sets the shown description of the list view
    *
    * @param int  $this->_description          description of the shown list
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
    }

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

    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       if (empty($this->_search_text)){
          $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_RUBRIC');
          if ( $this->_environment->inPrivateRoom()
               and $this->_environment->getConfiguration('c_use_new_private_room')
               and $this->_environment->getCurrentModule() == type2module(CS_DATE_TYPE)
             ) {
             $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES');
          }
       }
       return $this->_search_text;
    }

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

    function getAttributeLimit () {
       return $this->_attribute_limit;
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

   function setSelectedColor ($value) {
        $this->_selected_color = $value;
   }

   function getSelectedColor () {
        return $this->_selected_color;
   }

   function setSelectedStatus ($value) {
        $this->_selected_status = $value;
   }

   function getSelectedStatus () {
        return $this->_selected_status;
   }

   function setAvailableGroups ($group_list) {
      $this->_available_groups = $group_list;
   }

   function getAvailableGroups () {
      return $this->_available_groups;
   }

   function setSelectedUser ($value) {
       $this->_selected_user =$value;;
   }

   function getSelectedUser () {
      return $this->_selected_user;
   }
   // @segment-end 81876

   function setAvailableUser ($user_list) {
      $this->_available_user = $user_list;
   }

   function getAvailableUser () {
      return $this->_available_user;
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
      $params = $this->_environment->getCurrentParameterArray();
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
      if (isset($this->_search_text) and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_ROOM') and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_RUBRIC')){
         $params['search'] = rawurlencode($this->_search_text);
      }
      if ( $this->_environment->inProjectRoom() ) {
         $params['selgroup'] = $this->getSelectedGroup();
      } else {
         $params['selinstitution'] = $this->getSelectedInstitution();
      }
      $params['seltopic'] = $this->getSelectedTopic();
      $params['selbuzzword'] = $this->getSelectedBuzzword();
      $params['selstatus'] = $this->getSelectedStatus();
      $params['seluser'] = $this->getSelectedUser();
      $params['selcolor'] = $this->getSelectedColor();
      $tag_array = $this->_getSelectedTagArray();
      if ( !empty($tag_array) ){
         foreach($tag_array as $key => $tag_id){
            $params['seltag_'.$key] = $tag_id;
         }
      }
      return $params;
   }

   function getSortingBoxAsHTML () {
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_SORTING_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:10pt;">'.LF;
      $html .= '<a href="javascript:callStudyLogSortAlphabetical()">'.$this->_translator->getMessage('COMMON_TITLE').'</a>'.LF;
      if ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE ) {
         #$html .= ' - <a href="javascript:callStudyLogSortAuthor()">'.$this->_translator->getMessage('COMMON_AUTHOR').'</a>'.LF;
         $html .= ' - <a href="javascript:callStudyLogSortChronological()">'.$this->_translator->getMessage('COMMON_CALENDAR_DATE').'</a>'.LF;
         $html .= ' - <a href="javascript:callStudyLogSortDefault()">'.$this->_translator->getMessage('COMMON_NO_SORTING').'</a>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

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
      $width = '235';
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_BUZZWORD_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $buzzword = $this->_available_buzzwords->getFirst();
      if (!isset($buzzword)){
         $html .= '<span class="disabled" style="font-size:8pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['from']);
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
            $title .= $this->_text_as_html_short($buzzword->getName()).LF;
            $title .= '</span> ';
            if ( $this->_environment->inPrivateRoom()
                 and $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                 and $this->_display_mode == 'flash'
               ) {
               $html .= '<a href="javascript:callStudyLogSortByTagId('.$buzzword->getItemID().')">'.$title.'</a>'.LF;
            } elseif (!$is_selected){
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
      $width = '235';
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

      $session_item = $this->_environment->getSessionItem();
      $with_javascript = false;
      if($session_item->issetValue('javascript')){
         if($session_item->getValue('javascript') == "1"){
            $with_javascript = true;
         }
      }
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $with_javascript = false;
     }
      // UMSTELLUNG MUSEUM
      if($with_javascript){
         $html_text .= $this->_getTagContentAsHTMLWithJavascript($root_item,0,$selected_id, $father_id_array,0,true);
      } else {
         $html_text .= $this->_getTagContentAsHTML($root_item,0,$selected_id, $father_id_array);
      }

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
      unset($params['from']);
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
               $title = $this->_text_as_html_short($current_item->getTitle());
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
                  if ( $this->_environment->inPrivateRoom()
                       and $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                       and $this->_display_mode == 'flash'
                     ) {
                     $html .= '<a href="javascript:callStudyLogSortByTagId('.$current_item->getItemID().')">'.$title.'</a>'.LF;
                  } else {
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params,
                                         $title,
                                         $title,'','','','','','style="color:'.$color.'"').LF;
                  }
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

   function _getTagContentAsHTMLWithJavascript($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0, $with_div=false) {
      // MUSEUM
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['from']);
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
            if($with_div){
               if(isset($_GET['seltag'])){
                  $html .= '<div id="tag_tree" name="tag_tree_detail">';
               } else {
                  $html .= '<div id="tag_tree">';
               }
            }
            $html .= '<ul>'.LF;
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               $link_name = '';
               if ( empty($selected_id) ){
                  $tag2tag_manager = $this->_environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
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
                  #$font_style = 'normal';
                  $link_name = 'selected';
               }
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $title = $this->_text_as_html_short($current_item->getTitle());
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
                  if ( $this->_environment->inPrivateRoom()
                       and $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                       and $this->_display_mode == 'flash'
                     ) {
                     $html .= '<li id="' . $current_item->getItemID() . '" data="StudyLog: \'' . $current_item->getItemID() . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= '<a href="javascript:callStudyLogSortByTagId('.$current_item->getItemID().')">'.$title.'</a>'.LF;
                  } else {
                     $link = curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                     $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params,
                                         $title,
                                         $title,'','','','','','style="color:#545454; font-size:9pt;"').LF;
                  }
               }else{
                  $params['name'] = $link_name;
                  $link = curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                  $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#000000; font-style:normal; font-size:9pt; font-weight:bold;">'.LF;
                  $html .= $title.LF;
               }
               $html .= $this->_getTagContentAsHTMLWithJavascript($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
               $html.='</li>'.LF;
            }
            $html.='</ul>'.LF;
            if($with_div){
               $html .= '</div>'.LF;
            }
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
      $width = '235';
      $width = $width - ($depth*15);
      $margin = $depth*15;
      if ($width < 50){
         $width = 50;
         $margin = 110;
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

      // jQuery
      //$html .= '   <select style="width: '.$width.'px; font-size:8pt; margin-left:'.$margin.'px; margin-bottom:5px;" name="seltag_'.$depth.'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select style="width: '.$width.'px; font-size:8pt; margin-left:'.$margin.'px; margin-bottom:5px;" name="seltag_'.$depth.'" size="1" id="submit_form">'.LF;
      // jQuery
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

      $text2 = '';
      if($this->_environment->getCurrentUserItem()->isModerator()) {
         $text2 = $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION_MODERATOR");
      }

      $html .= "
          <script type='text/javascript'>
         <!--
              var headline = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_TITLE") . "';
              var text1 = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION") . "';
              var text2 = '" . $text2 . "';
              var button_delete = '" . $this->_translator->getMessage("COMMON_DELETE_BUTTON") . "';
              var button_cancel = '" . $this->_translator->getMessage("COMMON_CANCEL_BUTTON") . "';
          -->
          </script>
      ";
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
       or isset($params['seluser'])
       or isset($params['selinstitution'])
       or isset($params['seltopic'])
       or isset($params['search'])
       or isset($params['selstatus'])
       or isset($params['selactivatingstatus'])
       or isset($this->_activation_limit)
       or (!empty($ref_user) and isset($params['mode']) and $params['mode'] == 'attached')
       or (!empty($ref_iid) and isset($params['mode']) and $params['mode'] == 'attached')
       or $this->_additional_selects
       ){
         $html_text ='';
         if ( !empty($ref_user) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('MODIFIED_ITEMS_LISTVIEW_SEPERATOR').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $ref_item = $this->getRefItem();
            $link_params = array();
            $link_params['iid'] = $ref_user;
            $title = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_USER_TYPE,
                                'detail',
                                $link_params,
                                chunkText($this->_text_as_html_short($ref_item->getFullName()),15),
                                '',
                                '',
                                $this->getFragment()
                               );
            unset($link_params);
            $html_text .= '<span><a title="'.$this->_text_as_html_short($ref_item->getFullName()).'">'.$title.'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['ref_user']);
            unset($new_params['mode']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( !empty($ref_iid) and !(isset($_GET['mode']) and $_GET['mode']=='formattach')){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $ref_item = $this->getRefItem();
            $ref_item_type = $ref_item->getItemType();
            if($ref_item_type == CS_USER_TYPE){
               $link_title = $this->_text_as_html_short($ref_item->getFullName());
            } else {
               $link_title = $this->_text_as_html_short($ref_item->getTitle());
            }
            if ( $ref_item_type == CS_ANNOTATION_TYPE ) {
              $ref_item2 = $ref_item->getLinkedItem();
              $module = type2module($ref_item2->getItemType());
              $link_params = array();
              $link_params['iid'] = $ref_item2->getItemID();
              $title = ahref_curl($this->_environment->getCurrentContextID(),
                                  $module,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              unset($link_params);
              $html .= '</span>'.LF;
           } elseif ( $ref_item_type == CS_SECTION_TYPE ) {
              $ref_item2 = $ref_item->getLinkedItem();
              $link_params = array();
              $link_params['iid'] = $ref_item2->getItemID();
              $title = ahref_curl($this->_environment->getCurrentContextID(),
                                  CS_MATERIAL_TYPE,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              unset($link_params);
              $html .= '</span>'.LF;
           } elseif ( $ref_item_type == CS_DISCARTICLE_TYPE ) {
              $ref_item2 = $ref_item->getLinkedItem();
              $link_params = array();
              $link_params['iid'] = $ref_item2->getItemID();
              $title = ahref_curl($this->_environment->getCurrentContextID(),
                                  CS_DISCUSSION_TYPE,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              unset($link_params);
              $html .= '</span>'.LF;
           } else {
              $module = type2module($ref_item_type);
              $link_params = array();
              $link_params['iid'] = $ref_iid;
              $title = ahref_curl($this->_environment->getCurrentContextID(),
                                  $module,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $this->getFragment()
                                 );
              unset($link_params);
              $html .= '</span>'.LF;
           }
            $html_text .= '<span><a title="'.$ref_item->getTitle().'">'.$title.'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['ref_iid']);
            unset($new_params['mode']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( isset($params['search']) and !empty($params['search']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_SEARCH_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span><a title="'.urldecode($params['search']).'">'.chunkText($this->_text_as_html_short(urldecode($params['search'])),13).'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['search']);
            unset($new_params['selrestriction']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         $html .= $this->getAdditionalRestrictionTextAsHTML();
         if ( isset($params['selgroup']) and !empty($params['selgroup']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_GROUP').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
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
                                chunkText($this->_text_as_html_short($group_item->getTitle()),17),$this->_text_as_html_short($group_item->getTitle())).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selgroup']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( isset($params['seluser']) and !empty($params['seluser']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_USER').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            if ($params['seluser'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $user_manager = $this->_environment->getUserManager();
               $user_item = $user_manager->getItem($params['seluser']);
               $link_params = array();
               $link_params['iid'] = $user_item->getItemID();
               $html_text .=  ahref_curl($this->_environment->getCurrentContextID(),
                                CS_USER_TYPE,
                                'detail',
                                $link_params,
                                chunkText($this->_text_as_html_short($user_item->getFullName()),17),$this->_text_as_html_short($user_item->getFullName())).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['seluser']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( isset($params['selinstitution']) and !empty($params['selinstitution']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_INSTITUTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
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
                                chunkText($this->_text_as_html_short($institution_item->getTitle()),14),$this->_text_as_html_short($institution_item->getTitle())).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selinstitution']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( isset($params['selstatus']) and $params['selstatus'] != '-1' and $params['selstatus'] != '0' and !empty($params['selstatus']) and $this->_environment->current_module == "todo"){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('TODO_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         if (isset($params['selstatus']) and $params['selstatus'] == 1){
            $status_text = $this->_translator->getMessage('TODO_NOT_STARTED');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 2){
            $status_text = $this->_translator->getMessage('TODO_IN_POGRESS');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('TODO_DONE');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 4){
            $status_text = $this->_translator->getMessage('TODO_NOT_DONE');
         }elseif(isset($params['selstatus']) and $params['selstatus'] != 0){
            $context_item = $this->_environment->getCurrentContextItem();
            $todo_status_array = $context_item->getExtraToDoStatusArray();
            $status_text = '';
            if (isset($todo_status_array[$params['selstatus']])){
               $status_text = $todo_status_array[$params['selstatus']];
            }
         }else{
            $status_text = '';
         }
         $html_text .= '<span><a title="'.$status_text.'">'.$status_text.'</a></span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selstatus'] = 0;
         // unset($new_params['selstatus']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
         } else{
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         // $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('TODO_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         //$status_text = $this->_translator->getMessage('TODO_NOT_DONE');
         // $html_text .= '<span><a title="'.$status_text.'">'.$status_text.'</a></span>';
         // $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         // unset($new_params['selstatus']);
         // Loescht aber nicht die Einschränkung
         $new_params['selstatus'] = 0;
         //$html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
         }
         if ( isset($params['seltopic']) and !empty($params['seltopic']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_TOPIC').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
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
                                chunkText($this->_text_as_html_short($topic_item->getTitle()),17),$this->_text_as_html_short($topic_item->getTitle())).LF;
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['seltopic']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }

         if ( isset($params['selcolor']) and (!empty($params['selcolor']) or $this->_environment->getCurrentModule() == 'date') and $params['selcolor'] != 2 ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_DATE_COLOR').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $color_text = '';

         $color_text = '';
         switch ('#'.$params['selcolor']){
            case '#999999': $color_text = getMessage('DATE_COLOR_GREY');break;
            case '#CC0000': $color_text = getMessage('DATE_COLOR_RED');break;
            case '#FF6600': $color_text = getMessage('DATE_COLOR_ORANGE');break;
            case '#FFCC00': $color_text = getMessage('DATE_COLOR_DEFAULT_YELLOW');break;
            case '#FFFF66': $color_text = getMessage('DATE_COLOR_LIGHT_YELLOW');break;
            case '#33CC00': $color_text = getMessage('DATE_COLOR_GREEN');break;
            case '#00CCCC': $color_text = getMessage('DATE_COLOR_TURQUOISE');break;
            case '#3366FF': $color_text = getMessage('DATE_COLOR_BLUE');break;
            case '#6633FF': $color_text = getMessage('DATE_COLOR_DARK_BLUE');break;
            case '#CC33CC': $color_text = getMessage('DATE_COLOR_PURPLE');break;
            default: $color_text = getMessage('DATE_COLOR_UNKNOWN');
         }
         $html_text .= '<span style="color:#'.$params['selcolor'].';">'.$color_text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selcolor'] = 2;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
         }

         if ( isset($params['selbuzzword'])  and !empty($params['selbuzzword']) ){
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_BUZZWORD_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $buzzword_manager = $this->_environment->getBuzzwordManager();
            if ($params['selbuzzword'] == '-1'){
               $html_text .= '<span><a title="'.$this->_translator->getMessage('COMMON_NOT_LINKED').'">'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</a></span>';
            }else{
               $buzzword_item = $buzzword_manager->getItem($params['selbuzzword']);
               $html_text .= '<span><a title="'.$this->_text_as_html_short($buzzword_item->getName()).'">'.chunkText($this->_text_as_html_short($buzzword_item->getName()),12).'</a></span>';
            }
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['selbuzzword']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;

            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         if ( isset($params['seltag']) ){
            $i = 0;
            while ( !isset($params['seltag_'.$i]) ){
               $i++;
            }
            $tag_manager = $this->_environment->getTagManager();
            $tag_item = $tag_manager->getItem($params['seltag_'.$i]);
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_TAG_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>'.chunkText($this->_text_as_html_short($tag_item->getTitle()),12).'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['seltag_'.$i]);
            unset($new_params['seltag']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
      }
      return $html;
   }


   function _getIndexPageHeaderAsHTML($left_width = '70', $right_width = '28',$search_width='220'){
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      $new_private_room = $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID());
      $current_context_id = $this->_environment->getCurrentContextID();
      $current_portal_id = $this->_environment->getCurrentPortalID();
      if ( $current_context->isOpen() and $current_context->isPrivateRoom()
         and $new_private_room
         and !(isset($_GET['mode']) and $_GET['mode']=='print')) {
         $html .= '<div style="float:right;">'.LF;
         $html .= '<div class="portlet-configuration">'.LF;
         $html .= '<div class="portlet-header-configuration ui-widget-header" style="width:200px;">'.LF;
         $html .= $this->_translator->getMessage('MYCALENDAR_CONFIGURATION').LF;
         $html .= '<div style="float:right;">'.LF;
         $html .= '<a href="#"><img id="mycalendar_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png" style="height:0px;" /></a>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      } else {
         $html .= '<div id="search_box" style="float:right; width:'.$right_width.'%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
         $html .= $this->_getSearchAsHTML($search_width);
         $html .= '</div>'.LF;
      }
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $html .='<div style="width: 99%;">'.LF;
      }else{
         $html .='<div style="width: '.$left_width.'%; " >'.LF;

      }
      $html .='<div id="action_box">';
      $html .= $this->_getListActionsAsHTML();
      $html .='</div>';
      $html .='<div style="vertical-align:bottom;">'.LF;
      $tempMessage = '';
      switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ) {
         case 'ANNOUNCEMENT':
            $tempMessage = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/announcement.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/announcement.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'DATE':
            if ( $this->_environment->inPrivateRoom()
                 and $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID())
               ) {
               $tempMessage = $this->_translator->getMessage('MYCALENDAR_INDEX');
            } else {
               $tempMessage = $this->_translator->getMessage('DATE_INDEX');
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $tempMessage = '<img src="images/commsyicons_msie6/32x32/date.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
               } else {
                  $tempMessage = '<img src="images/commsyicons/32x32/date.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
               }
            }
            break;
         case 'DISCUSSION':
            $tempMessage = $this->_translator->getMessage('DISCUSSION_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/discussion.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/discussion.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'INSTITUTION':
            $tempMessage = $this->_translator->getMessage('INSTITUTION_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'GROUP':
            $tempMessage = $this->_translator->getMessage('GROUP_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MATERIAL':
            $tempMessage = $this->_translator->getMessage('MATERIAL_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/material.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/material.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MATERIAL_ADMIN':
            $tempMessage = $this->_translator->getMessage('MATERIAL_ADMIN_INDEX').' ('.$this->_translator->getMessage('MATERIAL_INDEX').')';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/material_admin.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/config/material_admin.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MYROOM':
            $tempMessage = $this->_translator->getMessage('MYROOM_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'PROJECT':
            $tempMessage = $this->_translator->getMessage('PROJECT_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'TODO':
            $tempMessage = $this->_translator->getMessage('TODO_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/todo.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/todo.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'TOPIC':
            $tempMessage = $this->_translator->getMessage('TOPIC_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/topic.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/topic.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'USER':
            $tempMessage = $this->_translator->getMessage('USER_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/user.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/user.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
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
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="font-size:0pt; width:100%; clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   function showBuzzwords(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withBuzzwords()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      return $retour;
   }

   function showTags(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withTags()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      return $retour;
   }

   function showNetnavigation(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withNetnavigation()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      if (!$context_item->withRubric(CS_GROUP_TYPE) and !$context_item->withRubric(CS_TOPIC_TYPE) and !$context_item->withRubric(CS_INSTITUTION_TYPE)){
         $retour = false;
      }
      return $retour;
   }



   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

  	  $html .= $this->addJavaScriptForSearch();

       $html .= $this->_getIndexPageHeaderAsHTML();
      /*****************************/
      /*******BEGIN RIGHT BOXES*****/
      /*****************************/
      if(!$this->_clipboard_mode and !(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div id="right_boxes_area">'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
         $current_context = $this->_environment->getCurrentContextItem();
         $list_box_conf = $current_context->getListBoxConf();
         $first_box = true;
         $title_string ='';
         $desc_string ='';
         $config_text ='';
         $size_string = '';
         $html .= $this->_getHiddenFieldsAsHTML();
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
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_index_view('.__LINE__.') ');
               break;
         }
         $html .= $this->_getListInfosAsHTML($tempMessage);

         $context_item = $this->_environment->getCurrentContextItem();

         /*********Expert Search*******/
         $html_text1 = $this->_getAdditionalRestrictionBoxAsHTML('14.5');
         $html_text2 = $this->_getAdditionalFormFieldsAsHTML();
         if ( !strstr($list_box_conf,'search_nodisplay')
            and ($this->_environment->getCurrentModule() == CS_DATE_TYPE
                 or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                 or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                 or $this->_environment->getCurrentModule() == CS_USER_TYPE
                 or $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                 or $this->_environment->getCurrentModule() == CS_TODO_TYPE
            )
            and( $html_text1 != '' or $html_text2 != '')
         ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            if ($this->_environment->getCurrentModule() != 'campus_search'){
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'"';
            }else{
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_RESTRICTION_SEARCH').'"';
            }
            $desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            $parameter_array = $this->_environment->getCurrentParameterArray();
            if (
                (isset($parameter_array['attribute_limit']) and $parameter_array['attribute_limit']!='0')
                or (
                   (isset($parameter_array['selstatus']) and $parameter_array['selstatus']!='0')
                   or ($this->_environment->getCurrentModule() == CS_TODO_TYPE)
                   )
                or (isset($parameter_array['selgroup']) and $parameter_array['selgroup']!='0')
                or (isset($parameter_array['selrubric']) and !empty($parameter_array['selrubric']))
                or (isset($parameter_array['selrestriction']) and !empty($parameter_array['selrestriction']))
                or (isset($parameter_array['seluser']) and !empty($parameter_array['seluser']))
                or ($this->_environment->getCurrentModule() == 'campus_search')
               ){
                if ($this->_environment->getCurrentModule() != CS_USER_TYPE or (isset($parameter_array['selstatus']) and $parameter_array['selstatus']=='3')){
                   $config_text .= $additional_text.'true';
                }else{
                   $config_text .= $additional_text.'false';
                }
            }else{
                $config_text .= $additional_text.'false';
            }
            $html .= $this->_getExpertSearchAsHTML();
         }

         /**************Buzzwords***************/
         if ( $this->showBuzzwords() ) {
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
            if ( (isset($parameter_array['selbuzzword']) and !empty($parameter_array['selbuzzword']))
                 or $current_context->isBuzzwordShowExpanded()
            ) {
               $config_text .= $additional_text.'true';
            } else {
               $config_text .= $additional_text.'false';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->getBuzzwordBoxAsHTML();
            $html .= '</div>'.LF;
         }

         /*********************Tags******************/
         if ( $this->showTags() ) {
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
            if ( (isset($parameter_array['seltag']) and !empty($parameter_array['seltag']))
                 or $current_context->isTagsShowExpanded()
            ) {
               $config_text .= $additional_text.'true';
            } else {
               $config_text .= $additional_text.'false';
            }
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->getTagBoxAsHTML();
            $html .= '</div>'.LF;
         }

         /**************sorting for flash***************/
         if ( !empty($this->_display_mode) and $this->_display_mode == 'flash' ) {
            if ( $first_box ) {
               $first_box = false;
               $additional_text = '';
            } else {
               $additional_text = ',';
            }
            $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_SORTING_BOX').'"';
            $desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            $config_text .= $additional_text.'true';
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->getSortingBoxAsHTML();
            $html .= '</div>'.LF;
         }

         $html .='</div>'.LF;


         /*****************Usage Information*************/
         $user = $this->_environment->getCurrentUserItem();
         $room = $this->_environment->getCurrentContextItem();
         $act_rubric = $this->_environment->getCurrentModule();
         $info_text = $room->getUsageInfoTextForRubric($act_rubric);
         if (!strstr($info_text, $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'))
             and !strstr($info_text, $this->_translator->getMessage('USAGE_INFO_COMING_SOON'))
             and !empty($info_text)
            ){
             $rubric_info_array = $room->getUsageInfoArray();
             if (!is_array($rubric_info_array)) {
                $rubric_info_array = array();
             }
             // kann man dies noch über die oberfläche schalten ??? (2009.07.24 ij)
             #if ( !strstr($list_box_conf,'usage_nodisplay') ){
                if ( $first_box ){
                   $first_box = false;
                   $additional_text ='';
                }else{
                   $additional_text =',';
                }
                $html .= '<div style="margin-bottom:1px;">'.LF;
                $html .= '<div style="position:relative; top:12px;">'.LF;
                if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                   $html .= '<img src="images/commsyicons_msie6/usage_info_3.gif"/>';
                } else {
                   $html .= '<img src="images/commsyicons/usage_info_3.png"/>';
                }
                $html .= '</div>'.LF;
                $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_text_as_html_short($room->getUsageInfoHeaderForRubric($act_rubric)).'</div>';
                $html .= '<div class="usage_info">'.LF;
                $info_text = $room->getUsageInfoTextForRubric($act_rubric);
                $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
                $html .= '</div>'.LF;
                $html .= '</div>'.LF;
             #} // end if
         }
         $html .= '</form>'.LF;

         $html .= '</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'jQuery(document).ready(function() {initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'),Array(),null,null)});'.LF;
         $html .= '</script>'.LF;
      }
      elseif(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div style="float:right; width:27%; padding-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .='<div style="margin-bottom:1px;">'.LF;
         $html .= $this->_getRubricClipboardInfoAsHTML($this->_environment->getCurrentModule());
         $html .='</div>'.LF;
         $html .='</div>'.LF;
      }else{
 #       $html .= $this->_getIndexPageHeaderAsHTML();
      }

      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px;';
      }else{
         $width= '';
      }

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='</div>'.LF;
         $html .='<div class="index_content_display_width" style="'.$width.'padding-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='</div>'.LF;
         $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      }
      if ( !empty($this->_display_mode)
           and $this->_display_mode == 'flash'
           and $this->_environment->getCurrentModule() == type2Module(CS_MATERIAL_TYPE)
         ) {

         $current_context_item = $this->_environment->getCurrentContextItem();
         $color_array = $current_context_item->getColorArray();
         unset($current_context_item);

         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['output'] = 'XML';
         $params['SID'] = $this->_environment->getSessionID();
         $data_url = utf8_encode(rawurlencode(_curl(false,$this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params)));
         unset($params);

         #$height = '100%';
         $height = '450px';
         #$bgcolor = $color_array['content_background'];
         $bgcolor = '#ffffff';
         unset($color_array);

         $html .= '<div id ="docuverser" class="index_flash" style="height: '.$height.'; background-color: '.$bgcolor.'; border: none;">'.LF;
         $html .= '<noscript>'.LF;
         $html .= '    <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'.LF;
         $html .= '            id="study_log" width="100%" height="'.$height.'"'.LF;
         $html .= '            codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">'.LF;
         $html .= '            <param name="movie" value="flash/study_log.swf" />'.LF;
         $html .= '            <param name="quality" value="high" />'.LF;
         $html .= '            <param name="FlashVars" value="applicationType=commsy&amp;commsyXml='.$data_url.'" />'.LF;
         $html .= '            <param name="wmode" value="transparent" />'.LF;
         $html .= '            <param name="bgcolor" value="'.$bgcolor.'" />'.LF;
         $html .= '            <param name="allowScriptAccess" value="sameDomain" />'.LF;
         $html .= '            <embed src="study_log.swf" quality="high" bgcolor="'.$bgcolor.'"'.LF;
         $html .= '                width="100%" height="'.$height.'" name="study_log" align="middle"'.LF;
         $html .= '                play="true"'.LF;
         $html .= '                FlashVars="applicationType=commsy&amp;commsyXml='.$data_url.'"'.LF;
         $html .= '                wmode="transparent"'.LF;
         $html .= '                loop="false"'.LF;
         $html .= '                quality="high"'.LF;
         $html .= '                allowScriptAccess="sameDomain"'.LF;
         $html .= '                type="application/x-shockwave-flash"'.LF;
         $html .= '                pluginspage="http://www.adobe.com/go/getflashplayer">'.LF;
         $html .= '            </embed>'.LF;
         $html .= '    </object>'.LF;
         $html .= '</noscript>'.LF;
         $html .= '</div>'.LF;
         $html .= $this->getDokuverserJavascriptAsHTML();

      } else {
         $params = $this->_environment->getCurrentParameterArray();
         unset($params['mode']);
         unset($params['download']);
         $html .= '<form style="padding:0px; margin:0px;" action="';
         $html .= curl($this->_environment->getCurrentContextID(),
                       $this->_environment->getCurrentModule(),
                       $this->_environment->getCurrentFunction(),
                       $params
                      ).'" method="post">'.LF;
         if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
            $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
         }
         $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
         $html .= $this->_getTableheadAsHTML();
         if (!$this->_clipboard_mode){
            $html .= $this->_getContentAsHTML();
         }else{
            $html .= $this->_getClipboardContentAsHTML();
         }
         if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
            $html .= $this->_getTablefootAsHTML();
         }
         $html .= '</table>'.LF;
         $html .= '</form>'.LF;
      }
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


   function getDokuverserJavascriptAsHTML(){
     $current_context_item = $this->_environment->getCurrentContextItem();
     $color_array = $current_context_item->getColorArray();
     unset($current_context_item);
     $params = array();
     $params = $this->_environment->getCurrentParameterArray();
     $params['output'] = 'XML';
     $params['SID'] = $this->_environment->getSessionID();
     $data_url = utf8_encode(rawurlencode(_curl(false,$this->_environment->getCurrentContextID(),'material','index',$params)));
     unset($params);
     $height = '450px';
     $bgcolor = '#ffffff';
     unset($color_array);
     $html  = '';
     $html .= '<script src="javascript/AC_OETags.js" language="javascript" type="text/javascript"></script>'.LF;
     $html .= '<script src="javascript/history/history.js" language="javascript" type="text/javascript"></script>'.LF;
     $html .= '<script language="JavaScript" type="text/javascript">'.LF;
     $html .= '<!--'.LF;
     $html .= '// Version check for the Flash Player that has the ability to start Player Product Install (6.0r65)'.LF;
     $html .= 'var hasProductInstall = DetectFlashVer(6, 0, 65);'.LF;
     $html .= '// Version check based upon the values defined in globals'.LF;
     $html .= 'var hasRequestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);'.LF;
     $html .= 'if ( hasProductInstall && !hasRequestedVersion ) {'.LF;
     $html .= '    // DO NOT MODIFY THE FOLLOWING FOUR LINES'.LF;
     $html .= '    // Location visited after installation is complete if installation is required'.LF;
     $html .= '    var MMPlayerType = (isIE == true) ? "ActiveX" : "PlugIn";'.LF;
     $html .= '    var MMredirectURL = window.location;'.LF;
     $html .= '    document.title = document.title.slice(0, 47) + " - Flash Player Installation";'.LF;
     $html .= '    var MMdoctitle = document.title;'.LF;
     $html .= '    jQuery("#docuverser").append(AC_FL_RunContent('.LF;
     $html .= '        "src", "flash/playerProductInstall",'.LF;
     $html .= '        "FlashVars", "MMredirectURL="+MMredirectURL+\'&MMplayerType=\'+MMPlayerType+\'&MMdoctitle=\'+MMdoctitle+\'&commsyXml=\'+\''.$data_url.'\'+\'&applicationType=commsy\'+"",'.LF;
     $html .= '        "width", "100%",'.LF;
     $html .= '        "height", "'.$height.'",'.LF;
     $html .= '        "align", "middle",'.LF;
     $html .= '        "id", "study_log",'.LF;
     $html .= '        "wmode", "transparent",'.LF;
     $html .= '        "quality", "high",'.LF;
     $html .= '        "bgcolor", "'.$bgcolor.'",'.LF;
     $html .= '        "name", "study_log",'.LF;
     $html .= '        "allowScriptAccess","sameDomain",'.LF;
     $html .= '        "type", "application/x-shockwave-flash",'.LF;
     $html .= '        "pluginspage", "http://www.adobe.com/go/getflashplayer"'.LF;
     $html .= '    ));'.LF;
     $html .= '} else if (hasRequestedVersion) {'.LF;
     $html .= '    // if we\'ve detected an acceptable version'.LF;
     $html .= '    // embed the Flash Content SWF when all tests are passed'.LF;
     $html .= '    jQuery("#docuverser").append(AC_FL_RunContent('.LF;
     $html .= '            "src", "flash/study_log",'.LF;
     $html .= '            "FlashVars", "thickbox=false&thickboxHeight=550&motionSpeed=18&thumbWidth=14&applicationType=commsy&commsyXml='.$data_url.'",'.LF;
     $html .= '            "width", "100%",'.LF;
     $html .= '            "height", "'.$height.'",'.LF;
     $html .= '            "align", "middle",'.LF;
     $html .= '            "id", "study_log",'.LF;
     $html .= '            "wmode", "transparent",'.LF;
     $html .= '            "quality", "high",'.LF;
     $html .= '            "bgcolor", "'.$bgcolor.'",'.LF;
     $html .= '            "name", "study_log",'.LF;
     $html .= '            "allowScriptAccess","sameDomain",'.LF;
     $html .= '            "type", "application/x-shockwave-flash",'.LF;
     $html .= '            "pluginspage", "http://www.adobe.com/go/getflashplayer"'.LF;
     $html .= '    ));'.LF;
     $html .= '  } else {  // flash is too old or we can\'t detect the plugin'.LF;
     $html .= '    var alternateContent = \'This content requires the Adobe Flash Player. <a href="http://www.adobe.com/go/getflash/">Get Flash</a>;\''.LF;
     $html .= '    jQuery("#docuverser").append(alternateContent);  // insert non-flash content'.LF;
     $html .= '  }'.LF;
     $html .= '// -->'.LF;
     $html .= '</script>'.LF;
     return $html;
   }

   function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div style="clear:both; padding-bottom:0px;">';
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/print.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                         $this->_environment->getCurrentModule(),
                         'index',
                         $params,
                         $image,
                         $this->_translator->getMessage('COMMON_LIST_PRINTVIEW'),
                         '_blank'
                         ).LF;
      $html .= $this->_getAdditionalActionsAsHTML();
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         }
         if ( $current_context->isOpen()
              and $current_context->isPrivateRoom()
              and $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID())
            ) {
            // do nothing
         } else {
            $html .= '&nbsp;';
         }
         $html .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                            'edit',
                            $params,
                            $image,
                            $this->_translator->getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon_disabled"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon_disabled"/>';
         }
         $html .= '&nbsp;&nbsp;<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_NEW_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= $this->_initDropDownMenus();
      return $html;
   }

   function _initDropDownMenus(){
      $action_array = array();
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();

      if ( $current_context->isOpen() ) {
         $image_new  = '';
         $href_new = '';
         $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image_new = 'images/commsyicons_msie6/22x22/new.gif';
         } else {
            $image_new = 'images/commsyicons/22x22/new.png';
         }
         $href_new = curl($this->_environment->getCurrentContextID(),
                          $this->_environment->getCurrentModule(),
                          'edit',
                          $params);
         unset($params);
         if ( $current_context->isOpen()
              and $current_context->isPrivateRoom()
              and $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID())
              and $this->_environment->getCurrentModule() == type2module(CS_DATE_TYPE)
            ) {
            $text_new = $this->_translator->getMessage('DATES_ENTER_NEW');
         } else {
            $text_new = $this->_translator->getMessage('COMMON_NEW_ITEM');
         }
         if ( !empty($text_new)
              and !empty($image_new)
              and !empty($href_new)
            ) {
            $temp_array = array();
            $temp_array['dropdown_image']  = "new_icon";
            $temp_array['text']  = $text_new;
            $temp_array['image'] = $image_new;
            $temp_array['href']  = $href_new;
            $action_array[] = $temp_array;
            unset($temp_array);
         }
      }

      unset($current_context);

      $action_array = array_merge($action_array, $this->_getAdditionalDropDownEntries());

      // init drop down menu
      if ( !empty($action_array)
           and count($action_array) > 1
         ) {
         $html .= '<script type="text/javascript">'.LF;
         $html .= '<!--'.LF;
         #$html .= 'var dropDownMenus = new Array(new Array("new_icon",new Array(';
         $html .= 'var dropDownMenus = new Array(';
         $first = true;
         foreach ($action_array as $action) {
            if ( $first ) {
               $first = false;
            } else {
               $html .= ',';
            }
            $html .= 'new Array("'.$action['dropdown_image'].'","'.$action['image'].'","'.$action['text'].'","'.$action['href'].'")';
         }
         $html .= ');'.LF;
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }

   function _getAdditionalDropDownEntries() {
      return array();
   }

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div id="right_box_page_numbers">'.$this->_translator->getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;

      $width = '';
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:250px;';
      }
      $html .= '<div class="right_box_main" style="'.$width.'">'.LF;

      $html .= '<table id="list_info_table" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_LIST_SHOWN_ENTRIES').' </span>';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= '<span class="index_description">'.$this->_getDescriptionAsHTML().'</span>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</table>'.LF;


      $html .= '<div class="listinfoborder"></div>'.LF;

      $html .= '<table id="list_info_table2" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
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
         case 'MATERIAL_ADMIN':
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
         case 'CAMPUS_SEARCH':
            $text .= $this->_translator->getMessage('COMMON_ENTRIES');
            break;
         default:
            $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(1913) ';
            break;
      }
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ALL_LIST_ENTRIES',$text).':</span> ';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= $this->_count_all.''.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      if ( empty($this->_display_mode)
           or $this->_display_mode != 'flash'
         ) {
         $html .= '<tr>'.LF;
         $html .= '<td class="infocolor">';
         $html .= $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
         $html .= '</td>'.LF;
         $html .= '<td style="text-align:right;">'.LF;
         $params = $this->_environment->getCurrentParameterArray();
         if (!isset($params['mode']) or $params['mode'] == 'browse'){
            $params['mode'] = 'list_actions';
         }
         unset($params['select']);
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
         $html .='</td>'.LF;
         $html .='</tr>'.LF;
      }
      $html .='</table>'.LF;

      // flash view
      if ( $this->_environment->inPrivateRoom()
           and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
               )
          ) {
         $html .= '<div class="listinfoborder"></div>'.LF;
         $params = $this->_environment->getCurrentParameterArray();
         if ( empty($this->_display_mode) or $this->_display_mode != 'flash' ) {
            $params['seldisplay_mode'] = 'flash';
            $html .= '<div style="float:right;">'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('COMMON_CHANGE_INDEX_VIEW_FLASH')).'</div>'.LF;
         } else {
            $params['seldisplay_mode'] = 'html';
            $html .= '<div style="float:right;">'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('COMMON_CHANGE_INDEX_VIEW_LIST')).'</div>'.LF;
         }
         $html .= '<span class="infocolor">'.$this->_translator->getMessage('DATE_ALTERNATIVE_DISPLAY').': </span>'.LF;
      }

      $html .= '</div>'.LF;
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


  function _getRubricClipboardInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.$this->_text_as_html_short($room->getUsageInfoHeaderForRubric($act_rubric)).'</div>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

  function _getHiddenFieldsAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $html  = '';
      // Search / select form
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
      if ( isset($params['selcolor']) ){
          $html .= '   <input type="hidden" name="selcolor" value="'.$params['selcolor'].'"/>'.LF;
      }
      if ( isset($params['selstatus']) ){
          $html .= '   <input type="hidden" name="selstatus" value="'.$params['selstatus'].'"/>'.LF;
      }
      if ( isset($params['seluser']) ){
          $html .= '   <input type="hidden" name="seluser" value="'.$params['seluser'].'"/>'.LF;
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
      if ( isset($params['search']) and $params['search'] != $this->_translator->getMessage('COMMON_SEARCH_IN_ROOM')){
         $html .= '   <input type="hidden" name="search" value="'.$params['search'].'"/>'.LF;
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
      return $html;
  }

  function _getListSelectionsAsHTML () {
      $html  = '';
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }


  function _getExpertSearchAsHTML(){
     $html  = '';
     $context_item = $this->_environment->getCurrentContextItem();
     $module = $this->_environment->getCurrentModule();
     if ($context_item->withActivatingContent()
          or $module == CS_DATE_TYPE
          or $module == CS_ANNOUNCEMENT_TYPE
          or $module == CS_USER_TYPE
          or $module == CS_MATERIAL_TYPE
          or $module == CS_TODO_TYPE
          or $module == CS_DISCUSSION_TYPE
          or $module == 'campus_search'
      ){
         $width = '235';
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.')) or (strstr($current_browser_version,'7.'))) ){
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
         }else{
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
         }
         $html .= '<div class="right_box">'.LF;
         $html .= '         <noscript>';
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
         $html .= '         </noscript>';
         $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
         $html .= '   <input type="hidden" name="selactivatingstatus" value="'.$this->_activation_limit.'"/>'.LF;
  /*       if ($context_item->withActivatingContent()){
            $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_SHOW_ACTIVATING_ENTRIES').'<br />'.LF;
            $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" onChange="javascript:document.indexform.submit()">'.LF;
            $html .= '      <option value="1"';
            if ( isset($this->_activation_limit) and $this->_activation_limit == 1 ) {
               $html .= ' selected="selected"';
            }
            $html .= '>*'.$this->_translator->getMessage('COMMON_ALL_ENTRIES').'</option>'.LF;
            $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
            $html .= '      <option value="2"';
            if ( !isset($this->_activation_limit) || $this->_activation_limit == 2 ) {
                $html .= ' selected="selected"';
            }
            $html .= '>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</option>'.LF;
            $html .= '   </select>'.LF;
            $html .='</div>';
         }*/
         $html .= $this->_getAdditionalRestrictionBoxAsHTML('14.5').LF;
         $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      return $html;
  }


  function _getSearchAsHTML ($search_with='220') {
     $html  = '';
     $search_module = 'campus_search';
     if ( $this->_environment->inPrivateRoom()
          and $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID())
        ) {
        $search_module = type2module(CS_DATE_TYPE);
     }
     $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $search_module, 'index','').'" method="get" name="searchform">'.LF;
     $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
     $html .= '   <input type="hidden" name="mod" value="'.$search_module.'"/>'.LF;
     $html .= '   <input type="hidden" name="SID" value="'.$this->_environment->getSessionItem()->getSessionID().'"/>'.LF;
     $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
     $html .= '   <input type="hidden" name="selrubric" value="'.$this->_environment->getCurrentModule().'"/>'.LF;
     // Abfrage von Kategorien... Schleife für jeden seltag_i
     if (isset($_GET['seltag'])){
        $i = 0;
        // Schleife: Alles Tags, die gesetzt sind abfragen
        while(isset($_GET['seltag'])) {
           if(isset($_GET['seltag_'.$i.''])) {
              $html .= '<input type="hidden" name="seltag_'.$i.'" value="'.$_GET['seltag_'.$i.''].'">'.LF;
              $html .= '<input type="hidden" name="seltag" value="yes">'.LF;

              break;
           }
           $i++;
        }
     }
     if (isset($_GET['selbuzzword'])) {
        $html .= '<input type="hidden" name="selbuzzword" value="'.$_GET['selbuzzword'].'">'.LF;
     }
     if (isset($_GET['seluser'])) {
        $html .= '<input type="hidden" name="seluser" value="'.$_GET['seluser'].'">'.LF;
     }
     if (isset($_GET['selgroup'])) {
        $html .= '<input type="hidden" name="selgroup" value="'.$_GET['selgroup'].'">'.LF;
     }
     if (isset($_GET['selcolor'])) {
        $html .= '<input type="hidden" name="selcolor" value="'.$_GET['selcolor'].'">'.LF;
     }
     if (isset($_GET['selstatus'])) {
        $html .= '<input type="hidden" name="selstatus" value="'.$_GET['selstatus'].'">'.LF;
     }
     #$html .= '	  <input type="hidden" name="seltag" value="'.$_GET['seltag_0'].'"'.LF;
     $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:'.$search_with.'px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     } else {
        $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     }
     $html .= '</form>';
     return $html;
  }

   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
         if ( method_exists($this,'_getAdditionalViewActionsAsHTML') ) {
            $html .= $this->_getAdditionalViewActionsAsHTML();
         }

         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($this->_environment->inPrivateRoom()){
            $html .= '   <option value="3" id="delete_check_option">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $user = $this->_environment->getCurrentUserItem();
            if ($user->isModerator()){
               $html .= '   <option value="3" id="delete_check_option">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
            }else{
               $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
            }
         }
      }else{
         $html .= '   <option value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      }

      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="delete_confirmselect_option" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }


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
      $html = $description;
      return /*$this->_text_as_html_short(*/ $html /*)*/;
   }

   function _getTableheadAsHTML () {
   }

   function _getTablefootAsHTML () {
   }

   function _getBrowsingIconsAsHTML(){
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
      $html = '<div style="float:right;">';
      if ( $browse_start > 0
           and ( empty($this->_display_mode)
                 or $this->_display_mode != 'flash'
               )
         ) {
         $params['from'] = $browse_start;
         $image = '<span class="bold">&lt;&lt;</span>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
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
      if ( $browse_left > 0
           and ( empty($this->_display_mode)
                 or $this->_display_mode != 'flash'
               )
         ) {
         $params['from'] = $browse_left;
         $image = '<span class="bold">&lt;</span>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
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
      if ( $browse_right > 0
           and ( empty($this->_display_mode)
                 or $this->_display_mode != 'flash'
               )
         ) {
         $params['from'] = $browse_right;
         $image = '<span class="bold">&gt;</span>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
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
      if ( $browse_end > 0
           and ( empty($this->_display_mode)
                 or $this->_display_mode != 'flash'
               )
         ) {
         $params['from'] = $browse_end;
         $image = '<span class="bold">&gt;&gt;</span>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
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
      $html .= '</div>';
      return $html;
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
      $html = $act_page.' / '.$num_pages.LF;
      return $html;
   }

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
		 $html_assessment_tooltip = '';
		 $current_context = $this->_environment->getCurrentContextItem();
		 if($current_context->isAssessmentActive()) {
		 	// create tooltip
			$html_assessment_tooltip .= '<div id="assessment_tooltip" class="stickytooltip" style="width: 250px; border-width: 1px;">'.LF;
			$html_assessment_tooltip .= '	<div style="padding: 5px;">'.LF;
		 }

         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);

			// assessment tooltip
			if($current_context->isAssessmentActive()) {
				// get assessment information
				$assessment_manager = $this->_environment->getAssessmentManager();
				$assessment = $assessment_manager->getAssessmentForItemDetail($current_item);
				$html_assessment_tooltip .= '	<div id="sticky_' . $current_item->getItemID() . '" class="atip">'.LF;
				$html_assessment_tooltip .= $this->_translator->getMessage('COMMON_ASSESSMENT_OVERLAY_DESCRIPTION');

				// show information about all votes
				for($index = 1; $index <= 5; $index++) {
					$html_assessment_tooltip .= '<div style="margin-top: 5px;">'.LF;
					for($j = 0; $j < $index; $j++) {
						$html_assessment_tooltip .= '<span style="width: 200px;"><img src="images/commsyicons/32x32/star_filled.png" style="width:18px; height:18px"/></span>'.LF;
					}
					for($j = $index; $j < 5; $j++) {
						$html_assessment_tooltip .= '<span style="width: 200px;"><img src="images/commsyicons/32x32/star_unfilled.png" style="width:18px; height:18px"/></span>'.LF;
					}

					$votes = 0;

					if(isset($assessment[$index])) {
						$votes = $assessment[$index];
					}

					$assessment_text = '';
					if($votes == 1) {
						$assessment_text = $this->_translator->getMessage('COMMON_ASSESSMENT_INDEX');
					} else {
						$assessment_text = $this->_translator->getMessage('COMMON_ASSESSMENT');
					}
					$html_assessment_tooltip .= '	<span>' . $votes . ' ' . $assessment_text . '</span>'.LF;
					$html_assessment_tooltip .= '</div>'.LF;
				}

				$html_assessment_tooltip .= '	</div>'.LF;
			}

            $current_item = $list->getNext();
         }

		 if($current_context->isAssessmentActive()) {
			$html_assessment_tooltip .= '</div>'.LF;
			$html .= $html_assessment_tooltip;
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
   function _getItemAsHTML($item) {
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

   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    */
   function _compareWithSearchText($value){
      if ( !empty($this->_search_array) ){
         foreach ($this->_search_array as $search_text) {
            if ( mb_stristr($value,$search_text) ) {
               // $replace = '(:mainsearch_text:)$0(:mainsearch_text_end:)';
               include_once('functions/misc_functions.php');
                if ( getMarkerColor() == 'green') {
                  $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
                }
                else if (getMarkerColor() == 'yellow') {
                  $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
                }
               // $replace = '(:searchedtext:)$0(:searchedtext_end:)';
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu',$replace,$value);
               // $value = preg_replace('~'.preg_quote($search_text,'/').'~iu','*$0*',$value);
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


   function _getAdditionalActionsAsHTML(){
      $html  = '';
      return $html;
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
         case CS_USER_TYPE: $this->setAvailableUser($rubric_list);break;
      }
   }
   // @segment-end 86697

   // @segment-begin 28490  setSelectedRubric($rubric,$value)
   function setSelectedRubric($rubric,$value){
      switch($rubric){
         case CS_TOPIC_TYPE: $this->setSelectedTopic($value);break;
         case CS_INSTITUTION_TYPE: $this->setSelectedInstitution($value);break;
         case CS_GROUP_TYPE: $this->setSelectedGroup($value);break;
         case CS_USER_TYPE: $this->setSelectedUSer($value);break;
      }
   }
   // @segment-end 28490

   function getAvailableRubric($rubric){
      switch($rubric){
         case CS_TOPIC_TYPE: return $this->getAvailableTopics();break;
         case CS_INSTITUTION_TYPE: return $this->getAvailableInstitutions();break;
         case CS_GROUP_TYPE: return $this->getAvailableGroups();break;
         case CS_USER_TYPE: return $this->getAvailableUser();break;
      }
   }

   function getSelectedRubric($rubric){
      switch($rubric){
         case CS_TOPIC_TYPE: return $this->getSelectedTopic();break;
         case CS_INSTITUTION_TYPE: return $this->getSelectedInstitution();break;
         case CS_GROUP_TYPE: return $this->getSelectedGroup();break;
         case CS_USER_TYPE: return $this->getSelectedUser();break;
      }
   }


   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      return '';
   }

   function _getAdditionalFormFieldsAsHTML ($field_length=14.5) {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      $html = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            if ($context_item->withRubric($link_name[0])
                and (
                   $context_item->_is_perspective($link_name[0]) or
                   ($link_name[0] == CS_USER_TYPE and $this->_environment->getCurrentModule() == CS_TODO_TYPE) or
                   ($link_name[0] == CS_USER_TYPE and $this->_environment->getCurrentModule() == CS_DATE_TYPE)
                )
            ) {

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
                  case 'USER':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_USER').'<br />'.LF;
                     break;
                  default:
                     $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(2846) ';
                     break;
               }

               if ( isset($list)) {
                  // jQuery
                  //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
                  $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" id="submit_form">'.LF;
                  // jQuery
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
                     if ($link_name[0] == CS_USER_TYPE){
                        $text = $this->_Name2SelectOption($sel_item->getFullName());
                     }else{
                        $text = $this->_Name2SelectOption($sel_item->getTitle());
                     }
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
         if ( $with_links
              and $this->_environment->inProjectRoom()
              or ( !$this->_environment->inProjectRoom()
                   and ( $item->isPublished()
                         or $user->isUser()
                       )
                 )
            ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if ( ( empty($_GET['download'])
                      or $_GET['download'] != 'zip'
                    )
                    and
                       in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))
                  ) {
                  $this->_with_slimbox = true;
                  // jQuery
                  //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  // jQuery
               } else {
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
}
?>