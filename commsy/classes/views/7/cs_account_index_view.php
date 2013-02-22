<?PHP
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

$this->includeClass(INDEX_VIEW);
include_once('classes/cs_link.php');

/**
 *  class for CommSy list view: contact
 */
class cs_account_index_view extends cs_index_view {

   var $_selected_status = NULL;
   private $_selected_auth_source = NULL;
   private $_auth_source_array = array();
   private $_auth_source_count = 1;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_account_index_view ($params) {
      $this->cs_index_view($params);
      $this->setTitle($this->_translator->getMessage('COMMON_ACCOUNTS'));
      $current_user = $this->_environment->getCurrentUserItem();

      $user_manager = $this->_environment->getUserManager();
      $count_auth_source = $user_manager->getCountAuthSourceOfRoom($this->_environment->getCurrentContextID());
      if ( $count_auth_source > 1 ) {
         $this->_auth_source_count = $count_auth_source;
         $auth_source_manager = $this->_environment->getAuthSourceManager();
         $auth_source_manager->setContextLimit($this->_environment->getCurrentPortalID());
         $auth_source_manager->select();
         $auth_source_list = $auth_source_manager->get();
         if ( !$auth_source_list->isEmpty() ) {
            $auth_source_item = $auth_source_list->getFirst();
            while ($auth_source_item) {
               $this->_auth_source_array[$auth_source_item->getItemID()] = $auth_source_item;
               $auth_source_item = $auth_source_list->getNext();
            }
         }
      }
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selstatus'] = $this->getSelectedStatus();
      return $params;
   }


   function _getTableheadAsHTML() {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:40%;" colspan="2">';
      if ( $this->getSortKey() == 'name' ) {
         $params['sort'] = 'name_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'name_rev' ) {
         $params['sort'] = 'name';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'name';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                          $this->_module, $this->_function,
                          $params,
                          $this->_translator->getMessage('USER_NAME'),
                          '',
                          '',
                          $this->getFragment(),
                          '',
                          '',
                          '',
                          'class="head"'
                         );
      $html .= $picture;

      if ( $this->getSortKey() == 'user_id' ) {
         $params['sort'] = 'user_id_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'user_id_rev' ) {
         $params['sort'] = 'user_id';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'user_id';
         $picture ='';
      }
      $html .= ' ('.ahref_curl($this->_environment->getCurrentContextID(),
                               $this->_module, $this->_function,
                               $params,
                               $this->_translator->getMessage('USER_USER_ID'),
                               '',
                               '',
                               $this->getFragment(),
                               '',
                               '',
                               '',
                               'class="head"'
                              );
      $html .= $picture.')';


      $html .= '</td>'.LF;

      if (!$this->_environment->inProjectRoom()){
         $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
         if ( $this->getSortKey() == 'last_login' ) {
            $params['sort'] = 'last_login_rev';
            $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'last_login_rev' ) {
            $params['sort'] = 'last_login';
            $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         } else {
            $params['sort'] = 'last_login';
            $picture ='&nbsp;';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_module, $this->_function,
                             $params,
                             $this->_translator->getMessage('USER_LASTLOGIN'),
                             '',
                             '',
                             $this->getFragment(),
                             '',
                             '',
                             '',
                             'class="head"'
                            );
         $html .= $picture;
         $html .= '</td>'.LF;
         $html .= '      <td style="width:30%; font-size:8pt;" class="head">';
      }else{
         $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
         if ( $this->getSortKey() == 'status' ) {
            $params['sort'] = 'status_rev';
            $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'status_rev' ) {
            $params['sort'] = 'status';
            $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         } else {
            $params['sort'] = 'status';
            $picture ='&nbsp;';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_module,
                             $this->_function,
                             $params,
                             $this->_translator->getMessage('USER_STATUS'),
                             '',
                             '',
                             $this->getFragment(),
                             '',
                             '',
                             '',
                             'class="head"'
                            );
         $html .= $picture;
         $html .= '</td>'.LF;
         $html .= '      <td style="width:40%; font-size:8pt;" class="head">';
      }

      if ( $this->getSortKey() == 'email' ) {
         $params['sort'] = 'email_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'email_rev' ) {
         $params['sort'] = 'email';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'email';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                          $this->_module, $this->_function,
                          $params,
                          $this->_translator->getMessage('USER_EMAIL'),
                          '',
                          '',
                          $this->getFragment(),
                          '',
                          '',
                          '',
                          'class="head"'
                         );
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
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
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_SEARCH').'</div>';
      $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $html .= '<input style="width:180px; font-size:10pt; margin-bottom:5px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
      $html .= '<input style="margin-bottom:5px; font-size:10pt; width:55px;" name="option" value="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'" type="submit"/>'.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      return $html;
   }

    function getSearchText (){
       return $this->_search_text;
    }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['selstatus']) and !empty($params['selstatus']) and $params['selstatus'] != 7){
         $this->_additional_selects = true;
         $module = $this->_environment->getCurrentModule();
         if ($params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('USER_STATUS_MODERATOR');
         }elseif ($params['selstatus'] == 7){
            $status_text = $this->_translator->getMessage('ALL');
         }elseif ($params['selstatus'] == 8){
            $status_text = $this->_translator->getMessage('USER_USER');
         }elseif ($params['selstatus'] == 6){
            $status_text = $this->_translator->getMessage('USER_STATUS_REJECTED');
         }elseif ($params['selstatus'] == 1){
            $status_text = $this->_translator->getMessage('USER_REQUEST');
         }elseif ($params['selstatus'] == 2){
            $status_text = $this->_translator->getMessage('USER_NORMAL_USER');
         }elseif ($params['selstatus'] == 10){
            $status_text = $this->_translator->getMessage('USER_STATUS_CONTACT');
         }elseif ($params['selstatus'] == 21){
            $status_text = $this->_translator->getMessage('USER_STATUS_MODERATOR_COMMUNITY');
         }elseif ($params['selstatus'] == 22){
            $status_text = $this->_translator->getMessage('USER_STATUS_CONTACT_COMMUNITY');
         }elseif ($params['selstatus'] == 23){
            $status_text = $this->_translator->getMessage('USER_STATUS_MODERATOR_PROJECT');
         }elseif ($params['selstatus'] == 24){
            $status_text = $this->_translator->getMessage('USER_STATUS_CONTACT_PROJECT');
         }elseif ($params['selstatus'] == 25){
            $status_text = $this->_translator->getMessage('USER_STATUS_MODERATOR_ROOM');
         }elseif ($params['selstatus'] == 26){
            $status_text = $this->_translator->getMessage('USER_STATUS_CONTACT_ROOM');
         } elseif ($params['selstatus'] == 31) {
            $status_text = $this->_translator->getMessage('USER_STATUS_NO_MEMBERSHIP');
         }else{
            $status_text = $this->_translator->getMessage('COMMON_USERS');
         }
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $html_text .= '<span><a title="'.$status_text.'">'.chunkText($status_text,15).'</a></span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         unset($new_params['selstatus']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      if ( isset($params['sel_auth_source']) and !empty($params['sel_auth_source']) and $params['sel_auth_source'] != -1){
         $current_context = $this->_environment->getCurrentPortalItem();
         $auth_source_item = $current_context->getAuthSource($params['sel_auth_source']);
         if ( isset($auth_source_item) ) {
            $this->_additional_selects = true;
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_SOURCE').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>';
            $title_short = chunkText($auth_source_item->getTitle(),15);
            if ( $title_short != $auth_source_item->getTitle() ) {
               $html_text .= '<a title="'.$auth_source_item->getTitle().'">';
            }
            $html_text .= $title_short;
            if ( $title_short != $auth_source_item->getTitle() ) {
               $html_text .= '</a>';
            }
            $html_text .= '</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['sel_auth_source']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
      }
      return $html;
   }


   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      $html .='<table style="width:100%;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="width:70%; padding-top:5px; vertical-align:bottom;">'.LF;
      $html .='<div>'.LF;
      $html .= '<h2 class="pagetitle">'.LF;
      $tempMessage = $this->_translator->getMessage('ACCOUNT_INDEX');
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
      } else {
         $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
      }
      $html .= $tempMessage;

      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='<td style="width:28%; padding-top:5px; padding-left:0px; vertical-align:bottom; text-align:right;">'.LF;
#           $html .= '<span class="index_forward_links">'.$this->_getForwardLinkAsHTML().'</span>'.LF;
           // actions
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="width:70%; padding-top:5px; vertical-align:top; ">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(),
                                                                      $this->_environment->getCurrentModule(),
                                                                      $this->_environment->getCurrentFunction(),
                                                                      $params
                                                                     ).'" method="post">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
      }
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      if (!$this->_clipboard_mode){
         $html .= $this->_getContentAsHTML();
      }else{
         $html .= $this->_getClipboardContentAsHTML();
      }
      $html .= $this->_getTablefootAsHTML();
      $html .= '</table>'.LF;
      $html .= '</form>'.LF;
      $html .='</td>'.LF;

      $html .='<td style="vertical-align:top; padding-left:10px; padding-top:5px;">'.LF;
      $html .='<div id="commsy_panels" style="width:250px; margin-bottom:1px;">'.LF;
      $html .= $this->_getListInfosAsHTML($this->_translator->getMessage('ACCOUNT_INDEX'));
      $html .='</div>'.LF;
      $html .= '<div class="commsy_no_panel" style="width:250px; margin-bottom:0px;">'.LF;
      $title_string = '"'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'"';
      $desc_string = '""';
      $size_string = '"10"';
      $config_text ='true';
      $html .= '<form style="width:250px; padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
      $html .= $this->_getListSelectionsAsHTML();
      $html .= '</form>'.LF;

      $html .='</div>'.LF;
      $html .= '<script type="text/javascript">'.LF;
      $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'),Array(),null,null);'.LF;
      $html .= '</script>'.LF;
      $html .= $this->_getConfigurationOverviewAsHTML();
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $rubric_info_array = $room->getUsageInfoArray();
      if ( $this->_environment->inCommunityRoom() or $this->_environment->inProjectRoom() ){
         $room = $this->_environment->getCurrentContextItem();
         $config_text .=',false';
         $title_string .= ',"'.$room->getUsageInfoHeaderForRubric($this->_environment->getCurrentModule()).'"';
         $desc_string .= ',""';
         $size_string .= ',"10"';
         $html .= '<div class="commsy_no_panel" style="width:250px; margin-bottom:1px;">'.LF;
         $html .= $this->_getRubricInfoAsHTML($this->_environment->getCurrentModule());
         $html .='</div>'.LF;
      }
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= '</table>'.BRLF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


  function _getRubricInfoAsHTML($act_rubric){
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      if ( !empty($info_text) ) {
         $html='';
         $html .= '<div style="margin-bottom:1px; width:250px;">'.LF;
         $html .= '<div style="position:relative; top:12px;">'.LF;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '<img src="images/commsyicons_msie6/usage_info_3.gif"/>';
         } else {
            $html .= '<img src="images/commsyicons/usage_info_3.png"/>';
         }
         $html .= '</div>'.LF;
         $html .= '<div class="right_box_title" style="font-weight:bold;">'.$room->getUsageInfoHeaderForRubricForm($act_rubric).'</div>';
         $html .= '<div class="usage_info">'.LF;
         $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
         $html .= '</div>'.LF;
         $html .='</div>'.LF;
         return $html;
      }
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML ( $item,$pos ) {
      $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $html  = '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
      $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
      if ( in_array($key, $checked_ids) ) {
         $html .= ' checked="checked"'.LF;
         if ( in_array($key, $dontedit_ids) ) {
            $html .= ' disabled="disabled"'.LF;
         }
      }
      $html .= '/>'.LF;
      $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      </td>'.LF;

      $html .= '      <td '.$style.' style="font-size:10pt;" >'.$this->_getItemFullname($item).' ('.$this->_getItemUserID($item).')'.LF;
          if ( $item->isRequested() and $item->getUserComment() != '' ) {
             $html .= '<img src="images/private.gif" width="10" height="10" border="0" title="'.$item->getUserComment().'" alt=""/>';
          }
      if ( !$this->_environment->inProjectRoom() ) {
         $html .= BRLF.'<span class="disabled" style="font-size:8pt;">'.$this->_getStatus($item).'</span>'.LF;
      }
      $html .= '      </td>'.LF;
      if ( !$this->_environment->inProjectRoom() ) {
         $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemLastLogin($item).'</td>'.LF;
      }else{
         $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getStatus($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemEmail($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @return string name
    */
   function _getItemFullName($item){
      $name = $item->getFullname();
      $name_text = $this->_compareWithSearchText($name);
      $params = array();
      $params['iid'] = $item->getItemID();
      $name = ahref_curl( $this->_environment->getCurrentContextID(),
                          'account',
                          'detail',
                          $params,
                          $this->_text_as_html_short($name_text)
                        );
      unset($params);
      return $name;
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @return string name
    */
   function _getItemUserID ($item){
      $name = $item->getUserID();
      if ($this->_auth_source_count > 1 and $this->_environment->inProjectRoom() and !$this->_environment->getCurrentUser()->isRoot()) {
         $name .= '&nbsp;<span class="disabled" style="font-size: 8pt;">['.$this->_auth_source_array[$item->getAuthSource()]->getTitle().']</span>';
      }
      return $name;
   }

   /** get the email of the item
    * this method returns the item email in the right formatted style
    *
    * @return string email
    */
   function _getItemEmail ($item){
      $email = $item->getEmail();
      $email_text = $this->_compareWithSearchText($email);
      if (!$this->isPrintableView()) {
         $email = curl_mailto( $item->getEmail(), $this->_text_as_html_short(chunkText($email_text,40)));
      }
      return $email;
   }

   /** get the last login time of the account
    * this method returns the account last login time in the right formatted style
    *
    * @return string tast login time of the account
    */
   function _getItemLastlogin ($item) {
      // last login
      $datetime = $item->getLastLogin();
      if (empty($datetime) or $datetime == '0000-00-00 00:00:00') {
         $datetime = $this->_translator->getMessage('USER_NEVER_LOGIN');
      } else {
         $datetime = getDateTimeInLang($datetime);
      }
      $datetime = $this->_compareWithSearchText($datetime);
      return $datetime;
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
      $html .= '   <option value="1">'.$this->_translator->getMessage('USER_LIST_ACTION_DELETE_ACCOUNT').'</option>'.LF;
      $html .= '   <option value="2">'.$this->_translator->getMessage('USER_LIST_ACTION_LOCK_ACCOUNT').'</option>'.LF;
      $html .= '   <option value="3">'.$this->_translator->getMessage('USER_LIST_ACTION_FREE_ACCOUNT').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="11">'.$this->_translator->getMessage('USER_LIST_ACTION_STATUS_USER').'</option>'.LF;
      $html .= '   <option value="14">'.$this->_translator->getMessage('USER_LIST_ACTION_STATUS_MODERATOR').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="30">'.$this->_translator->getMessage('USER_LIST_ACTION_STATUS_CONTACT_MODERATOR').'</option>'.LF;
      $html .= '   <option value="31">'.$this->_translator->getMessage('USER_LIST_ACTION_STATUS_NO_CONTACT_MODERATOR').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if ( !$this->_environment->inProjectRoom()
           and !$this->_environment->inGroupRoom()
         ) {
         $html .= '   <option value="22">'.$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_ACCOUNT_PASSWORD').'</option>'.LF;
      }
      if ( !$this->_environment->inProjectRoom()
           and !$this->_environment->inGroupRoom()
         ) {
         $html .= '   <option value="23">'.$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_MERGE_ACCOUNTS').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      }
      $html .= '   <option value="21">'.$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_SEND').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<input type="hidden" name="mode" value="list_actions"/>'.LF;
      $html .= '<input type="submit" style="width:70px;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="padding: 0px 2px 2px 2px; font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="padding: 0px 2px 2px 2px; vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" style="padding: 0px 2px 2px 2px; vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }


   /** get the possible actions
    * this method returns the possible actions in the right formatted style
    *
    * @return string item date
    */
   function _getItemActions ($item) {
      $actions = '';

      if (empty($item) or $item->isDeleted()) {
         // do nothing
      } elseif (!empty($item) and $item->isA('user')) {
         if ($this->_environment->inProjectRoom()) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['status'] = 'user';
            $free_url_active = ahref_curl($this->_environment->getCurrentContextID(),
                                          'account',
                                          'automatic',
                                          $params,
                                          $this->_translator->getMessage('ADMIN_USER_FREE')
                                         );
            $params['status'] = 'reject';
            $reject_url_active = ahref_curl($this->_environment->getCurrentContextID(),
                                            'account',
                                            'automatic',
                                            $params,
                                            $this->_translator->getMessage('ADMIN_USER_LOCK')
                                           );
            unset($params);
            $free_url_not = '<span class="disabled">'.$this->_translator->getMessage('ADMIN_USER_FREE').'</span>';
            $reject_url_not = '<span class="disabled">'.$this->_translator->getMessage('ADMIN_USER_LOCK').'</span>';

            if ($item->isRequested() or $item->isRejected()) {
               if ($this->_environment->inCommunityRoom()) {
                  $free_url = $free_url_active;
                  $reject_url = $reject_url_active;
               } else {
                  $user_manager = $this->_environment->getUserManager();
                  $portal_user_item = $item->getRelatedCommSyUserItem();
                  if ($portal_user_item->isUser()) {
                     if ($item->isRejected()) {
                        $free_url = $free_url_active;
                        $reject_url = $reject_url_not;
                     } elseif ($item->isRequested()) {
                        $free_url = $free_url_active;
                        $reject_url = $reject_url_active;
                     } else {
                        $free_url = $free_url_not;
                        $reject_url = $reject_url_active;
                     }
                  } else {
                     $free_url = $free_url_not;
                     $reject_url = $reject_url_not;
                  }
               }
               $actions .= $free_url.' - '.$reject_url;
            } elseif ($item->isRejected()) {
               $params = array();
               $params['iid'] = $item->getItemID();
               $params['status'] = 'user';
               $free_url = ahref_curl($this->_environment->getCurrentContextID(),
                                      'account',
                                      'automatic',
                                      $params,
                                      $this->_translator->getMessage('ADMIN_USER_FREE')
                                     );
               unset($params);
               $reject_url = '<span class="disabled">'.$this->_translator->getMessage('ADMIN_USER_LOCK').'</span>';
               $actions .= $free_url.' - '.$reject_url;
            } else {
               $free_url = '<span class="disabled">'.$this->_translator->getMessage('ADMIN_USER_FREE').'</span>';
               $params = array();
               $params['iid'] = $item->getItemID();
               $params['status'] = 'reject';
               $reject_url = ahref_curl($this->_environment->getCurrentContextID(),'account','automatic',$params,$this->_translator->getMessage('ADMIN_USER_LOCK'));
               unset($params);
               $actions .= $free_url.' - '.$reject_url;
            }
         }
      }
      return $actions;
   }

   function _getStatus ($item) {
      $retour = '';
      if ($item->isModerator()) {
         $retour = $this->_translator->getMessage('USER_STATUS_MODERATOR');
      } elseif ($item->isUser()) {
         $retour = $this->_translator->getMessage('USER_STATUS_USER');
      } elseif ($item->isRequested()) {
         $retour = $this->_translator->getMessage('USER_STATUS_REQUESTED');
      } else {
         if ($this->_environment->inProjectRoom()) {
            $retour = $this->_translator->getMessage('USER_STATUS_REJECTED');
         } else {
            $last_login = $item->getLastlogin();
            if (!empty($last_login)) {
               $retour = $this->_translator->getMessage('USER_STATUS_REJECTED');
            } else {
               $retour = $this->_translator->getMessage('USER_STATUS_REJECTED');
            }
         }
      }
      if ($item->isContact()) {
         $retour .= ' ['.$this->_translator->getMessage('USER_STATUS_CONTACT_SHORT').']';
      }

      if ( $this->_auth_source_count > 1
           and !$this->_environment->inProjectRoom()
           and isset($this->_auth_source_array[$item->getAuthSource()])
         ) {
         $retour .= '&nbsp;|&nbsp;';
         $retour .= $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_SOURCE');
         $retour .= ': '.$this->_auth_source_array[$item->getAuthSource()]->getTitle().'';
      }

      return $retour;
   }

   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
   }

   function setSelectedAuthSource ($value) {
      $this->_selected_auth_source = (int)$value;
   }

   function getSelectedAuthSource () {
      return $this->_selected_auth_source;
   }


   function _getAdditionalFormFieldsAsHTML () {
      $width = '235';
      $html='';
      $selstatus = $this->getSelectedStatus();
      $html .= '<div style="text-align:left; font-size: 10pt;">';
      $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_STATUS').'</span>'.LF;

      $html .= '   <select name="selstatus" size="1" style="width: '. $width;
      // jQuery
      //$html .= 'px; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= 'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="7"';
      if ( !isset($selstatus) || $selstatus == 7 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;

      $html .= '      <option value="8"';
      if ( $selstatus == 8 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_USER').'</option>'.LF;

      $html .= '      <option value="8" disabled="disabled"';
      $html .= '>------------------</option>'.LF;

      $html .= '      <option value="6"';
      if ( isset($selstatus) and $selstatus == 6 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_STATUS_REJECTED').'</option>'.LF;

      $html .= '      <option value="1"';
      if ( isset($selstatus) and $selstatus == 1 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_REQUEST').'</option>'.LF;

      $html .= '      <option value="2"';
      if ( $selstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_NORMAL_USER').'</option>'.LF;

      $html .= '      <option value="3"';
      if ( isset($selstatus) and $selstatus == 3 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_STATUS_MODERATOR').'</option>'.LF;

      $html .= '      <option value="10"';
      if ( isset($selstatus) and $selstatus == 10 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('USER_STATUS_CONTACT').'</option>'.LF;

      if ( $this->_environment->inPortal() ) {

         $html .= '      <option value="8" disabled="disabled"';
         $html .= '>------------------</option>'.LF;

         $html .= '      <option value="21"';
         if ( isset($selstatus) and $selstatus == 21 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_MODERATOR_COMMUNITY').'</option>'.LF;

         $html .= '      <option value="22"';
         if ( isset($selstatus) and $selstatus == 22 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_CONTACT_COMMUNITY').'</option>'.LF;

         $html .= '      <option value="23"';
         if ( isset($selstatus) and $selstatus == 23 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_MODERATOR_PROJECT').'</option>'.LF;

         $html .= '      <option value="24"';
         if ( isset($selstatus) and $selstatus == 24 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_CONTACT_PROJECT').'</option>'.LF;

         $html .= '      <option value="25"';
         if ( isset($selstatus) and $selstatus == 25 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_MODERATOR_ROOM').'</option>'.LF;

         $html .= '      <option value="26"';
         if ( isset($selstatus) and $selstatus == 26 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_CONTACT_ROOM').'</option>'.LF;

         $html .= '      <option value="8" disabled="disabled"';
         $html .= '>------------------</option>'.LF;

         $html .= '      <option value="31"';
         if ( isset($selstatus) and $selstatus == 31 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('USER_STATUS_NO_MEMBERSHIP').'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .='</div>';

      if ( $this->_environment->inPortal()
           or $this->_environment->inCommunityRoom()
         ) {
         $current_context = $this->_environment->getCurrentPortalItem();
         $auth_source_list = $current_context->getAuthSourceList();
         if ( $auth_source_list->isNotEmpty()
              and $auth_source_list->getCount() > 1
            ) {
            $sel_auth_source = $this->getSelectedAuthSource();
            $html .= '<div style="text-align:left; font-size: 10pt;">';
            $html .= $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_SOURCE').BRLF;
            $html .= '   <select name="sel_auth_source" size="1" style="width: '. $width;
            // jQuery
            //$html .= 'px; font-size:8pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
            $html .= 'px; font-size:8pt; margin-bottom:5px;" id="submit_form">'.LF;
            // jQuery

            $html .= '      <option value="-1"';
            if ( !isset($sel_auth_source) || $sel_auth_source == -1 ) {
               $html .= ' selected="selected"';
            }
            $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;

            $html .= '      <option value="" disabled="disabled"';
            $html .= '>------------------</option>'.LF;

            $auth_source_item = $auth_source_list->getFirst();
            while ( $auth_source_item ) {
               $html .= '      <option value="'.$auth_source_item->getItemID().'"';
               if ( isset($sel_auth_source)
                    and $sel_auth_source == $auth_source_item->getItemID()
                  ) {
                  $html .= ' selected="selected"';
               }
               $html .= '>'.$auth_source_item->getTitle().'</option>'.LF;
               $auth_source_item = $auth_source_list->getNext();
            }

            $html .= '   </select>'.LF;
            $html .='</div>';
         }
      }

      return $html;
   }

     function _getConfigurationOptionsAsHTML(){
         $html = '<div id="netnavigation1">'.LF;
         $html .= '<div class="netnavigation" >'.LF;
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>';
         $html .= $this->_getConfigurationOverviewAsHTML($this->_environment->getCurrentFunction());

         $title_string ='"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'"';
         $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"';
         if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
            $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"';
         }
         $show_entry ='-1';
         if ($this->_environment->getCurrentFunction() == 'mail' or
             $this->_environment->getCurrentFunction() == 'agb' or
             $this->_environment->getCurrentFunction() == 'usageinfo' or
             $this->_environment->getCurrentFunction() == 'news' or
             $this->_environment->getCurrentFunction() == 'extra' or
             $this->_environment->getCurrentModule() == 'account' or
             $this->_environment->getCurrentFunction() == 'statistic'
            ){
            $show_entry = '0';
         }elseif ($this->_environment->getCurrentFunction() == 'preferences' or
             $this->_environment->getCurrentFunction() == 'portalhome' or
             $this->_environment->getCurrentFunction() == 'portalupload' or
             $this->_environment->getCurrentFunction() == 'rubric' or
             $this->_environment->getCurrentFunction() == 'defaults' or
             $this->_environment->getCurrentFunction() == 'home' or
             $this->_environment->getCurrentFunction() == 'color' or
             $this->_environment->getCurrentFunction() == 'listviews' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'time' or
             $this->_environment->getCurrentFunction() == 'room_opening' or
             $this->_environment->getCurrentFunction() == 'ims' or
             $this->_environment->getCurrentFunction() == 'privateroom_newsletter' or
             $this->_environment->getCurrentFunction() == 'authentication' or
             $this->_environment->getCurrentFunction() == 'language' or
             $this->_environment->getCurrentFunction() == 'backup'
            ){
            $show_entry = '1';
         }elseif ($this->_environment->getCurrentFunction() == 'dates' or
             $this->_environment->getCurrentFunction() == 'discussion' or
             $this->_environment->getCurrentFunction() == 'path' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'grouproom'
            ){
            $show_entry = '2';
         }else{
            $show_entry = '3';
         }
         $title_string .=',"'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'"';
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),'.$show_entry.',"1");'.LF;
         $html .= '</script>'.LF;
         return $html;
     }


     function _getConfigurationOverviewAsHTML(){
        $html='';
        $room = $this->_environment->getCurrentContextItem();
        $html .='<div class="commsy_no_panel" style="width:250px; margin-bottom:1px; padding:0px;">'.LF;
        $html .= '<div class="right_box">'.LF;
        $array = $this->_environment->getCurrentParameterArray();
        $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE_LINKS').'</div>';
        $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_COMMSY_CONFIGURE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;
        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/room_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/room_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'room_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/rubric_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/rubric_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/structure_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/structure_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'structure_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/account_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/account_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'account_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT')).LF;
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->isCommunityRoom()
           and $context_item->isOpenForGuests()
           and $context_item->withRubric(CS_MATERIAL_TYPE)
        ) {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/material_admin.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/material_admin.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'material_admin',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION')).LF;
        }
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/informationbox.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_INFORMATION_BOX').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/informationbox.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_INFORMATION_BOX').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'informationbox',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_INFORMATION_BOX')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/usage_info_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/usage_info_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'usageinfo',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/mail_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/mail_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'mail',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt; white-space:nowrap;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADDON_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        global $c_html_textarea;
        if ( $c_html_textarea ) {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/htmltextarea.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEXTAREA_TITLE').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/htmltextarea.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEXTAREA_TITLE').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'htmltextarea',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_TEXTAREA_TITLE')).LF;
        }
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->withWikiFunctions() and !$context_item->isServer()  and !$context_item->isGrouproom()) {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/pmwiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('WIKI_CONFIGURATION_LINK').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/pmwiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('WIKI_CONFIGURATION_LINK').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'wiki',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('WIKI_CONFIGURATION_LINK')).LF;
        }
        if ( $context_item->withChatLink() and !$context_item->isPortal()  and !$context_item->isGrouproom()) {
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/etchat.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CONFIGURATION_LINK').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/etchat.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CONFIGURATION_LINK').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'chat',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CHAT_CONFIGURATION_LINK')).LF;
        }
        if ( !$context_item->isGrouproom()){
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/template_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/template_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'template_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE')).LF;
        }
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/rubric_extras.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/rubric_extras.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_extras',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;


        $html .= '</div>'.LF;
        $html .='</div>'.LF;
        $html .= '</div>'.LF;
        return $html;
     }


     function _getConfigurationBoxAsHTML($act_fct){
      $html = '';
      $room = $this->_environment->getCurrentContextItem();
      $link_item = new cs_link();
      $link_item->setDescription($this->_translator->getMessage('HOME_ROOM_MEMBER_ADMIN_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_OVERVIEW.gif');
      $link_item->setTitle($this->_translator->getMessage('COMMON_COMMSY_CONFIGURE_HOME'));
      $link_item->setContextID($this->_environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('index');
      $params = array();
      $link_item->setParameter($params);
      unset($params);
      $html .= '<div class="netnavigation_panel_top">     '.LF;
      $html .= '<div style="padding-top:3px; padding-bottom:3px; padding-left:0px; padding-right:0px;"><ul style="list-style-type: none; font-size:8pt; padding-top:0px; margin-bottom:0px; padding-left:0px;">'.LF;
      $html .= '<li>'.LF;
      $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
      $html .= $link_item->getLinkIcon(25).LF;
      $html .= '</div><div style="padding-top:5px; text-align:left;">'.LF;
      $html .= $link_item->getLink(30).LF;
      $html .= '</div></div>'.LF;
      $html .='</li>'.LF;
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getAdminConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRoomConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;


      if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRubricConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      }

      $addonlist = $this->getAddOnConfigurationList();
      $element = $addonlist->getFirst();
      if ($element){
         $html .= '<div class="netnavigation_panel">     '.LF;
         $html .= '<noscript>';
         $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'</div>';
         $html .= '</noscript>';
         $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;

         while ($element){
            $html .= '<li>'.LF;
            $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction() or !$this->_with_modifying_actions ){
               $html .= $element->getIcon(25).LF;
            } else {
               $html .= $element->getLinkIcon(25).LF;
            }
            $html .= '</div><div style="padding-top:5px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } elseif ( !$this->_with_modifying_actions ){
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } else {
               $html .= $element->getShortLink().LF;
            }
            $html .= '</div></div>'.LF;
            $html .='</li>'.LF;
            $element = $addonlist->getNext();
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }



   function getRoomConfigurationList () {
      $room_link_list = '';
      include_once('include/inc_configuration_room_links.php');
      return $room_link_list;
   }

   function getAdminConfigurationList () {
      $admin_link_list = '';
      include_once('include/inc_configuration_admin_links.php');
      return $admin_link_list;
   }

   function getRubricConfigurationList () {
      $rubric_link_list = '';
      include_once('include/inc_configuration_rubric_links.php');
      return $rubric_link_list;
   }

   function getAddOnConfigurationList () {
        $addon_link_list = '';
      // addon configuration options
      include_once('include/inc_configuration_links_addon.php');
      return $addon_link_list;
   }
}
?>