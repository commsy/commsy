<?PHP
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

$this->includeClass(ROOM_INDEX_VIEW);
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: contact
 */
class cs_user_index_view extends cs_room_index_view {


   var $_selected_status = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param string  viewname               e.g. contact_index
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_user_index_view ($params) {
      $this->cs_room_index_view($params);
      $this->setTitle($this->_translator->getMessage('USER_HEADER'));
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
                         CS_USER_TYPE,
                         'index',
                         $params,
                         $image,
                         $this->_translator->getMessage('COMMON_LIST_PRINTVIEW'),
                         '_blank'
                         ).LF;
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $current_user->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/user_own.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_OWN_INFORMATION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/user_own.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_OWN_INFORMATION').'"/>';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                            CS_USER_TYPE,
                            'detail',
                            $params,
                            $image,
                            $this->_translator->getMessage('USER_OWN_INFORMATION')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/user_own_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_OWN_INFORMATION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/user_own_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_OWN_INFORMATION').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('USER_OWN_INFORMATION')).' "class="disabled">'.$image.'</a>'.LF;
      }
       if ($current_user->isModerator() and $this->_with_modifying_actions ) {
          if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
             $image = '<img src="images/commsyicons_msie6/22x22/config_account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_INDEX').'"/>';
          } else {
             $image = '<img src="images/commsyicons/22x22/config_account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_INDEX').'"/>';
          }
          $html .= '&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('ACCOUNT_INDEX')).LF;

      }else{
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/config_account_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NO_ACTION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/config_account_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NO_ACTION').'"/>';
         }
         $html .= '&nbsp;&nbsp;<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_NO_ACTION')).' "class="disabled">'.$image.'</a>'.LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:45%;" colspan="4">';
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
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('USER_NAME'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;


      $html .= '      <td style="width:25%; font-size:8pt;"  class="head">';
      $text = $this->_translator->getMessage('USER_TELEPHONE');
      $html .= $text;
      $html .= '</td>'.LF;
      $html .= '      <td style="width:30%; font-size:8pt;" class="head">';
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
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('USER_EMAIL'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="5"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="5" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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
      $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="2">'.$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_SEND').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos=0) {
      $shown_entry_number = $pos;
      $phone = $this->_compareWithSearchText($item->getTelephone());
      $handy = $this->_compareWithSearchText($item->getCellularphone());
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
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

         $html .= '      <td colspan="3"'.$style.' style="font-size:10pt;">'.$this->_getItemFullname($item).'</td>'.LF;
      }else{
         $html .= '      <td colspan="4"'.$style.' style="font-size:10pt;">'.$this->_getItemFullname($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">';
      if ( !empty($phone) ){
         $html .= $this->_text_as_html_short($phone).LF;
      }
      if (!empty($phone) and !empty($handy)) {
         $html .= BRLF;
      }
      if ( !empty($handy) ){
         $html .= $this->_text_as_html_short($handy).LF;
      }
      $html .= '</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemEmail($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getItemPicture($item){
      $html = '';
      $picture = $item->getPicture();
      if ( !empty($picture) ) {
         $disc_manager = $this->_environment->getDiscManager();
         if ($disc_manager->existsFile($picture)){
            $image_array = getimagesize($disc_manager->getFilePath().$picture);
            $pict_height = $image_array[1];
            if ($pict_height > 25){
               $height = 25;
            }else{
               $height = $pict_height;
            }
            $pict_width = $image_array[0];
            if ($pict_width > 25){
               $width = 25;
            }else{
               $width = $pict_height;
            }
         }else{
             $height = 25;
             $width = 25;
         }
         $params = array();
         $params['picture'] = $picture;
         $curl = curl($this->_environment->getCurrentContextID(),
                      'picture', 'getfile', $params,'');
         unset($params);
         $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="'.$curl.'" style="vertical-align:middle; width: '.$width.'px;"/>'.LF;
      }else{
#         $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="images/commsyicons/common/user_unknown_small.gif" style="vertical-align:middle;  height: 20px;"/>'.LF;
      }
      $params = array();
      $params['iid'] = $item->getItemID();
      $html = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'detail',
                           $params,
                           $html);
      $html ='';
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
                           CS_USER_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($name_text),
                           '','', '', '', '', '', '', '',
                           CS_USER_TYPE.$item->getItemID());

      ##################################################
      # messenger - BEGIN
      ##################################################

      global $c_commsy_domain;
      $host = $c_commsy_domain;
      global $c_commsy_url_path;
      $url_to_img = $host.$c_commsy_url_path.'/images/messenger';
      #$url_to_service = '???';

      $icq_number = $item->getICQ();
      if ( !empty($icq_number) ){
         $name .= '   <img style="vertical-align:middle;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=5" alt="ICQ Online Status" />'.LF;
      }
      /*
      $jabber_number = $item->getJabber();
      if ( !empty($jabber_number) ){
         $name .= '<a href="xmpp:'.$jabber_number.'">'.LF;
         $name .= '   <img style="vertical-align:middle;" srcC="'.$url_to_service.'/jabber/'.rawurlencode($jabber_number).'/onurl='.$url_to_img.'/jabber_short_online.gif/offurl='.$url_to_img.'/jabber_short_offline.gif/unknownurl='.$url_to_img.'/jabber_short_unknown.gif" alt="Jabber Online Status Indicator" />'.LF;
         $name .= '</a>'.LF;
      }
      */
      $msn_number = $item->getMSN();
      if ( !empty($msn_number) ){
         $name .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
         $name .= '   <img style="vertical-align:middle;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status" />'.LF;
         $name .= '</a>'.LF;
      }
      $skype_number = $item->getSkype();
      if ( !empty($skype_number) ){
         $name .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
         $name .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
         $name .= '   <img src="http://mystatus.skype.com/smallicon/'.rawurlencode($skype_number).'" style="vertical-align:middle; border: none;" width="16" height="16" alt="Skype Online Status" />'.LF;
         $name .= '</a>'.LF;
      }
      $yahoo_number = $item->getYahoo();
      if ( !empty($yahoo_number) ){
         $name .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
         $name .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=0/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
         $name .= '</a>'.LF;
      }

      ##################################################
      # messenger - END
      ##################################################

      unset($params);
      if ( !$this->_environment->inPrivateRoom() ) {
         $name .= $this->_getItemChangeStatus($item);
      }
      return $name;
   }

   function _getPrintableItemFullName($item){
      $name = $item->getFullname();
      $name_text = $this->_compareWithSearchText($name);
      $name = $this->_text_as_html_short($name_text);
      return $name;
   }


   /** get the email of the item
    * this method returns the item email in the right formatted style
    *
    * @return string email
    */
   function _getItemEmail ($item) {
     if ($item->isEmailVisible()) {
         $email = $item->getEmail();
         $email_text = $this->_compareWithSearchText($email);
         $email = curl_mailto( $item->getEmail(), $this->_text_as_html_short(chunkText($email_text,35)));
     } else {
         $email = '<span class="disabled">'.$this->_translator->getMessage('USER_EMAIL_HIDDEN').'</span>';
     }
      return $email;
   }


   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
   }

  function _getExpertSearchAsHTML(){
     $html  = '';
     $context_item = $this->_environment->getCurrentContextItem();
     $module = $this->_environment->getCurrentModule();
     if ($context_item->withActivatingContent()
          or $module == CS_DATE_TYPE
          or $module == CS_USER_TYPE
          or $module == CS_MATERIAL_TYPE
          or $module == CS_TODO_TYPE
          or $module == 'campus_search'
      ){
         $width = '235';
         $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
         $html .= '<div class="right_box">'.LF;
         $html .= '         <noscript>';
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
         $html .= '         </noscript>';
         $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
         $html .= $this->_getAdditionalRestrictionBoxAsHTML('14.5').LF;
         $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      return $html;
  }


   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['selstatus']) and !empty($params['selstatus']) and $params['selstatus'] == 3){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         if ($params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('USER_MODERATORS');
         }else{
            $status_text = $this->_translator->getMessage('COMMON_USERS');
         }
         $html_text .= '<span><a title="'.$status_text.'">'.$status_text.'</a></span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         unset($new_params['selstatus']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      return $html;
   }


   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $selstatus = $this->getSelectedStatus();
      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_STATUS').BRLF;
      // STATUS SELECTION FIELD

      // jQuery
      //$html .= '   <select name="selstatus" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="selstatus" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="2"';
      if ( empty($selstatus) || $selstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;

      $html .= '      <option value="3"';
      if ( !empty($selstatus) and $selstatus == 3 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('USER_MODERATORS');
      $html .= '>'.$text.'</option>'.LF;

      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->isCommunityRoom()) {
         $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="11"';
         if ( !empty($selstatus) and $selstatus == 11 ) {
            $html .= ' selected="selected"';
         }
         $text = $this->_translator->getMessage('USER_PROJECT_USER');
         $html .= '>'.$text.'</option>'.LF;
         $html .= '      <option value="12"';
         if ( !empty($selstatus) and $selstatus == 12 ) {
            $html .= ' selected="selected"';
         }
         $text = $this->_translator->getMessage('USER_PROJECT_CONTACT_MODERATOR');
         $html .= '>'.$text.'</option>'.LF;

      }
      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }

}
?>