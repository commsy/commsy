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
   function cs_privateroom_home_new_entries_view ($params) {
      $this->cs_view($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_NEWEST_ENTRIES');
      $this->setViewName('new_entries');
   }

   function setList($list){
      $this->_list = $list;
   }


   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      if ($pos%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $type = $item->getItemType();
      $item_manager = $this->_environment->getManager($type);
      $full_item = $item_manager->getItem($item->getItemID());
      if (is_object($full_item)){
         $html .= '   <tr class="list">'.LF;
         $html .= '   <td '.$style.'>'.LF;
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
         switch ( $type ) {
            case CS_DISCARTICLE_TYPE:
               $linked_iid = $full_item->getDiscussionID();
               $fragment = 'anchor'.$full_item->getItemID();
               $discussion_manager = $this->_environment->getDiscussionManager();
               $new_full_item = $discussion_manager->getItem($linked_iid);
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
              $img = 'images/commsyicons/netnavigation/todo.png';
              break;
           case 'TOPIC':
              $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
              $img = 'images/commsyicons/netnavigation/topic.png';
              break;
           case 'USER':
              $text .= $this->_translator->getMessage('COMMON_USER');
              $img = 'images/commsyicons/netnavigation/user.png';
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
            $link_title = chunkText($this->_text_as_html_short($full_item->getFullName()),35);
         }else{
            $link_title = chunkText($this->_text_as_html_short($full_item->getTitle()),35);
         }
         $params = array();
         $params['iid'] = $linked_iid;
         $html .= ahref_curl( $full_item->getContextID(),
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
                                       '');
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
                                       '');
         $html .= '   </td>'.LF;
         $html .= '   </tr>'.LF;
      }

      return $html;
   }







   function asHTML () {
      $list = $this->_list;
      $html = '';
      $user = $this->_environment->getCurrentUser();
      $html .= '<div style="margin:0px 5px 5px 5px;">'.$this->_translator->getMessage('COMMON_NEWEST_ENTRIES_IN_ROOMS',$user->getFullName()).'</div>'.LF;
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
}
?>