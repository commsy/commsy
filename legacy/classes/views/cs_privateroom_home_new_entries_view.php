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
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_privateroom_home_new_entries_view extends cs_view {

var  $_config_boxes = false;
var $_list = NULL;


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_NEWEST_ENTRIES');
      $this->setViewName('new_entries');
   }

   function setList($list){
      $this->_list = $list;
   }


   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      if ($pos%2 == 0){
         $style='class="even"';
      }else{
         $style='class="odd"';
      }
      $type = $item->getItemType();
      $item_manager = $this->_environment->getManager($type);
      $full_item = $item_manager->getItem($item->getItemID());
      if (is_object($full_item)){
         $html .= '   <tr class="list">'.LF;
         $html .= '   <td '.$style.' style="font-size:8pt;">'.LF;
         $type = $full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         $fragment = '';    // there is no anchor defined by default
         $link_created = $this->_translator->getDateInLang($full_item->getModificationDate());
         $text = '';
         $creator = $full_item->getCreatorItem();
         if ( isset($creator) and !$creator->isDeleted()) {
            $fullname = $this->_text_as_html_short($creator->getFullname());
         } else {
            $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
         }
         $room = $full_item->getContextItem();
         $room_title = $room->getTitle();
         switch ( $type ) {
            case CS_DISCARTICLE_TYPE:
               $linked_iid = $full_item->getDiscussionID();
               $fragment = 'anchor'.$full_item->getItemID();
               $discussion_manager = $this->_environment->getDiscussionManager();
               $new_full_item = $discussion_manager->getItem($linked_iid);
               break;
            case CS_STEP_TYPE:
               $linked_iid = $full_item->getToDoID();
               $fragment = 'anchor'.$full_item->getItemID();
               $todo_manager = $this->_environment->getToDoManager();
               $new_full_item = $todo_manager->getItem($linked_iid);
               break;
            case CS_SECTION_TYPE:
               $linked_iid = $full_item->getLinkedItemID();
               $fragment = 'anchor'.$full_item->getItemID();
               $material_manager = $this->_environment->getMaterialManager();
               $new_full_item = $material_manager->getItem($linked_iid);
               break;
            default:
               $linked_iid = $full_item->getItemID();
               $new_full_item = $full_item;
         }
         $type = $new_full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         switch ( mb_strtoupper($type, 'UTF-8') ) {
           case 'ANNOUNCEMENT':
              $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
              $img = 'images/commsyicons/32x32/announcement.png';
              break;
           case 'DATE':
              $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
              $img = 'images/commsyicons/32x32/date.png';
              break;
           case 'DISCUSSION':
              $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
              $img = 'images/commsyicons/32x32/discussion.png';
              break;
           case 'GROUP':
              $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
              $img = 'images/commsyicons/32x32/group.png';
              break;
           case 'INSTITUTION':
              $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
              $img = '';
              break;
           case 'MATERIAL':
              $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
              $img = 'images/commsyicons/32x32/material.png';
              break;
           case 'PROJECT':
              $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
              $img = '';
              break;
           case 'TODO':
              $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
              $img = 'images/commsyicons/32x32/todo.png';
              break;
           case 'TOPIC':
              $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
              $img = 'images/commsyicons/32x32/topic.png';
              break;
           case 'USER':
              $text .= $this->_translator->getMessage('COMMON_USER');
              $img = 'images/commsyicons/32x32/user.png';
              break;
           default:
              $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
              $img = '';
              break;
        }
        $link_creator_text = $text.' - '.$this->_translator->getMessage('COMMON_EDIT_BY').' '.
                                    $fullname.', '.
                                    $link_created;
         $module = Type2Module($type);
         if ($module == CS_USER_TYPE){
            $link_title = $this->_text_as_html_short($full_item->getFullName());
         }else{
            $link_title = $this->_text_as_html_short($full_item->getTitle());
         }
         $params = array();
         $params['iid'] = $linked_iid;
         $html .= '<div style="float:left;">'.ahref_curl( $full_item->getContextID(),
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
                                       '',
                                       '',
                                       '',
                                       '').'</div>';
         $html .= ahref_curl( $full_item->getContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '').$this->_getItemChangeStatus($full_item,$full_item->getContextID());
         $html .= '<br/><span style="font-size:8pt;">('.$this->_translator->getMessage('COMMON_ROOM').': ';
         $params = array();
         $html .= ahref_curl( $full_item->getContextID(),
                                       'home',
                                       'index',
                                       $params,
                                       $room_title,
                                       $room_title,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '');
         $html .= ')</span>'.LF;
         $html .= '   </td>'.LF;
         $html .= '   </tr>'.LF;
      }

      return $html;
   }


   function _getItemChangeStatus($item,$context_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $related_user = $current_user->getRelatedUserItemInContext($context_id);
      if (isset($related_user) and is_object($related_user) and $related_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestnoticedByUser($item->getItemID(),$related_user->getItemID());

         $anno_change_status = $this->_getItemAnnotationChangeStatus($item);

         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
         if ( !empty($anno_change_status) ) {
            //$info_text .= ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
            $info_text .= $anno_change_status;
         }
      } else {
         $info_text = '';
      }
      return $info_text;
   }


   function _getItemAnnotationChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      $related_user = $current_user->getRelatedUserItemInContext($item->getContextID());
      if ($related_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->_current_user_id = $related_user->getItemID();
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



   function asHTML () {
      $list = $this->_list;
      $html = '';
      $user = $this->_environment->getCurrentUser();
      $html .= '<div  id="'.get_class($this).'" style="margin:0px 5px 5px 5px;">'.$this->_translator->getMessage('COMMON_NEWEST_ENTRIES_IN_ROOMS',$user->getFullName()).'</div>'.LF;
      $html .= '<table style="width:100%;">';
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<tr  class="list"><td class="odd" style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }
      $html .= '</table>';
      return $html;

   }

   function getPreferencesAsHTML(){
   	$current_context = $this->_environment->getCurrentContextItem();
   	$count = $current_context->getPortletNewEntryListCount();
   	$user = $current_context->getPortletNewEntryListShowUser();

      $html = $this->_translator->getMessage('PORTLET_CONFIGURATION_NEW_ENTRIES_COUNT').': ';
      $html .= '<select id="portlet_new_entries_count" size="0" tabindex="30" style="font-size:10pt;">';
      if($count == '10'){
      	$html .= '<option value="10" selected>10</option>';
      } else {
         $html .= '<option value="10">10</option>';
      }
      if($count == '15'){
         $html .= '<option value="15" selected>15</option>';
      } else {
         $html .= '<option value="15">15</option>';
      }if($count == '20'){
         $html .= '<option value="20" selected>20</option>';
      } else {
         $html .= '<option value="20">20</option>';
      }
      $html .= '</select><br/>';

      #$html .= $this->_translator->getMessage('PORTLET_CONFIGURATION_NEW_ENTRIES_SHOW_USER').': ';
      #$html .= '<select id="portlet_new_entries_show_user" size="0" tabindex="30" style="font-size:10pt;">';
      #if($user == '1'){
      #   $html .= '<option value="1" selected>'.$this->_translator->getMessage('COMMON_YES').'</option>';
      #} else {
      #   $html .= '<option value="1">'.$this->_translator->getMessage('COMMON_YES').'</option>';
      #}
      #if($user == '-1'){
      #   $html .= '<option value="-1" selected>'.$this->_translator->getMessage('COMMON_NO').'</option>';
      #} else {
      #   $html .= '<option value="-1">'.$this->_translator->getMessage('COMMON_NO').'</option>';
      #}
      #$html .= '</select><br/>';

      $html .= '<input type="submit" id="portlet_new_entries_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'"/>';
      return $html;
   }
}
?>