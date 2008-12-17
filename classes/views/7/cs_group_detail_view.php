<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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
 *  class for CommSy detail-view: group
 */
class cs_group_detail_view extends cs_detail_view {

   private $_account_mode = 'none';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_group_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   function setAccountMode($mode) {
      $this->_account_mode = $mode;
   }

   function getAccountMode() {
      return $this->_account_mode;
   }

   function _getNewestLinkedItemsAsHTML($item){
      $title_string =$this->_translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES');
      $link_items = $item->getLatestLinkItemList(10);
      $title_string .= ' ('.getMessage('COMMON_REFERENCED_LATEST_ONE').' '.$link_items->getCount().')';
      $html = '</div>'.LF.LF;
      $html .= '</div>';
      $html .= '<h3 class="annotationtitle" style="margin-top:40px; margin-bottom:5px;">'.$title_string;
      $html .= '</h3>'.LF;
      $html .='<div id="newest_link_box">'.LF;
      $html .= '<div style="width:100%; background-color:white;">'.LF.LF;
      $i = 0;
      $html .= '<table class="list">';
      $html .= '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:55%;">';
      $html .= $this->_translator->getMessage('COMMON_TITLE');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('COMMON_RUBRIC');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;"  class="head">';
      $html .= $this->_translator->getMessage('COMMON_LINK_CREATOR');
      $html .= '</td>'.LF;
      $html .= '      <td style="width:10%; font-size:8pt;"  class="head">';
      $html .= $this->_translator->getMessage('COMMON_AT');
      $html .= '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;


      if ($link_items->isEmpty()) {
         $html .= '   <tr class="list">'.LF;
         $html .= '      <td '.'class="odd"'.' style="font-size:10pt;">'.getMessage('COMMON_NONE').'</td>'.LF;
         $html .= '</td>';
         $html .= '</tr>';
      } else {
         $link_item = $link_items->getFirst();
         while($link_item){
            if ($i%2 == 0){
               $style='class="odd"';
            }else{
               $style='class="even"';
            }
            $link_creator = $link_item->getCreatorItem();
            if ( isset($link_creator) and !$link_creator->isDeleted() ) {
               $fullname = $link_creator->getFullname();
            } else {
               $fullname = getMessage('COMMON_DELETED_USER');
            }

            $linked_item = $link_item->getLinkedItem($item);  // Get the linked item
            if ( isset($linked_item) ) {
               $fragment = '';    // there is no anchor defined by default
               $type = $linked_item->getType();
               if ($type =='label'){
                  $type = $linked_item->getLabelType();
               }
               $link_created = $this->_translator->getDateInLang($link_item->getCreationDate());
               $text = '';
               switch ( strtoupper($type) )
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
                     $text .= getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view(692) ';
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
               $params = array();
               $params['iid'] = $linked_iid;
               $module = Type2Module($type);
               $user = $this->_environment->getCurrentUser();
               if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                   $activating_date = $linked_item->getActivatingDate();
                   if (strstr($activating_date,'9999-00-00')){
                      $link_creator_text .= ' ('.getMessage('COMMON_NOT_ACTIVATED').')';
                   }else{
                      $link_creator_text .= ' ('.getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($linked_item->getActivatingDate()).')';
                   }
                   $html_text = ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $linked_item->getTitle(),
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
                  $html_text = ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $linked_item->getTitle(),
                                       $link_creator_text,
                                       '_self',
                                       $fragment);
                  unset($params);
               }



            $html .= '   <tr class="list">'.LF;
            $html .= '      <td '.$style.' style="font-size:10pt;">'.$html_text.'</td>'.LF;
            $html .= '      <td '.$style.' style="font-size:8pt;">'.$text.'</td>'.LF;
            $html .= '      <td '.$style.' style="font-size:8pt;">' .LF;
            $params = array();
            $params['iid'] = $link_item->getCreatorItem()->getItemID();
            $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       'user',
                                       'detail',
                                       $params,
                                       $fullname);
            $html .= '</td>'.LF;
            $html .= '      <td '.$style.' style="font-size:8pt;">' .LF;
            $html .= $link_created;
            $html .= '</td>'.LF;
            $html .= '   </tr>'.LF;
            $i++;
            $link_item = $link_items->getNext();
         }
      }
      }
      $html .= '</table>'.LF.LF;

      $html .= '</div>'.LF.LF;
      $html .= '</div>';
      $html .= '<div>'.LF.LF;
      $html .= '<div>&nbsp;'.LF.LF;
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

      $html  = LF.'<!-- BEGIN OF GROUP ITEM DETAIL -->'.LF;

      $html  .='<table style="width:100%; border-collapse:collapse; border:0px solid black;" summary="Layout"><tr><td>';

      #########################################
      # FLAG: group room
      #########################################
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( !$current_context_item->showGrouproomFunctions()
           or !$item->isGroupRoomActivated()
         ) {
      #########################################
      # FLAG: group room
      #########################################

      // Picture
      $picture = $item->getPicture();
      if ( !empty($picture) ){
         $disc_manager = $this->_environment->getDiscManager();
         if ( $disc_manager->existsFile($picture) ) {
            $image_array = getimagesize($disc_manager->getFilePath('picture').$picture);
            $pict_width = $image_array[0];
            if ( $pict_width > 150 ) {
               $width = 150;
            } else {
               $width = $pict_width;
            }
         } else {
            $width = 150;
         }
         unset($disc_manager);
         $params = array();
         $params['picture'] = $picture;
         $curl = curl($this->_environment->getCurrentContextID(),
                      'picture', 'getfile', $params, '');
         unset($params);
         $html .= '<img style=" width: '.$width.'px; margin-left:5px; margin-bottom:5px;" alt="Portrait" src="'.$curl.'" class="portrait2" />'.LF;
      }
      unset($picture);

      // Description
      $desc = $this->_item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($desc);
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      #########################################
      # FLAG: group room
      #########################################
      } else {
         // Description
         $grouproom_item = $item->getGroupRoomItem();
         if ( isset($grouproom_item) and !empty($grouproom_item) ) {
            $desc = $grouproom_item->getDescription();
            if ( !empty($desc) ) {
               $desc = $this->_text_as_html_long($desc);
               $html .= $desc.LF;
            }
         }
      }
      #########################################
      # FLAG: group room
      #########################################

      #########################################
      # FLAG: group room
      #########################################
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( $current_context_item->showGrouproomFunctions() ) {
         $grouproom_item = $item->getGroupRoomItem();
         if ( isset($grouproom_item) and !empty($grouproom_item) ) {
            if ( !empty($desc) ) {
               $char = '';
               $i = 1;
               while ( (empty($char) or $char == LF) and $i < strlen($desc) ) {
                  $char = $desc[strlen($desc)-$i];
                  $i++;
               }
               if ( $char != '>' ) {
                  $html .= BRLF.BRLF;
               }
               unset($char);
            }
            $html .= $this->_getRoomWindowAsHTML($grouproom_item,$this->_account_mode);
            $show_group_room_window = true;
         }
      }
      unset($desc);

      if ( !isset($show_group_room_window) or !$show_group_room_window ) {
      #########################################
      # FLAG: group room
      #########################################

      // Members
      $html .= '<h3 class="subitemtitle" style="margin-top:10px;">'.$this->_translator->getMessage('GROUP_MEMBERS').'</h3>'.LF;
      $context_item = $this->_environment->getCurrentContextItem();
      $members = $item->getMemberItemList();
      $count_member = $members->getCount();
      $html1 = '';
      $html2 = '';
      $html3 = '';
      if ( $members->isEmpty() ) {
         $html1 .= '   <li><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></li>'.LF;
      } else {
         $member = $members->getFirst();
         $i = 1;
         while ($member) {
            if ( $member->isUser() ){
               $linktext = $member->getFullname();
               $member_title = $member->getTitle();
               if ( !empty($member_title) ) {
                  $linktext .= ', '.$member_title;
               }
               if ($i == 1){
                  $html1 .= '   <li>';
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $html1 .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  unset($params);
                  $html1 .= '</li>'.LF;
               }elseif($i == 2){
                  $html2 .= '   <li>';
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $html2 .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  unset($params);
                  $html2 .= '</li>'.LF;
               }else{
                  $html3 .= '   <li>';
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $html3 .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  unset($params);
                  $html3 .= '</li>'.LF;
               	$i = 0;
               }
               $i++;
            }
            unset($member);
            $member = $members->getNext();
         }
      }
      $html .= '<table summary = "layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td style="vertical-align:top;">'.LF;
      $html .= '<ul>'.LF;
      $html .= $html1.LF;
      $html .= '</ul>'.LF;
      $html .= '</td>'.LF;
      if (!empty($html2)){
         $html .= '<td style="vertical-align:top;">'.LF;
         $html .= '<ul>'.LF;
         $html .= $html2;
         $html .= '</ul>'.LF;
         $html .= '</td>'.LF;
      }
      if (!empty($html3)){
         $html .= '<td style="vertical-align:top;">'.LF;
         $html .= '<ul>'.LF;
         $html .= $html3;
         $html .= '</ul>'.LF;
         $html .= '</td>'.LF;
      }
      $html .= '</tr>'.LF;
      $html .= '</table>'.LF;

      // Foren
      $context_item = $this->_environment->getCurrentContextItem();
      if($context_item->WikiEnableDiscussionNotificationGroups() == 1){
         $discussions = $item->getDiscussionNotificationArray();
         if ( isset($discussions[0]) ) {
            $html .= '<h3>'.$this->_translator->getMessage('GROUP_DISCUSSIONS').'</h3>'.LF;
            $html .= '<ul>'.LF;
            foreach($discussions as $discussion){
                  $html .= '   <li>' . $discussion . '</li>'.LF;

            }
            $html .= '</ul>'.LF;
         }
      }

      #########################################
      # FLAG: group room
      #########################################
      }
      #########################################
      # FLAG: group room
      #########################################
      $html .= '</td></tr></table>'.LF;

      $html .= '<!-- END OF group ITEM DETAIL -->'.LF.LF;

      unset($grouproom_item);
      unset($current_context_item);
      return $html;
   }


   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      if ( $item->mayEdit($current_user) and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'edit',
                                          $params,
                                          $image,
                                          getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
      $html .= $this->_getDetailItemActionsAsHTML($item).'&nbsp;&nbsp;&nbsp;';
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_LIST_PRINTVIEW')).LF;
      unset($params['mode']);
      if ( !$this->_environment->inPrivateRoom() ) {
         if ( $current_user->isUser() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $image = '<img src="images/commsyicons/22x22/mail.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EMAIL_TO').'"/>';
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'rubric',
                                    'mail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_EMAIL_TO')).LF;
            unset($params);
         } else {
            $image = '<img src="images/commsyicons/22x22/mail_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EMAIL_TO').'"/>';
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      $params = $this->_environment->getCurrentParameterArray();
      $params['download']='zip';
      $params['mode']='print';
      $image = '<img src="images/commsyicons/22x22/save.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DOWNLOAD').'"/>';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_DOWNLOAD')).LF;
      unset($params['download']);
      unset($params['mode']);
      if ( $current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         $html .= '&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $image,
                                    getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
      } else {
         $image = '<img src="images/commsyicons/22x22/new_grey.png" style="float:right; vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         $html .= '&nbsp;&nbsp;'.'<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }


   function _getDetailItemActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      ##############################
      # FLAG: group room
      ##############################
      if ( $item->isGroupRoomActivated() ) {
         $grouproom_item = $item->getGroupRoomItem();
         if ( isset($grouproom_item) and !empty($grouproom_item) ) {
            if ( $grouproom_item->isUser($current_user) ) {
               if ($item->isSystemLabel() or ($grouproom_item->isLastModeratorByUserID($current_user->getUserID(),$current_user->getAuthSource())) ) {
                  $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
                  $html .= '<a title="'.$this->_translator->getMessage('GROUP_LEAVE').' "class="disabled">'.$image.'</a>'.LF;
               } else {
                  $params = array();
                  $params['iid'] = $this->_item->getItemID();
                  $params['group_option'] = '2';
                  $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
                  $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                             'group',
                                             'detail',
                                             $params,
                                             $image,
                                             $this->_translator->getMessage('GROUP_LEAVE')).LF;
                  unset($params);
               }
            } else {
               $grouproom_user_item = $grouproom_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
               if ( !empty($grouproom_user_item)
                    and ( $grouproom_user_item->isRequested()
                          or $grouproom_user_item->isRejected()
                        )
                  ) {
                  $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_ENTER').'"/>';
                  $html .= '<a title="'.$this->_translator->getMessage('GROUP_ENTER').' "class="disabled">'.$image.'</a>'.LF;
               } else {
                  $params = array();
                  $params['iid'] = $this->_item->getItemID();
                  $params['account'] = 'member';
                  $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_ENTER').'"/>';
                  $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                             'group',
                                             'detail',
                                             $params,
                                             $image,
                                             $this->_translator->getMessage('GROUP_ENTER')).LF;
                  unset($params);
               }
            }
         }
      } else {
      ##############################
      # FLAG: group room
      ##############################

      // Enter or leave the group
      if ( $item->isMember($current_user) ) {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['group_option'] = '2';
            $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'group',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('GROUP_LEAVE')).LF;
            unset($params);
         } else {
            $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
            $html .= '<a title="'.$this->_translator->getMessage('GROUP_LEAVE').' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['group_option'] = '1';
            $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_ENTER').'"/>';
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'group',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('GROUP_ENTER')).LF;
            unset($params);
         } else {
            $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_ENTER').'"/>';
            $html .= '<a title="'.$this->_translator->getMessage('GROUP_ENTER').' "class="disabled">'.$image.'</a>'.LF;
         }
      }

      ##############################
      # FLAG: group room
      ##############################
      }
      ##############################
      # FLAG: group room
      ##############################
      if ( $item->mayEdit($current_user)  and $this->_with_modifying_actions and !$item->isSystemLabel()) {
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'detail',
                                     $params,
                                     $image,
                                     getMessage('COMMON_DELETE_ITEM')).LF;
         unset($params);
      } else {
         $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }

      return $html;
   }


   function _getActionsAsHTML() {
   }

   function _is_always_visible ($rubric) {
      return true;
   }

   function _has_attach_link ($rubric) {
      return true;
   }

   /** get room window as html
    *
    * param cs_grouproom_item item room item
    * param string            mode member status
    */
   private function _getRoomWindowAsHTML ($item, $mode='') {
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $color_array = $item->getColorArray();
      $title = $item->getTitle();
      $html  = '';
      $html .= '<table class="room_window" style="width: 100%; border-collapse:collapse; border: 2px solid '.$color_array['tabs_background'].';" summary="Layout">'.LF;
      $html .= '<tr><td style="padding:0px;">'.LF.LF;
      $logo = $item->getLogoFilename();
      $html .= '<table style="width: 100%; padding:0px; border-collapse:collapse;" summary="Layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:'.$color_array['tabs_background'].'; padding:0px;">';

      // Titelzeile
      if ( !empty($logo) ) {
         $html .= '<div style="background-color:'.$color_array['tabs_background'].'; float: left; padding: 5px;">'.LF;
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params,'');
         unset($params);
         if (!$may_enter) {
            $html .= '      <img class="logo_small" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>'.LF;
         } else {
            $html .= ahref_curl($item->getItemID(),'home','index','','<img class="logo_small" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>');
         }
         $html .= '</div>'.LF;
         $html .= '<div style="background-color:'.$color_array['tabs_background'].'; color:'.$color_array['tabs_title'].'; font-size: large; padding-top: 8px; padding-bottom: 8px;">'.LF;
         $html .= $this->_text_as_html_short($title);
         if ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']'.LF;
         } elseif ($item->isProjectroom() and $item->isTemplate()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_TEMPLATE').']'.LF;
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']'.LF;
         }
         $html .= '</div>'.LF;
      } else {
         $html .= '<div style="background-color:'.$color_array['tabs_background'].';  color:'.$color_array['tabs_title'].'; vertical-align: large; font-size: large; padding-top: 8px; padding-bottom: 8px;">'.LF;
         $html .= $this->_text_as_html_short($title)."\n";

         if ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']';
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']';
         }
         $html .= '</div>';
      }
      $html .= '</td>';
      $html .= '</tr>'.LF;

      $formal_data = array();

      // group room user
      $html .= '<tr>'.LF;
      $html .= '<td  colspan="2" style="background-color: '.$color_array['content_background'].'; padding:5px;">'.LF;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)){
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      $current_user = $this->_environment->getCurrentUserItem();

      // Anzeige außerhalb des Anmeldeprozesses
      if ( ( $mode !='member'
             and $mode !='info'
             and $mode !='email'
           )
           or !$item->isOpen()
         ) {
         $current_user = $this->_environment->getCurrentUserItem();
         if ($current_user->isRoot()) {
            $may_enter = true;
         } elseif ( !empty($room_user) ) {
            $may_enter = $item->mayEnter($room_user);
         } else {
            $may_enter = false;
         }
         $html .= '<div style="float:right; width:15em; padding:5px; vertical-align: middle; text-align: center;">'.LF;

         // Eintritt erlaubt
         if ( $may_enter ) {
            $actionCurl = curl( $item->getItemID(),
                                'home',
                                'index',
                                '');
            if ( !$this->isPrintableView() ) {
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img alt="door" src="images/door_open_large.gif" style="vertical-align: large;"/></a>'.BRLF;
            } else {
               $html .= '<img alt="door" src="images/door_open_large.gif" style="vertical-align: large;"/>'.BRLF;
            }
            if ( $item->isOpen() ) {
               $actionCurl = curl( $item->getItemID(),
                                'home',
                                'index',
                                '');
               $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
                 if (!$this->isPrintableView()) {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER').'</a></div>'.LF;
               } else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_ENTER').'</div>'.LF;
               }
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html .= '</div>';

         } elseif ( $item->isLocked() ) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle; "/>'.LF;

         //Um Erlaubnis gefragt
         } elseif ( !empty($room_user) and $room_user->isRequested() ) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET').'</p></div>'.LF;
           $html.= '</div>';

         //Erlaubnis verweigert
         } elseif ( !empty($room_user) and $room_user->isRejected() ) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED').'</p></div>'.LF;
           $html.= '</div>';

         // noch nicht angemeldet als Mitglied im Raum
         } else {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle text-align:left;"/>'.BRLF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:center;">';
            if ( $item->isOpen() ) {
               $params['account'] = 'member';
               $params['iid'] = $this->_item->getItemID();
               $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                   $this->_environment->getCurrentModule(),
                                   'detail',
                                   $params,
                                   '');
               if (!$this->isPrintableView()) {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
               } else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_JOIN').'</div>'.LF;
               }
               unset($params);
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html.= '</div>';
         }
         $html .= '</div>'.LF;
         $html .= '<div>'.LF;

         // prepare member list
         $html_temp = '';
         $user_list = $item->getUserList();
         $user_item = $user_list->getFirst();
         while ($user_item) {
            $status = '';
            if ( $user_item->isModerator() ) {
               $status = ' ('.$this->_translator->getMessage('CONTEXT_MODERATOR').')';
            }
            $html_temp .= '<li>'.$this->_text_as_html_short($user_item->getFullName()).$status.'</li>';
            $user_item = $user_list->getNext();
         }
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('GROUP_MEMBERS').':</span>'.LF;
         $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
         if ( !empty($html_temp) ) {
            $html .= $html_temp;
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;

      // Person ist User und will Mitglied werden
      } elseif ( $mode == 'member'
                 and ( $current_user->isUser()
                       or ( $current_user->getUserID() != 'guest'
                            and ( $current_user->isGuest()
                                  or $current_user->isRequested()
                                )
                          )
                     )
               ) {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params).'" name="member">'.LF;
         $get_params = $this->_environment->getCurrentParameterArray();
         if ( $item->checkNewMembersWithCode() ) {
            $html .= $this->_translator->getMessage('ACCOUNT_GET_CODE_TEXT');
            if ( isset($get_params['error']) and !empty($get_params['error']) ) {
               $temp_array[0] = $this->_translator->getMessage('COMMON_ATTENTION').': ';
               $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
               $formal_data[] = $temp_array;
            }
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE').': ';
            $temp_array[1] = '<input type="text" name="code" tabindex="14" size="30"/>'.LF;
            $formal_data[] = $temp_array;
         } else {
            $html .= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON').': ';
            $temp_array[1]= '<textarea name="description_user" cols="40" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;
            $formal_data[] = $temp_array;
         }

         $temp_array = array();
         $temp_array[0] = '&nbsp;';
         $temp_array[1]= '<input type="submit" name="option" tabindex="15" value="'.$this->_translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON').'"/>'.
                       '&nbsp;&nbsp;'.'<input type="submit" name="option" tabindex="16" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML2($formal_data);
            $html .= BRLF;
         }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;

      } elseif ( $mode=='email') {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html.= $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT');
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params).'" name="member">'.LF;
         $temp_array[0] = $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT_DESC').': ';
         $temp_array[1]= '<textarea name="description_user" cols="50" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;
         $formal_data[] = $temp_array;
         $temp_array = array();
         $temp_array[0] = '&nbsp;';
         $temp_array[1] = '<input type="submit" name="option"  value="'.$this->_translator->getMessage('CONTACT_MAIL_SEND_BUTTON').'"/>'.
                          '&nbsp;&nbsp;'.'<input type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML2($formal_data);
            $html .= BRLF;
         }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;
      }

      //Person ist User und hat sich angemeldet; wurde aber nicht automatisch freigschaltet
      elseif ($mode =='info') {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params).'" name="member">'.LF;
         $temp_array = array();
         $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_CONFIRMATION').': ';
         $temp_array[1]= $this->_translator->getMessage('ACCOUNT_GET_6_TEXT_2',$this->_item->getTitle());
         $formal_data[] = $temp_array;
         $temp_array = array();
         $temp_array[0] = '&nbsp;';
         $temp_array[1]= '<input type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_FORWARD_BUTTON').'"/>'.LF;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML2($formal_data);
            $html .= BRLF;
         }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;
      }

      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;

      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;
      return $html;
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '   <!-- BEGIN Styles -->'.LF;
      $retour .= '   <style type="text/css">'.LF;
      $retour .= '    img.logo_small { height: 40px; }'.LF;
      $retour .= '   </style>'.LF;
      $retour .= '   <!-- END Styles -->'.LF;
      return $retour;
   }

   function _getFormalDataAsHTML2($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" summary="Layout" ';
      if ( $clear ) {
         $html .= 'style="clear:both; width: 100%;" ';
      }else{
         $html .= 'style="width: 100%;" ';
      }
      $html .= '>'.LF;
      foreach ($data as $value) {
         if ( !empty($value[0]) ) {
            $html .= $prefix.'   <tr>'.LF;
            $html .= $prefix.'      <td style="padding: 10px 2px 10px 0px; color: #666; vertical-align: top; width: 1%;">'.LF;
            $html .= $prefix.'         '.$value[0].LF;
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td style="margin: 0px; padding: 10px 2px 10px 0px;">'.LF;
         if ( !empty($value[1]) ) {
            $html .= $prefix.'         '.$value[1].LF;
         }
         $html .= $prefix.'      </td>'.LF;
         $html .= $prefix.'   </tr>'.LF;
      }
      $html .= $prefix.'</table>'.LF;
      return $html;
   }
}
?>