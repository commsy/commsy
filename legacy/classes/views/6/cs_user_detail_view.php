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
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_detail_view::__construct($params);
   }

   function setSubItem ($item) {
      $list = new cs_list();
      $list->add($item);
      $this->setSubItemList($list);
      $this->_sub_item_title_description = $this->_translator->getMessage('COMMON_READABLE_ONLY_USER',$item->getFullName());
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
         $temp_array[] = $this->_text_as_html_short($title);
         $formal_data[] = $temp_array;
      }

      $birthday = $item->getBirthday();
      if ( !empty($birthday) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_BIRTHDAY');
         $temp_array[] = $this->_text_as_html_short($birthday);
         $formal_data[] = $temp_array;
      }

      $street = $item->getStreet();
      if ( !empty($street) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_STREET');
         $temp_array[] = $this->_text_as_html_short($street);
         $formal_data[] = $temp_array;
      }

      $city = $item->getCity();
      if ( !empty($city) ) {
         $zipcode = $item->getZipCode();
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_CITY');
         $temp_array[] = $this->_text_as_html_short(trim($zipcode.' '.$city));
         $formal_data[] = $temp_array;
      }


      $room = $item->getRoom();
      if ( !empty($room) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_ROOM');
         $temp_array[] = $this->_text_as_html_short($room);
         $formal_data[] = $temp_array;
      }

      $temp_array = array();
      $temp_array[] = '';
      $temp_array[] = '';
      $formal_data[] = $temp_array;

      $telephone = $item->getTelephone();
      if ( !empty($telephone) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_TELEPHONE');
         $temp_array[] = $this->_text_as_html_short($telephone);
         $formal_data[] = $temp_array;
      }

      $cellularphone = $item->getCellularphone();
      if ( !empty($cellularphone) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_CELLULARPHONE');
         $temp_array[] = $this->_text_as_html_short($cellularphone);
         $formal_data[] = $temp_array;
      }

      $email = $item->getEmail();
      $email_text = $this->_text_as_html_short($email);
      $email_short = chunkText($email_text,45);
      if ( !empty($email) and ( $item->isEmailVisible() or $this->_display_mod == 'admin') ) {
         if (isset($_GET['mode']) and $_GET['mode']=='print'){
            $emailDisplay = $email_short;
         }else{
            $emailDisplay = '<a title="'.$email_text.'" href="mailto:'.$email.'">'.$email_short.'</a>';
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
      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data).LF;
      }
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
         #$url_to_service = '???';

         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('USER_MESSENGER_NUMBERS');
         $first = true;
         $html_text = '<div style=" vertical-align:bottom; ">';
         if ( !empty($icq_number) ){
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            #$html_text .= '<a href="'.$url_to_service.'/message/icq/'.$icq_number.'">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://status.icq.com/online.gif?icq='.$icq_number.'&amp;img=2" alt="ICQ Online Status Indicator" />'.LF;
            #$html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$icq_number.')';
            $first = false;
         }
         /*
         if ( !empty($jabber_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<a href="skype:'.$jabber_number.'?chat">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="'.$url_to_service.'/jabber/'.$jabber_number.'/onurl='.$url_to_img.'/jabber_long_online.gif/offurl='.$url_to_img.'/jabber_long_offline.gif/unknownurl='.$url_to_img.'/jabber_long_unknown.gif" alt="Jabber Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$jabber_number.')';
         }
         */
         if ( !empty($msn_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://www.IMStatusCheck.com/status/msn/'.$msn_number.'?icons" alt="MSN Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$msn_number.')';
         }
         if ( !empty($skype_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
            $html_text .= '<a href="skype:'.$skype_number.'?chat">'.LF;
            $html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://mystatus.skype.com/smallclassic/'.$skype_number.'" alt="Skype Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$skype_number.')';
         }
         if ( !empty($yahoo_number) ) {
            if ( !$first ){
                $html_text .='<br/> ';
            }
            $first = false;
            $html_text .= '<!-- Begin Online Status Indicator code -->'.LF;
            $html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
            $html_text .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.$yahoo_number.'">'.LF;
            $html_text .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.$yahoo_number.'/m=g/t=1/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
            $html_text .= '</a>'.LF;
            $html_text .= '<!-- End Online Status Indicator code -->'.LF;
            $html_text .= ' ('.$yahoo_number.')';
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
            $homepage = $homepage_short;
         }else{
            $homepage = '<a href="'.$homepage.'" title="'.$homepage_text.'" target="_blank">'.$homepage_short.'</a>';
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
         $desc = $this->_text_as_html_long($desc);
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


   function _getForwardBoxAsHTML () {
      $item = $this->_item;
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= '<div class="index_forward_links" style="white-space:nowrap; text-align:center;">'.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" >'.LF;
      if ( $item->mayEdit($current_user) and $this->_with_modifying_actions ) {
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
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
      $params['download']='zip';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_DOWNLOAD')).BRLF;
      unset($params['download']);

      $params['mode'] = 'take_over';
      if ( $this->_environment->inPortal()
           and ( $current_user->isRoot()
                 or $current_user->isModerator()
               )
           
         ) {
      	if(!$current_user->isDeactivatedLoginAsAnotherUser() or $current_user->isTemporaryAllowedToLoginAs()){
      		if($item->getUserID() != $current_user->getUserID()){
      			$html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname())).BRLF;
      		} else {
      			$html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname()).'</span>'.BRLF;
      		}
      		
      	} else {
      		$html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('ACCOUNT_TAKE_OVER',$item->getFullname()).'</span>'.BRLF;
      	}
         
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }


   function _getSubItemAsHTML($item, $anchor_number, $spaces=0) {
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
      
      if ($item->isModerator() or $item->isUser()){
      	// Datenschutz expired password date
      	$temp_array = array();
      	$temp_array[] = $this->_translator->getMessage('USER_LOGIN_AS_ACTIV');
      	 
      	if(!$item->isDeactivatedLoginAsAnotherUser()){
      		$temp_array[] = $this->_translator->getMessage('COMMON_YES');
      	} else if($item->isTemporaryAllowedToLoginAs()){
      		$temp_array[] = $item->getTimestampForLoginAs();
      	} else {
      		$temp_array[] = $this->_translator->getMessage('COMMON_NO');
      	}
      	
      	$formal_data[] = $temp_array;
      	if ($portal_item->getPasswordExpiration() != 0) {
      		// Datenschutz expired password date
      		$temp_array = array();
      		$temp_array[] = $this->_translator->getMessage('USER_EXPIRED_PASSWORD');
      		 
      		if($item->isPasswordExpired()){
      			$temp_array[] = $this->_translator->getMessage('COMMON_YES');
      		} else {
      			$temp_array[] = $this->_translator->getMessage('COMMON_NO');
      		}
      	
      		$formal_data[] = $temp_array;
      	}
      	
      	$temp_array = array();
      	$temp_array[] = $this->_translator->getMessage('USER_ACCEPTED_AGB');
      	 
      	$agb = $item->getAGBAcceptanceDate();
      	if(!empty($agb)){
      		$temp_array[] = getDateTimeInLang($item->getAGBAcceptanceDate());
      	}
      	$formal_data[] = $temp_array;
      }

      $temp_array = array();
      $temp_array[] = $this->_translator->getMessage('USER_EMAIL_DEFAULT');

      if(!$item->getDefaultIsMailVisible()){
         $temp_array[] = $this->_translator->getMessage('COMMON_YES');
      } else {
         $temp_array[] = $this->_translator->getMessage('COMMON_NO');
      }
      $formal_data[] = $temp_array;

       if ($this->_environment->inPortal()) {
           $temp_array = array();
           $formal_data[] = $temp_array;

           $temp_array = array();
           $temp_array[] = $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT');

           if (!$item->isModerator()) {
               if ($item->getIsAllowedToCreateContext() == 'standard') {
                   $temp_array[] = $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_AUTH_SOURCE_SETTING_SHORT');
               } else if ($item->getIsAllowedToCreateContext() == 1) {
                   $temp_array[] = $this->_translator->getMessage('COMMON_YES');
               } else {
                   $temp_array[] = $this->_translator->getMessage('COMMON_NO');
               }
               $formal_data[] = $temp_array;
           } else {
               $temp_array[] = $this->_translator->getMessage('COMMON_YES').' ('.$this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_IS_PORTAL_MODERATOR').')';
               $formal_data[] = $temp_array;
           }

           $temp_array = array();
           $temp_array[] = $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_AUTH_SOURCE_SETTING');

           $auth_source_manager = $this->_environment->getAuthSourceManager();
           $auth_source_item = $auth_source_manager->getItem($item->getAuthSource());
           $auth_source_standard_setting = '';
           if ($auth_source_item->isUserAllowedToCreateContext()) {
               $auth_source_standard_setting .= $this->_translator->getMessage('COMMON_YES');
           } else {
               $auth_source_standard_setting .= $this->_translator->getMessage('COMMON_NO');
           }
           $temp_array[] = $auth_source_standard_setting;

           $formal_data[] = $temp_array;

           $temp_array = array();
           $formal_data[] = $temp_array;
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
            if ($room_item->getRoomType() == 'grouproom') {
               $temp_string .= '- ';
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

       // archive
       if ( !$this->_environment->isArchiveMode() ) {
       
          $temp_array = array();
          $formal_data[] = $temp_array;
          unset($temp_array);
       
          $temp_array = array();
          $temp_array[] = $this->_translator->getMessage('PORTAL_ARCHIVED_ROOMS');
          
          $zzz_project_manager = $this->_environment->getZzzProjectManager();
          $zzz_project_manager->setUserIDLimit($item->getUserID());
          $zzz_project_manager->setStatusLimit(2);
          $zzz_project_manager->select();

          $archived_room_list = $zzz_project_manager->get();

          $zzzCommunityManager = $this->_environment->getZzzCommunityManager();
          $archivedCommunityList = $zzzCommunityManager->getRelatedCommunityListForUser($item);

          if ($archivedCommunityList->isNotEmpty()) {
            $archived_room_list->addList($archivedCommunityList);
          }

          $zzzGroupRoomManager = $this->_environment->getZzzGroupRoomManager();
          $zzzGroupRoomManager->setUserIDLimit($item->getUserID());
          $zzzGroupRoomManager->select();
          $archivedGroupRoomList = $zzzGroupRoomManager->get();

          if ($archivedGroupRoomList->isNotEmpty()) {
            $archived_room_list->addList($archivedGroupRoomList);
          }

          if ($archived_room_list->isNotEmpty()) {

            // get related archived user
            $roomItem = $archived_room_list->getFirst();
            $lookupArchivedContextIds = array();
            while ($roomItem) {
                if (!isset($related_user_array[$roomItem->getItemID()])) {
                    $lookupArchivedContextIds[] = $roomItem->getItemID();
                }

                $roomItem = $archived_room_list->getNext();
            }

            if (!empty($lookupArchivedContextIds)) {
                $zzzUserManager = $this->_environment->getZzzUserManager();
                $zzzUserManager->resetLimits();
                $zzzUserManager->setUserIDLimit($item->getUserID());
                $zzzUserManager->setContextArrayLimit($lookupArchivedContextIds);
                $zzzUserManager->select();

                $zzzUserList = $zzzUserManager->get();
                if ($zzzUserList->isNotEmpty()) {
                    $zzzUserItem = $zzzUserList->getFirst();

                    while ($zzzUserItem) {
                        $related_user_array[$zzzUserItem->getContextID()] = $zzzUserItem;

                        $zzzUserItem = $zzzUserList->getNext();
                    }
                }
            }

            $archived_room_list->reset();
             $room_item = $archived_room_list->getFirst();
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
               $room_item = $archived_room_list->getNext();
            }
            $temp_array[] = $temp_string;
            unset($temp_string);
            unset($room_list);
          } else {
             $temp_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>';
          }
          
          $formal_data[] = $temp_array;
          unset($temp_array);
       
          $this->_environment->activateArchiveMode();
       
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
                // status
                if (isset($related_user_array[$room_item->getItemID()]) && $related_user_array[$room_item->getItemID()] != null) {
                  $status = $this->_getStatus($related_user_array[$room_item->getItemID()],$room_item);
                   if (!empty($status)) {
                      $temp_string .= ' ('.$status.')';
                   }
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
          $this->_environment->deactivateArchiveMode();
       }
       // arcive - end
       
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

   function _getSubItemsAsHTML($item){
      $html = '<!-- BEGIN OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      $current_item = $item;
      $html .= '<div style="width:100%; margin-top:15px;">'.LF;
      $html .= '   <div class="detail_sub_items_title" style="font-weight:normal;">'.LF;
      $html .= '      <span class="sub_item_pagetitle">'.$this->_getSubItemTitleAsHTML($current_item, '1');
      $html .= '</span>'.LF;
      $html .= '   </div>'.LF;

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div style="float:right; width:27%; margin-top:5px; padding-left:5px; padding-right:8px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="margin-bottom:10px;">'.LF;
         $html .= $this->_getSubItemDetailActionsAsHTML($current_item);
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='<div style="width:70%; margin-top:5px; padding-top:10px; padding-left:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div style="width:100%; margin-top:5px; padding-top:10px; padding-left:5px; vertical-align:bottom;">'.LF;
      }
      $html .='<div style="margin-bottom:10px;">'.LF;
      $html .= $this->_getSubItemAsHTML($current_item,1).LF;
      $html .='</div>'.LF;
      $html .='<div style="margin-top:5px; margin-bottom:0px; padding-top:10px; padding-bottom:50px; vertical-align:top;">';
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
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      return $html;
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

         /* $private_room_manager = $this->_environment->getPrivateRoomManager();
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
         } */
         if ( $user->isRoot() and isset($own_room) ) {
            $params = array();
            $params['iid'] = $own_room->getItemID();
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','export',$params,$this->_translator->getMessage('PRIVATEROOM_EXPORT')).BRLF;
            unset($params);
         }
         
         if ($user->isModerator() or $user->isRoot()) {
         	$params = array();
         	$params['iid'] = $item->getItemID();
         	$html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'account','assignroom',$params,'Raum zuordnen').BRLF;
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
      
      if($this->_environment->inPortal()
      	and $this->_environment->getCurrentUser()->isRoot()
      	and $item->isModerator()){
      	$params['mode'] = 'deactivateLoginAs';
      	$params['iid'] = $item->getItemID();
      	if($item->isDeactivatedLoginAsAnotherUser()) {
      		$html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LOGIN_AS_ANOTHER_USER_ACTIVATE')).BRLF;
      	} else {
      		$html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LOGIN_AS_ANOTHER_USER_DEACTIVATE')).BRLF;
      	}
      	
      }
      // USER_LIST_ACTION_EMAIL_HIDE_DEFAULT
      $params['iid'] = $item->getItemID();
      $params['mode'] = 'hideMailDefault';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_HIDE_DEFAULT')).BRLF;
      $params['mode'] = 'hideMailAllRooms';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_HIDE_ROOM')).BRLF;
      $params['mode'] = 'showMailDefault';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_SHOW_DEFAULT')).BRLF;
      $params['mode'] = 'showMailAllRooms';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_SHOW_ROOM')).BRLF;
      
      

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _is_always_visible ($rubric) {
      return ($rubric == CS_TOPIC_TYPE);
   }

   function _getAllLinkedItemsAsHTML ($spaces=0) {
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
         if ($connection == CS_GROUP_TYPE) {
            $context = $this->_environment->getCurrentContextItem();
            $html .='		<div class="netnavigation_panel">     '.LF;
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
                  $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
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
            $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_user_detail_view('.__LINE__.') ';
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
   }

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
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_user_detail_view('.__LINE__.') ';
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


   /** get detail view as HTML
    * this method returns the detail view in HTML-Code
    *
    * @returns string detail view as HMTL
    */
   function asHTML () {
      $item = $this->getItem();
      $html  = LF.'<!-- BEGIN OF USER DETAIL VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="width:100%;">'.LF;
      $rubric = $this->_environment->getCurrentModule();

      if($rubric == CS_DISCUSSION_TYPE){
         $html .= '<h2 class="pagetitle">'.$this->_getTitleAsHTML();
      }elseif ($rubric == 'account' ){
        $html .= '<h2 class="pagetitle">'.$item->getFullName();
      }elseif ($rubric != CS_USER_TYPE ){
        $html .= '<h2 class="pagetitle">'.$item->getTitle();
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
         $html .='<div style="margin-bottom:0px;">'.LF;
         $html .= $this->_getForwardBoxAsHTML($item);
         $html .='</div>'.LF;
############SQL-Statements reduzieren
         if ( $rubric != 'account' and !$this->_environment->inPrivateRoom()){
            $title_string .= '"'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'"';
            $desc_string .= '""';
            $size_string .= '"10"';
            $config_text .= 'true';
            $html .= '<div class="commsy_panel" style="margin-bottom:0px;">'.LF;
            $html .= $this->_getAllLinkedItemsAsHTML($item);
            $html .='</div>'.LF;
         }
         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'),Array(),null,null);'.LF;
         $html .= '</script>'.LF;
         $html .='</div>'.LF;
         $html .='<div class="infoborder" style="width:71%; margin-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div class="infoborder" style="width:100%; margin-top:5px; vertical-align:bottom;">'.LF;
      }
      $html .='<div style="margin-bottom:10px;">'.LF;
      $html .= $this->_getContentAsHTML();
      $html .='</div>'.LF;

      $html .='<div class="infoborder" style="margin-top:20px; margin-bottom:0px; padding-bottom:50px; vertical-align:top;">';
      $mode = 'short';
      if (in_array($item->getItemID(),$this->_openCreatorInfo)) {
         $mode = 'long';
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print') ){
         if ( $rubric != 'account' ){
            $html .= $this->_getCreatorInformationAsHTML($item, 3,$mode).LF;
         }
      }
      $html .='</div>'.LF;

      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $current_user = $this->_environment->getCurrentUserItem();
      if ( $current_user->isModerator()
           or $current_user->getItemID() == $item->getItemID()
         ) {
############SQL-Statements reduzieren
         $html .= $this->_getSubItemsAsHTML($item);
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      unset($current_user);
      if ( $rubric != CS_GROUP_TYPE
          and $rubric != CS_USER_TYPE
          and $rubric != CS_DISCUSSION_TYPE
          and $this->_environment->getCurrentModule() !='account'
         ) {
         $html .= $this->_getAnnotationsAsHTML();
      } elseif ( $rubric == CS_DISCUSSION_TYPE
                 and !$item->isClosed()
                 and $this->_with_modifying_actions
               ) {
         $html .= $this->_getDiscussionFormAsHTML();
      }
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
      return $html;
   }
}
?>