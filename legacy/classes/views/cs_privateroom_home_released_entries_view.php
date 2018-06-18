<?PHP
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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_released_entries_view extends cs_view {


var $_related_user = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('note');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_RELEASED_ENTRIES_PORTLET');
   }

   function asHTML(){
   	$room_id = $this->_environment->getCurrentContextID();
   	$user = $this->_environment->getCurrentUser();

   	$item_manager = $this->_environment->getItemManager();
   	$released_ids = $item_manager->getExternalViewerEntriesForRoom($room_id);
   	$viewable_ids = $item_manager->getExternalViewerEntriesForUser($user->getUserID());

   	$select_ids = array_merge($released_ids, $viewable_ids);

   	$item_manager = $this->_environment->getItemManager();
   	$item_list = $item_manager->getItemList($select_ids);

      $html = '';
      $user = $this->_environment->getCurrentUser();
      $html .= '<table style="width:100%;">';
      $html .= '<tr><td colspan="2">';
      $html .= '<div id="'.get_class($this).'" style="margin:0px 5px 5px 0px; font-weight:bold;">'.$this->_translator->getMessage('COMMON_RELEASED_ENTRIES_FOR_OTHER_USERS',$user->getFullName()).'</div>'.LF;
      $html .= '</td></tr>';
      if(!empty($released_ids)){
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($released_ids);
         $noticed_manager->getLatestNoticedAnnotationsByIDArray($released_ids);
         $released_item = $item_list->getFirst();
          $i = 0;
          while($released_item){
            if(in_array($released_item->getItemID(), $released_ids)){
               $html .= $this->_getItemAsHTML($released_item, $i++);
            }
            $released_item = $item_list->getNext();
          }
      } else {
         $html .= '<tr  class="list"><td class="odd" style="border-bottom: 0px; font-size:8pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      }
      $html .= '</table>';

      $html .= '<hr/>';
      $html .= '<table style="width:100%;">';
      $html .= '<tr><td colspan="2">';
      $html .= '<div id="'.get_class($this).'" style="margin:0px 5px 5px 0px; font-weight:bold;">'.$this->_translator->getMessage('COMMON_RELEASED_ENTRIES_FOR_CURRENT_USER').'</div>'.LF;
      $html .= '</td></tr>';
      if(!empty($viewable_ids)){
         $this->_related_user = $user->getRelatedUserItemInContext($this->_environment->getCurrentPortalID());
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($viewable_ids,$this->_related_user->getItemID());
         $noticed_manager->getLatestNoticedAnnotationsByIDArrayAndUser($viewable_ids,$this->_related_user->getItemID());
         $viewable_item = $item_list->getFirst();
         $i = 0;
         while($viewable_item){
            if(in_array($viewable_item->getItemID(), $viewable_ids)){
               $html .= $this->_getItemAsHTML($viewable_item, $i++, true, 'viewable');
            }
            $viewable_item = $item_list->getNext();
         }
      } else {
         $html .= '<tr  class="list"><td class="odd"  style="border-bottom: 0px; font-size:8pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      }
      $html .= '</table>';
      return $html;
   }


   function _getItemAsHTML($item, $pos=0, $with_links=TRUE,$view_type='released') {
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
                                       '');
         $html .=$this->_getItemChangeStatus($full_item,$full_item->getContextID(),$view_type).$this->_getItemAnnotationChangeStatus($full_item,$full_item->getContextID(),$view_type);

         if ($view_type == 'released'){
            $html .= '<br/><span style="font-size:8pt;">('.$this->_translator->getMessage('PRIVATEROOM_RELEASED_FOR').': ';
            $external_viewer_array = $full_item->getExternalViewerArray();
            $user_manager = $this->_environment->getUserManager();
            $tmp_html = '';
            foreach($external_viewer_array as $external_viewer){
                $user_manager->setUserIDLimit($external_viewer);
                $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
                $user_manager->select();
                $user_list = $user_manager->get();
                $user_item = $user_list->getFirst();
                if (isset($user_item) and is_object($user_item)){
                   $tmp_html .= $user_item->getFullname().', ';
                }
            }
            if (!empty($tmp_html)){
            	$html .= substr($tmp_html, 0, -2);
            }
         }else{
            $modifier_item = $full_item->getModificatorItem();
            $html .= '<br/><span style="font-size:8pt;">('.$this->_translator->getMessage('PRIVATEROOM_RELEASED_FROM').': ';
            $html .= $modifier_item->getFullname();
         }
         $html .= ')</span>'.LF;
         $html .= '   </td>'.LF;
         $html .= '   </tr>'.LF;
      }

      return $html;
   }


   function _getItemAnnotationChangeStatus($item,$context_id,$view_type) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($view_type == 'viewable'){
         $related_user = $this->_related_user;
      }else{
         $related_user = $current_user->getRelatedUserItemInContext($context_id);
      }
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


   function _getItemChangeStatus($item,$context_id,$view_type) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($view_type == 'viewable'){
         $related_user = $current_user->getRelatedUserItemInContext($this->_environment->getCurrentPortalID());
      }else{
         $related_user = $current_user->getRelatedUserItemInContext($context_id);
      }
      if ($related_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestnoticedByUser($item->getItemID(),$related_user->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      return $info_text;
   }




}
?>