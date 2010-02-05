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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy user detail-views
 */
class cs_user_detail_view extends cs_detail_view {

   var $_modified_items_array = array();

   var $_display_mod = 'normal';

   var $_sub_item_title_description = '';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_user_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   function setSubItem ($item) {
      $list = new cs_list();
      $list->add($item);
      $this->setSubItemList($list);
      $this->_sub_item_title_description = $this->_translator->getMessage('COMMON_READABLE_ONLY_USER',$this->_compareWithSearchText($item->getFullName()));
   }

   function addModifiedItemIDArray($type, $array){
      $this->_modified_items_array[$type] = $array;
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
      $html  = LF.'<!-- BEGIN OF USER ITEM DETAIL -->'.LF;

      $html .='<table style="width:100%; padding:0px; border-collapse:collapse;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="vertical-align:top; padding:0px;">'.LF;
      $formal_data = array();

      $title = $item->getTitle();
      if ( !empty($title) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_TITLE');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($title));
         $formal_data[] = $temp_array;
      }

      $birthday = $item->getBirthday();
      if ( !empty($birthday) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_BIRTHDAY');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($birthday));
         $formal_data[] = $temp_array;
      }
      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      }
      $formal_data = array();

      $email = $item->getEmail();
      $email_text = $this->_text_as_html_short($this->_compareWithSearchText($email));
      if ( !empty($email) and ( $item->isEmailVisible() or $this->_display_mod == 'admin') ) {
         if (isset($_GET['mode']) and $_GET['mode']=='print'){
            $emailDisplay = $email_text;
         }else{
            $emailDisplay = '<a title="'.$email.'" href="mailto:'.$email.'">'.$email_text.'</a>';
         }
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_EMAIL');
         $temp_array[] = $emailDisplay;
         $formal_data[] = $temp_array;
      } elseif (!$item->isEmailVisible()) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_EMAIL');
         $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('USER_EMAIL_HIDDEN').'</span>';
         $formal_data[] = $temp_array;
      }

      $telephone = $item->getTelephone();
      if ( !empty($telephone) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_TELEPHONE');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($telephone));
         $formal_data[] = $temp_array;
      }

      $cellularphone = $item->getCellularphone();
      if ( !empty($cellularphone) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_CELLULARPHONE');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($cellularphone));
         $formal_data[] = $temp_array;
      }
      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      }
      $formal_data = array();


      $street = $item->getStreet();
      if ( !empty($street) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_STREET');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($street));
         $formal_data[] = $temp_array;
      }

      $city = $item->getCity();
      if ( !empty($city) ) {
         $zipcode = $item->getZipCode();
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_CITY');
         $temp_array[] = $this->_text_as_html_short(trim($this->_compareWithSearchText($zipcode).' '.$this->_compareWithSearchText($city)));
         $formal_data[] = $temp_array;
      }


      $room = $item->getRoom();
      if ( !empty($room) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_ROOM');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($room));
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      }
      $formal_data = array();

      $organisation = $item->getOrganisation();
      if ( !empty($organisation) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_ORGANISATION');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($organisation));
         $formal_data[] = $temp_array;
      }

      $position = $item->getPosition();
      if ( !empty($position) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_POSITION');
         $temp_array[] = $this->_text_as_html_short($this->_compareWithSearchText($position));
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      }
      $formal_data = array();

      $html .='</td>'.LF;
      $picture = $item->getPicture();

      if ( !empty($picture) ) {
         $disc_manager = $this->_environment->getDiscManager();
         if ($disc_manager->existsFile($picture)){
            $image_array = getimagesize($disc_manager->getFilePath().$picture);
            $pict_width = $image_array[0];
            if ($pict_width > 150){
               $width = 150;
            }else{
               $width = $pict_width;
            }
         }else{
            $width = 150;
         }

         $html .='<td style="vertical-align:top; width: 150px;">'.LF;
         $params = array();
         $params['picture'] = $picture;
         $curl = curl($this->_environment->getCurrentContextID(),
                      'picture', 'getfile', $params,'');
         unset($params);
         $html .= '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="'.$curl.'" class="portrait2" style=" width: '.$width.'px;"/>'.LF;
         $html .='</td>'.LF;
      }else{
         $html .='<td style="vertical-align:top; width: 150px;">'.LF;
         $linktext = $this->_translator->getMessage('USER_PICTURE_NO_PICTURE',str_replace('"','&quot;',encode(AS_HTML_SHORT,$item->getFullName())));
         $html .= '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="images/commsyicons/common/user_unknown.gif" class="portrait2" title="'.$linktext.'"/>'.LF;
         $html .='</td>'.LF;
      }
      $html .='</tr>'.LF;
      $html .='</table>'.LF;
      $formal_data = array();

      ##################################################
      # messenger - BEGIN
      ##################################################

      $icq_number = $item->getICQ();
      $jabber_number = $item->getJabber();
      $msn_number = $item->getMSN();
      $skype_number = $item->getSkype();
      $yahoo_number = $item->getYahoo();
      if ( !empty($icq_number)
           or !empty($jabber_number)
           or !empty($msn_number)
           or !empty($skype_number)
           or !empty($yahoo_number)
         ) {
         global $c_commsy_domain;
         if ( strstr($c_commsy_domain,'http') ) {
            $host = mb_substr($c_commsy_domain,mb_strrpos($c_commsy_domain,'/')+1);
         } else {
            $host = $c_commsy_domain;
         }
         global $c_commsy_url_path;
         $url_to_img = $host.$c_commsy_url_path.'/images/messenger';
         $url_to_service = 'http://osi.danvic.co.uk';
         #$url_to_service = 'http://osi.techno-st.net:8000';
         #$url_to_service = 'http://www.the-server.net:8000'; ???
         #$url_to_service = 'http://technoserv.no-ip.org:8080';
         #$url_to_service = 'http://osi3.linux-tech.net:7777';
         #$url_to_service = 'http://www.funnyweb.dk:8080';
         #$url_to_service = 'http://crossbow.timb.us:5757';

         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_MESSENGER_NUMBERS');
         $first = true;
         $html_text = '<div style=" vertical-align:bottom; ">';
         if ( !empty($icq_number) ){
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=2" alt="ICQ Online Status Indicator" />'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$this->_text_as_html_short($this->_compareWithSearchText($icq_number)).')';
            $first = false;
         }
         /*
         if ( !empty($jabber_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<a href="skype:'.rawurlencode($jabber_number).'?chat">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="'.$url_to_service.'/jabber/'.rawurlencode($jabber_number).'/onurl='.$url_to_img.'/jabber_long_online.gif/offurl='.$url_to_img.'/jabber_long_offline.gif/unknownurl='.$url_to_img.'/jabber_long_unknown.gif" alt="Jabber Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$this->_text_as_html_short($jabber_number).')';
         }
         */
         if ( !empty($msn_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$this->_text_as_html_short($this->_compareWithSearchText($msn_number)).')';
         }
         if ( !empty($skype_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
            $html_text .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://mystatus.skype.com/smallclassic/'.rawurlencode($skype_number).'" alt="Skype Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$this->_text_as_html_short($this->_compareWithSearchText($skype_number)).')';
         }
         if ( !empty($yahoo_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
            $html_text .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
            $html_text .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=1/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$this->_text_as_html_short($this->_compareWithSearchText($yahoo_number)).')';
         }
         $html_text .='</div>'.LF;
         $temp_array[] = $html_text;
         $formal_data[] = $temp_array;
      }

      ##################################################
      # messenger - END
      ##################################################

      $homepage = $item->getHomepage();
      $homepage_text = $this->_text_as_html_short($homepage);
      $homepage_short = chunkText($homepage,60);
      if ( !empty($homepage) ) {
         if (isset($_GET['mode']) and $_GET['mode']=='print'){
            $homepage = $this->_text_as_html_short($this->_compareWithSearchText($homepage_short));
         }else{
            if ( strstr($homepage,'?') ) {
               $homepage_array = explode('?',$homepage);
               $homepage = $homepage_array[0].'?';
               if ( strstr($homepage_array[1],'&') ) {
                  $param_array = explode('&',$homepage_array[1]);
                  foreach ($param_array as $key => $value) {
                     $value = str_replace('=','EQUAL',$value);
                     $value = rawurlencode($value);
                     $value = str_replace('EQUAL','=',$value);
                     $param_array[$key] = $value;
                  }
                  $homepage .= implode('&',$param_array);
               }
            }
            $homepage = '<a href="'.$homepage.'" title="'.str_replace('"','&quot;',$homepage_text).'" target="_blank">'.$this->_text_as_html_short($this->_compareWithSearchText($homepage_short)).'</a>';
         }
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_HOMEPAGE');
         $temp_array[] = $homepage;
         $formal_data[] = $temp_array;
      }


     if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
     }


      // description of the user
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $html .='<div style="padding-top:10px; vertical-align:top;"></div>'.LF;
         $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $html .= $desc.LF;
      }
      $html  .= '<!-- END OF USER ITEM DETAIL -->'."\n\n";

      return $html;
   }

   function _getTitleAsHTML () {
     $html = '';
     if ( $this->_display_title ) {
         $item = $this->getItem();
         if ( isset($item) ){
            $html = $item->getFullname();
         } else {
            $html = 'NO ITEM';
         }
     }
      return $html;
   }

   function _getDetailItemActionsAsHTML($item){
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // edit
      $html .= $this->_getEditAction($item,$current_user);

      return $html.'&nbsp;&nbsp;&nbsp;';
   }

   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html  = $this->_getDetailItemActionsAsHTML($item);
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/print.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_LIST_PRINTVIEW'),
                                    '_blank'
                         ).LF;
      unset($params['mode']);
      $params = $this->_environment->getCurrentParameterArray();
      $params['download']='zip';
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/save.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DOWNLOAD').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/save.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DOWNLOAD').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_DOWNLOAD')).LF;
      unset($params['download']);
      unset($params['mode']);

      $params['mode'] = 'take_over';
      if ( $this->_environment->inPortal()
           and ( $current_user->isRoot()
                 or $current_user->isModerator()
               )
         ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/take_over.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname()).'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/take_over.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname()).'"/>';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$image,$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname())).LF;
      }
      return $html;
   }


   function _getSubItemAsHTML($item, $anchor_number) {
      $html = '';
      $formal_data = array();

      $temp_array = array();
      $temp_array[] = $this->_translator->getMessage('COMMON_ACCOUNT');
      $temp_array[] = $item->getUserID();
      $formal_data[] = $temp_array;
      unset($temp_array);

      $portal_item = $this->_environment->getCurrentPortalItem();
      if ( $portal_item->getCountAuthSourceListEnabled() != 1 ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_AUTH_SOURCE');
         $auth_source_item = $portal_item->getAuthSource($item->getAuthSource());
         $temp_array[] = $auth_source_item->getTitle();
         $formal_data[] = $temp_array;
         unset($temp_array);
      }

      if ( !$this->_environment->inPrivateRoom() ) {

         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('COMMON_STATUS');
         $current_context = $this->_environment->getCurrentContextItem();
         $status = $this->_getStatus($item,$current_context);
         $temp_array[] = $status;
         $formal_data[] = $temp_array;

         if ($item->isContact()) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('ROOM_CONTACT_SINGULAR');
            $temp_array[] = $this->_translator->getMessage('COMMON_YES');
            $formal_data[] = $temp_array;
         } elseif ($item->isModerator()) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('ROOM_CONTACT_SINGULAR');
            $temp_array[] = $this->_translator->getMessage('COMMON_NO');
            $formal_data[] = $temp_array;
         }
      }

      $current_context = $this->_environment->getCurrentContextItem();
      $language = $current_context->getLanguage();
      if (mb_strtoupper($language, 'UTF-8') == 'USER' or ($this->_display_mod == 'admin' and $this->_environment->inPortal())) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_LANGUAGE');
         switch ( cs_strtoupper($item->getLanguage()) )
         {
            case 'BROWSER':
               $temp_array[] = $this->_translator->getMessage('BROWSER');
               break;
            default:
               $temp_array[] = $this->_translator->getLanguageLabelTranslated($item->getLanguage());
               break;
         }
         $formal_data[] = $temp_array;
      }

      if ( $this->_environment->inCommunityRoom() ) {
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->isOpenForGuests() ) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('ACCOUNT_VISIBLE_PROPERTY');
            if ($item->isVisibleForAll()) {
               $temp_array[] = $this->_translator->getMessage('VISIBLE_ALWAYS');
            } else {
               $temp_array[] = $this->_translator->getMessage('VISIBLE_ONLY_LOGGED');
            }
            $formal_data[] = $temp_array;
         }
         unset($current_context);
      }

      if ( $item->isModerator() and !$this->_environment->inPrivateRoom() ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('ACCOUNT_EMAIL_MEMBERSHIP');

         $tempExtra = cs_strtoupper($item->getAccountWantMail());	// text_functions, respectively cs_user_item.php
         switch ( $tempExtra )
         {
            case 'YES':
               $temp_array[] = $this->_translator->getMessage('COMMON_YES');
               break;
            case 'NO':
               $temp_array[] = $this->_translator->getMessage('COMMON_NO');
               break;
            default:
               $temp_array[] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_user_detail_view(362) ";
               break;
         }

         $formal_data[] = $temp_array;

         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_MAIL_ROOM');

         $tempExtra = cs_strtoupper($item->getOpenRoomWantMail());
         switch( $tempExtra )
         {
            case 'YES':
               $temp_array[] = $this->_translator->getMessage('COMMON_YES');
               break;
            case 'NO':
               $temp_array[] = $this->_translator->getMessage('COMMON_NO');
               break;
            default:
               $temp_array[] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_user_detail_view(362) ";
               break;
         }

         $formal_data[] = $temp_array;

         if ($this->_environment->inCommunityRoom()) {
            $current_context = $this->_environment->getCurrentContextItem();
            if ($current_context->isOpenForGuests()) {
               $temp_array = array();
               $temp_array[] = $this->_translator->getMessage('ACCOUNT_EMAIL_MATERIAL');

               $tempExtra = cs_strtoupper($item->getPublishMaterialWantMail());
               switch( $tempExtra )
               {
                  case 'YES':
                     $temp_array[] = $this->_translator->getMessage('COMMON_YES');
                     break;
                  case 'NO':
                     $temp_array[] = $this->_translator->getMessage('COMMON_NO');
                     break;
                  default:
                     $temp_array[] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_user_detail_view(411) ";
                     break;
               }
               $formal_data[] = $temp_array;
            }
            unset($current_context);
         }
      }

     if ($this->_environment->inPortal()) {
        $related_user_array = array();
        $related_user_list = $item->getRelatedUserList();
        if ($related_user_list->isNotEmpty()) {
           $user_item = $related_user_list->getFirst();
           while ($user_item) {
              $related_user_array[$user_item->getContextID()] = $user_item;
              unset($user_item);
              $user_item = $related_user_list->getNext();
           }
           unset($related_user_list);
       }

       $temp_array = array();
       $formal_data[] = $temp_array;
       unset($temp_array);

       $temp_array = array();
       $temp_array[] = $this->_translator->getMessage('USER_ROOM_MEMBERSHIPS');
       $formal_data[] = $temp_array;
       unset($temp_array);

       $temp_array = array();
       $temp_array[] = $this->_translator->getMessage('COMMUNITYS');

       $community_list = $item->getRelatedCommunityList();
       if ($community_list->isNotEmpty()) {
          $community_item = $community_list->getFirst();
          $first = true;
          $temp_string = '';
          while ($community_item) {
            if ($first) {
               $first = false;
            } else {
               $temp_string .= BRLF;
            }
            $temp_string .= $community_item->getTitle();

            // status
                $status = $this->_getStatus($related_user_array[$community_item->getItemID()],$community_item);
            if (!empty($status)) {
               $temp_string .= ' ('.$status.')';
            }
            unset($community_item);
            $community_item = $community_list->getNext();
         }
         $temp_array[] = $temp_string;
         unset($temp_string);
         unset($community_list);
       } else {
          $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>';
       }
       $formal_data[] = $temp_array;
       unset($temp_array);

       $temp_array = array();
       $temp_array[] = $this->_translator->getMessage('PROJECTS');

       $room_list = $item->getRelatedProjectList();
       if ($room_list->isNotEmpty()) {
          $room_item = $room_list->getFirst();
          $first = true;
          $temp_string = '';
          while ($room_item) {
            if ($first) {
               $first = false;
            } else {
               $temp_string .= BRLF;
            }
            $temp_string .= $room_item->getTitle();
            // room status
                if ($room_item->isLocked()) {
                   $temp_string .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']'.LF;
                } elseif ($room_item->isClosed()) {
                   $temp_string .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']'.LF;
                }
            // status
                $status = $this->_getStatus($related_user_array[$room_item->getItemID()],$room_item);
            if (!empty($status)) {
               $temp_string .= ' ('.$status.')';
            }
            unset($room_item);
            $room_item = $room_list->getNext();
         }
         $temp_array[] = $temp_string;
         unset($temp_string);
         unset($room_list);
       } else {
          $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>';
       }
       $formal_data[] = $temp_array;
       unset($temp_array);
       unset($related_user_list);
      }

      if ( $this->_environment->inPrivateRoom() ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('CONFIGURATION_AUTOSAVE_STATUS');
         if ( $item->isAutoSaveOn() ) {
            $temp_array[] = $this->_translator->getMessage('COMMON_YES');
         } else {
            $temp_array[] = $this->_translator->getMessage('COMMON_NO');
         }
         $formal_data[] = $temp_array;
      }

      if (count($formal_data) > 0) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('USER_PREFERENCES_NO_ENTRIES').'</span>'.LF;
      }
      unset($formal_data);

      return $html;
   }

   function _getStatus ($user_room_item, $room_item) {
     $status = '';
      if ($user_room_item->isModerator()) {
         $status = $this->_translator->getMessage('USER_STATUS_MODERATOR');
      } elseif ($user_room_item->isUser()) {
         $status = $this->_translator->getMessage('USER_STATUS_USER');
      } elseif ($user_room_item->isRequested()) {
         $status = $this->_translator->getMessage('USER_STATUS_REQUESTED');
      } else {
         if (!$room_item->isCommunityRoom()) {
            $status = $this->_translator->getMessage('USER_STATUS_CLOSED');
         } else {
            $last_login = $user_room_item->getLastlogin();
            if (!empty($last_login)) {
               $status = $this->_translator->getMessage('USER_STATUS_CLOSED');
            } else {
               $status = $this->_translator->getMessage('USER_STATUS_REJECT');
            }
         }
      }
     return $status;
   }

   function _getSubItemDetailActionsAsHTML ($subitem) {
      $user = $this->_environment->getCurrentUserItem();
      $mod  = $this->_with_modifying_actions;
      $item = $this->getItem();

      $html = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold; font-size:10pt;">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
      $html .= '<div class="right_box_main">'.LF;
      if ( $item->mayEdit($user) and $mod ) {
         if ($this->_display_mod == 'admin' and $this->_environment->getCurrentModule() == 'account') {
            if ( $this->_environment->inPortal() and $user->isModerator() ) {
               $current_portal_item = $this->_environment->getCurrentPortalItem();
               $auth_source_item = $current_portal_item->getAuthSource($subitem->getAuthSource());
               // must be addAccount not PasswordChange, because
               // for admin change password for user we need super user access to the auth source.
               // passwordChange ist for user change his/her own password
               if ( ( $auth_source_item->isCommSyDefault()
                      and $auth_source_item->allowChangePassword()
                    ) or $auth_source_item->allowAddAccount()
                  ) {
                  $params = array();
                  $params['iid'] = $item->getItemID();
                  $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                            $this->_environment->getCurrentModule(),
                                            'password',
                                            $params,
                                            $this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE')).BRLF;
                  unset($params);
               } else {
                  $html .= '<span class="disabled">> '.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'</span>'.BRLF;
               }
            }

            $params = array();
            $params['iid'] = $item->getItemID();
            $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                               $this->_environment->getCurrentModule(),
                               'status',
                               $params,
                               $this->_translator->getMessage('ACCOUNT_STATUS_CHANGE')).BRLF;
            unset($params);
         }

         $private_room_manager = $this->_environment->getPrivateRoomManager();
         $own_room = $private_room_manager->getRelatedOwnRoomForUser($item,$this->_environment->getCurrentPortalID());
         if ( $this->_environment->inPortal() and $this->_environment->getCurrentModule() == 'account' and $user->isModerator() ){
            $params = array();
            if ( isset($own_room) and $own_room->isLocked() ) {
               $params['iid'] = $own_room->getItemID();
               $params['automatic'] = 'unlock';
               $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('PRIVATEROOM_UNLOCK')).BRLF;
               unset($params);
            } elseif ( isset($own_room) ) {
               $params['iid'] = $own_room->getItemID();
               $params['automatic'] = 'lock';
               $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('PRIVATEROOM_LOCK')).BRLF;
               unset($params);
            }
         }
         if ( $user->isRoot() and isset($own_room) ) {
            $params = array();
            $params['iid'] = $own_room->getItemID();
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','export',$params,$this->_translator->getMessage('PRIVATEROOM_EXPORT')).BRLF;
            unset($params);
         }

         if ($this->_environment->inCommunityRoom()) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                      $this->_environment->getCurrentModule(),
                                      'preferences',
                                      $params,
                                      $this->_translator->getMessage('USER_EDIT_PREFERENCES')).BRLF;
            unset($params);
         }

         // project room
         elseif ( $this->_environment->inProjectRoom()
                  or $this->_environment->inPrivateRoom()
                  or $this->_environment->inGroupRoom()
                 ) {
            $current_context = $this->_environment->getCurrentContextItem();
            $lang = $current_context->getLanguage();
            if ( $user->isModerator() or $current_context->isLanguageFix() ) {
               $params = array();
               $params['iid'] = $item->getItemID();
               $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'preferences',
                                     $params,
                                     $this->_translator->getMessage('USER_EDIT_PREFERENCES')).BRLF;
               unset($params);
            } else {
               $html .= '<span class="disabled">> '.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'</span>'.BRLF;
            }
         }
      } elseif ($this->_environment->inCommunityRoom() or $this->_environment->inProjectRoom() or $this->_environment->inPrivateRoom()) {
         $html .= '<span class="disabled">> '.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'</span>'.BRLF;
      }

      // last moderator
      $last_moderator = false;
      if ( $item->isModerator() ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($this->_environment->getCurrentContextID());
         $user_manager->setModeratorLimit();
         $moderator_count = $user_manager->getCountAll();
         if ($moderator_count == 1) {
            $last_moderator = true;
         }
      }


      if ( $item->getItemID() == $user->getItemID()
           and !$this->_environment->inPrivateRoom()
           and !$last_moderator
         ) {
         $params['iid'] = $item->getItemID();
         $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                   $this->_environment->getCurrentModule(),
                                   'close',
                                   $params,
                                   $this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION')).BRLF;
         unset($params);
      } elseif (!$this->_environment->inPrivateRoom()) {
         $html .= '<span class="disabled">> '.$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION').'</span>'.BRLF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _is_always_visible ($rubric) {
      return ($rubric == CS_TOPIC_TYPE or $rubric == CS_INSTITUTION_TYPE);
   }


   function _getAllLinkedItemsAsHTML ($spaces=0) {
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $item = $this->getItem();
      if ($this->_environment->inCommunityRoom()){
         $link_items = $item->getLinkItemList(CS_INSTITUTION_TYPE);
      }else{
         $link_items = $item->getLinkItemList(CS_GROUP_TYPE);
      }
      $count_link_item = $link_items->getCount();
      if ($this->_environment->inCommunityRoom()){
         $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_INSTITUTIONS').' ('.$count_link_item.')"';
      }else{
         $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_GROUPS').' ('.$count_link_item.')"';
      }
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      if($current_context->isNetnavigationShowExpanded()){
         $this->_right_box_config['config_string'] .= $separator.'true';
      } else {
         $this->_right_box_config['config_string'] .= $separator.'false';
      }
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $connections = $this->getRubricConnections();
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_ENTRIES').'</div>';
      $html .= '         </noscript>';
      $html .='     <div class="right_box_main">     '.LF;
      if ($link_items->isEmpty()) {
         $html .= '  <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'&nbsp;</div>'.LF;
      } else {
         $html .='     <ul style="list-style-type: circle; font-size:8pt; list-style-position:inside; margin:0px; padding:0px;">'.LF;
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
                     $img = 'images/commsyicons/netnavigation/announcement.png';
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
                     $img = 'images/commsyicons/netnavigation/date.png';
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
                     $img = 'images/commsyicons/netnavigation/discussion.png';
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
                     $img = 'images/commsyicons/netnavigation/group.png';
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
                     $img = '';
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
                     $img = 'images/commsyicons/netnavigation/material.png';
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
                     $img = '';
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
                     $img .= 'images/commsyicons/netnavigation/todo.png';
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
                     $img .= 'images/commsyicons/netnavigation/topic.png';
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('COMMON_ONE_USER');
                     $img .= 'images/commsyicons/netnavigation/user.png';
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view(692) ';
                     $img .= '';
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
               $html .= '   <li  style="padding-left:5px; list-style-type:none;">';

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
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
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
                   $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       chunkText($linked_item->getTitle(),35),
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
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
                                       $link_creator_text,
                                       '_self',
                                       $fragment);
                  $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       chunkText($linked_item->getTitle(),35),
                                       $link_creator_text,
                                       '_self',
                                       $fragment);
                  unset($params);
               }


               $html .= '</li>'.LF;
            }
            $link_item = $link_items->getNext();
         }
         $html .= '</ul>'.LF;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $current_user = $this->_environment->getCurrentUserItem();
      if ($this->_environment->inCommunityRoom()){
         $message = $this->_translator->getMessage('COMMON_INSTITUTION_ATTACH');
      }else{
         $message = $this->_translator->getMessage('COMMON_GROUP_ATTACH');
      }
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['attach_view'] = 'yes';
         $params['attach_type'] = 'item';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             $this->_environment->getCurrentFunction(),
                             $params,
                             $message
                             ).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$message.'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .='      </div>';
      $html .='      </div>';
      $html .='      </div>';
      return $html;
   }

/*   function _getAllLinkedItemsAsHTML ($spaces=0) {
      $connections = $this->getRubricConnections();
      $item = $this->getItem();
      $html = '';
      $html .= '<div id="netnavigation'.$item->getItemID().'" style="height:250px;">'.LF;
      $html .= '<div class="netnavigation" >'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'</div>';
      $html .= '         </noscript>';
      $title_string = '';
      foreach ( $connections as $connection ) {
         $link_items = $item->getLinkItemList($connection);
         $context = $this->_environment->getCurrentContextItem();
         if ($connection == CS_GROUP_TYPE OR $connection == CS_INSTITUTION_TYPE) {
            $context = $this->_environment->getCurrentContextItem();
            $html .='		<div class="netnavigation_panel">     '.LF;
            $text = '';
            switch ( strtoupper($connection) )
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
            if (empty($title_string)){
               $title_string .= '"'.$text;
               $title_string .= ' ('.$link_items->getCount().')"';
            }else{
               $title_string .= ',"'.$text;
               $title_string .= ' ('.$link_items->getCount().')"';
            }
            $html .= '         <noscript>';
            $html .= '<div class="netnavigation_title">'.$text.'('.$link_items->getCount().')</div>';
            $html .= '         </noscript>';
            $html .= $this->_getLinkedItemsAsHTML($item, $link_items, $connection,
                      true,
                      true,
                      $this->_has_attach_link($connection));
            $html .='</div> ';
         }
      }
      foreach ($this->_modified_items_array as $connection => $ids) {
         switch ( strtoupper($connection) )
         {
         case 'ANNOUNCEMENT':
            $text = $this->_translator->getMessage('ANNOUNCEMENTS');
            break;
         case 'DATE':
            $text = $this->_translator->getMessage('DATES');
            break;
         case 'DISCUSSION':
            $text = $this->_translator->getMessage('DISCUSSIONS');
            break;
         case 'GROUP':
            $text = $this->_translator->getMessage('GROUPS');
            break;
         case 'INSTITUTION':
            $text = $this->_translator->getMessage('INSTITUTIONS');
            break;
         case 'MATERIAL':
            $text = $this->_translator->getMessage('MATERIALS');
            break;
         case 'PROJECT':
            $text = $this->_translator->getMessage('PROJECTS');
            break;
         case 'TODO':
            $text = $this->_translator->getMessage('TODOS');
            break;
         case 'TOPIC':
            $text = $this->_translator->getMessage('TOPICS');
            break;
         case 'USER':
            $text = $this->_translator->getMessage('USERS');
            break;
         default:
            $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_user_detail_view(818) ';
            break;
         }
         if (empty($title_string)){
            $title_string .= '"'.$text;
            $title_string .= ' ('.sizeof($ids).')"';
         }else{
            $title_string .= ',"'.$text;
            $title_string .= ' ('.sizeof($ids).')"';
         }
      }
      $html .= $this->_getModifiedItemsAsHTML($item);
      $html .='		</div>';
      $html .='		</div>';
      $html .='		<!-- END OF MENU -->';
      $html .= '<script type="text/javascript">'.LF;
      $title_string = str_replace('</','&COMMSYDHTMLTAG&',$title_string);
      $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),"0","'.$item->getItemID().'");'.LF;
      $html .= '</script>'.LF;
      return $html;
   }*/

   function _getModifiedItemsAsHTML ($item) {
      $html = '';
      foreach ($this->_modified_items_array as $connection => $ids) {
         $module = Type2Module($connection);
         $manager = $this->_environment->getItemManager();
         $html .=' <div class="netnavigation_panel">     '.LF;
         switch ( mb_strtoupper($connection, 'UTF-8') )
         {
            case 'ANNOUNCEMENT':
               $text = $this->_translator->getMessage('ANNOUNCEMENTS');
               break;
            case 'DATE':
               $text = $this->_translator->getMessage('DATES');
               break;
            case 'DISCUSSION':
               $text = $this->_translator->getMessage('DISCUSSIONS');
               break;
            case 'GROUP':
               $text = $this->_translator->getMessage('GROUPS');
               break;
            case 'INSTITUTION':
               $text = $this->_translator->getMessage('INSTITUTIONS');
               break;
            case 'MATERIAL':
               $text = $this->_translator->getMessage('MATERIALS');
               break;
            case 'PROJECT':
               $text = $this->_translator->getMessage('PROJECTS');
               break;
            case 'TODO':
               $text = $this->_translator->getMessage('TODOS');
               break;
            case 'TOPIC':
               $text = $this->_translator->getMessage('TOPICS');
               break;
            case 'USER':
               $text = $this->_translator->getMessage('USERS');
               break;
            default:
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_user_detail_view(818) ';
               break;
         }
         $html .= '         <noscript>';
         $html .= '<div class="netnavigation_title">'.$text.'('.sizeof($ids).')</div>';
         $html .= '         </noscript>';

         $html .= '<div>'.LF;
         $html .= '<ul style="list-style-type: circle; font-size:8pt;">'.LF;
         $count = sizeof($ids);
         $limit = 0;
         $count_shown = 0;
         $show = true;
         if ( $count > 10 ) {
            $limit = 9;
            $count_shown = 1;
         }
         if ($count == 0){
            $html .= '   <li><a><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></a></li>'.LF;
         }
         foreach ($ids as $id){
            if ( $show ){
               $item = $manager->getItem($id);
               if ( isset($item) ) {
                  $type = $item->getItemType();
                  $rubric_manager = $this->_environment->getManager($type);
                  $rubric_item = $rubric_manager->getItem($id);
                  // Get link creator
                  $link_creator = $rubric_item->getCreatorItem();
                  $link_created = $this->_translator->getDateInLang($rubric_item->getCreationDate());
                  $link_creator_text = $this->_translator->getMessage('COMMON_LINK_CREATOR').' '.
                                       $this->_text_as_html_short($link_creator->getFullname()).', '.
                                       $this->_text_as_html_short($link_created);

                  switch ( $connection ) {
                     case CS_DISCARTICLE_TYPE:
                        $linked_iid = $rubric_item->getDiscussionID();
                        break;
                     default:
                        $linked_iid = $rubric_item->getItemID();
                  }
                  $html .= '   <li>';
                  $params = array();
                  $params['iid'] = $linked_iid;
                  $user = $this->_environment->getCurrentUser();
                  if ($rubric_item->isNotActivated() and !($rubric_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                      $activating_date = $rubric_item->getActivatingDate();
                      if (strstr($activating_date,'9999-00-00')){
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                      }else{
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($rubric_item->getActivatingDate()).')';
                      }
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                           $module,
                                           'detail',
                                           $params,
                                           chunkText($this->_text_as_html_short($rubric_item->getTitle()),27),
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
                     $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       chunkText($this->_text_as_html_short($rubric_item->getTitle()),27),
                                       $link_creator_text);
                     unset($params);
                  }
                  $html .= '</li>'.LF;
                  if ( $limit > 0 ) {
                     $count_shown++;
                  }
                  if ( $count_shown > $limit ) {
                     $show = false;
                  }
               }
            }
         }
         if ( $limit > 0 ) {
            $html .= '   <li>';
            $params = array();
            $params['ref_user'] = $this->_item->getItemID();
            $params['mode'] = 'attached';
            $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                 $module,
                                 'index',
                                 $params,
                                 $this->_translator->getMessage('RUBRIC_ALL_ATTACHMENTS', count($ids)));
            unset($params);
            $html .= '</li>'.LF;
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;
         $html .=' </div>';
      }
      return $html;
   }

   function setDisplayModToAdmin () {
       $this->_display_mod = 'admin';
   }

   function setDisplayModToNormal () {
       $this->_display_mod = 'normal';
   }

   function getTitle () {
     $retour  = '';
     $retour .= $this->_item->getFullname();
     $this->_display_title = false;
     return $retour;
   }

   function getAccountActionsAsHTML($item= NULL){
      $current_context = $this->_environment->getCurrentContextItem();
      $user = $this->_environment->getCurrentUserItem();
      $annotated_item = $this->getItem();
      $annotated_item_type = $annotated_item->getItemType();
      $mod  = $this->_with_modifying_actions;
      $html  = '';

      if ( $item->mayEdit($user) and $mod ) {
         if ($this->_display_mod == 'admin' and $this->_environment->getCurrentModule() == 'account') {
            if ( $this->_environment->inPortal() and $user->isModerator() ) {
               $current_portal_item = $this->_environment->getCurrentPortalItem();
               $auth_source_item = $current_portal_item->getAuthSource($subitem->getAuthSource());
               // must be addAccount not PasswordChange, because
               // for admin change password for user we need super user access to the auth source.
               // passwordChange ist for user change his/her own password
               if ( $auth_source_item->allowAddAccount() ) {
                  $params = array();
                  $params['iid'] = $item->getItemID();
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $image = '<img src="images/commsyicons_msie6/22x22/config/account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'"/>';
                  } else {
                     $image = '<img src="images/commsyicons/22x22/config/account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'"/>';
                  }
                  $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                            $this->_environment->getCurrentModule(),
                                            'password',
                                            $params,
                                            $image,
                                            $this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE')).LF;
                  unset($params);
               } else {
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $image = '<img src="images/commsyicons_msie6/22x22/config/account_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'"/>';
                  } else {
                     $image = '<img src="images/commsyicons/22x22/config/account_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'"/>';
                  }
                  $html .= '<span class="disabled"> '.$this->_translator->getMessage('ACCOUNT_PASSWORD_CHANGE').'</span>'.LF;
               }
            }

            $params = array();
            $params['iid'] = $item->getItemID();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/config/account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_STATUS_CHANGE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/config/account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ACCOUNT_STATUS_CHANGE').'"/>';
            }
            $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                               $this->_environment->getCurrentModule(),
                               'status',
                               $params,
                               $image,
                               $this->_translator->getMessage('ACCOUNT_STATUS_CHANGE')).LF;
            unset($params);
         }

         $private_room_manager = $this->_environment->getPrivateRoomManager();
         $own_room = $private_room_manager->getRelatedOwnRoomForUser($item,$this->_environment->getCurrentPortalID());
         if ( $this->_environment->inPortal() and $this->_environment->getCurrentModule() == 'account' and $user->isModerator() ){
            $params = array();
            if ( isset($own_room) and $own_room->isLocked() ) {
               $params['iid'] = $own_room->getItemID();
               $params['automatic'] = 'unlock';
               $html .= ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('PRIVATEROOM_UNLOCK')).' '.LF;
               unset($params);
            } elseif ( isset($own_room) ) {
               $params['iid'] = $own_room->getItemID();
               $params['automatic'] = 'lock';
               $html .= ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('PRIVATEROOM_LOCK')).' '.LF;
               unset($params);
            }
         }
         if ( $user->isRoot() and isset($own_room) ) {
            $params = array();
            $params['iid'] = $own_room->getItemID();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/config/rubric_extras.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/config/rubric_extras.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
            }
            $html .=  ahref_curl($this->_environment->getCurrentContextID(),'configuration','export',$params,$image,$this->_translator->getMessage('PRIVATEROOM_EXPORT')).' '.LF;
            unset($params);
         }

         if ($this->_environment->inCommunityRoom()) {
            $params = array();
            $params['iid'] = $item->getItemID();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
            }
            $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                      $this->_environment->getCurrentModule(),
                                      'preferences',
                                      $params,
                                      $image,
                                      $this->_translator->getMessage('USER_EDIT_PREFERENCES')).' '.LF;
            unset($params);
         }

         // project room
         elseif ( $this->_environment->inProjectRoom()
                  or $this->_environment->inPrivateRoom()
                  or $this->_environment->inGroupRoom()
                 ) {
            $current_context = $this->_environment->getCurrentContextItem();
            $lang = $current_context->getLanguage();
            if ( $user->isModerator() or $current_context->isLanguageFix() ) {
               $params = array();
               $params['iid'] = $item->getItemID();
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
               }
               $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'preferences',
                                     $params,
                                     $image,
                                     $this->_translator->getMessage('USER_EDIT_PREFERENCES')).LF;
               unset($params);
            } else {
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/config_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/config_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
               }
               $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('USER_EDIT_PREFERENCES')).' "class="disabled">'.$image.'</a>'.LF;
            }
         }
      } elseif ($this->_environment->inCommunityRoom() or $this->_environment->inProjectRoom() or $this->_environment->inPrivateRoom()) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/config_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/config_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('USER_EDIT_PREFERENCES').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('USER_EDIT_PREFERENCES')).' "class="disabled">'.$image.'</a>'.LF;
      }

      // last moderator
      $last_moderator = false;
      if ( $item->isModerator() ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($this->_environment->getCurrentContextID());
         $user_manager->setModeratorLimit();
         $moderator_count = $user_manager->getCountAll();
         if ($moderator_count == 1) {
            $last_moderator = true;
         }
      }


      if ( $item->getItemID() == $user->getItemID()
           and !$this->_environment->inPrivateRoom()
           and !$last_moderator
         ) {
         $params['iid'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                   $this->_environment->getCurrentModule(),
                                   'close',
                                   $params,
                                   $image,
                                   $this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION')).LF;
         unset($params);
      } elseif (!$this->_environment->inPrivateRoom()) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_CLOSE_PARTICIPATION')).' "class="disabled">'.$image.'</a>'.LF;
     }
     return $html;
   }


   function _getSubItemsAsHTML($item){
      $current_user = $this->_environment->getCurrentUserItem();
      $html ='';
      if ($current_user->isModerator() or $current_user->isRoot() or ($current_user->getItemID() == $item->getItemID()) ){
         $html = '<!-- BEGIN OF SUB ITEM DETAIL VIEW -->'.LF.LF;
         $current_item = $item;
         $html .='<div class="detail_annotation_headline" style="margin-top:60px;">'.LF;
         $html .= '<div style="float:right">';
         $html .= $this->getAccountActionsAsHTML($item);
         $html .= '</div>';
         $html .= '<h3 class="annotationtitle">'.$this->_getSubItemTitleAsHTML($current_item, '1');
         $html .= '</h3>'.LF;
         $html .='</div>'.LF;
         $html .='<div class="detail_content" style=" margin-top: 5px; border-top:1px solid #B0B0B0; border-left:0px solid #B0B0B0; border-right:0px solid #B0B0B0; border-bottom:0px solid #B0B0B0;">'.LF;
         $html .= $this->_getSubItemAsHTML($current_item,1).LF;
         $html .='</div>'.LF;
         $html .='<div style="clear:both;">'.LF;
         $html .='</div>'.LF;
         $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      }
      return $html;
   }


 function _getConfigurationOverviewAsHTML(){
        $html='';
        $room = $this->_environment->getCurrentContextItem();
        $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
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
        if ( $room->isCommunityRoom()
           and $room->isOpenForGuests()
           and $room->withRubric(CS_MATERIAL_TYPE)
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


}
?>