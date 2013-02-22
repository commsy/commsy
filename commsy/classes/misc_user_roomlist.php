<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

class misc_user_roomlist {

   /* TBD
    * ---
    *
    * Use this object for room list
    * - in rooms above right corner
    * - at portal on the left side
    * - maybe for all room lists
    */

   private $_environment = NULL;
   private $_translator = NULL;
   private $_option_add_array = array();

   public function __construct ($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
         $this->_translator = $this->_environment->getTranslationObject();
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
   }

   public function addAdditionalOption ( $input ) {
      if ( !empty($input) ) {
         $this->_option_add_array[] = $input;
      }
   }

   private function _getAdditionalOptionAsHTML () {
      $retour = '';
      if ( !empty($this->_option_add_array) ) {
         foreach ($this->_option_add_array as $option) {
            $retour .= $option.LF;
         }
      }
      return $retour;
   }

   public function getCurrentUserRoomListAsSelectHTML ($select_add,$select_room) {
      $retour = '';
      $current_user = $this->_environment->getCurrentUserItem();
      if ( !empty($current_user) ) {
         $retour = $this->_getRoomListAsSelectHTML($current_user,$select_add,$select_room);
      }
      return $retour;
   }

   private function _getRoomListAsSelectHTML ( $user_item, $select_add = '', $select_room = '' ) {
      $retour = '';
      if ( !empty($user_item) ) {
         $context_array = $this->_getAllOpenContexts($user_item);
         if ( !empty($context_array) ) {
             $retour .= '         <select';
             if ( !empty($select_add) ) {
                $retour .= ' '.trim($select_add);
             }
             $retour .= '>'.LF;
             $retour .= $this->_getAdditionalOptionAsHTML();
             $retour .= $this->_getOwnRoomAsOptionHTML($user_item,$select_room);
             $retour .= $this->_getContextArrayAsOptionHTML($context_array,$select_room);
             $retour .= '         </select>'.LF;
         }
      }
      return $retour;
   }

   private function _getAllOpenContexts ($user_item) {
      $own_room_item = $user_item->getOwnRoom();
      if ( isset($own_room_item) ) {
         $customized_room_array = $own_room_item->getCustomizedRoomIDArray();
      }
      if ( isset($customized_room_array[0]) ) {
         return $this->_getCustomizedRoomList($user_item);
      } else {
         $this->translatorChangeToPortal();
         $selected = false;
         $selected_future = 0;
         $selected_future_pos = -1;
         $retour = array();
         $temp_array = array();
         $temp_array['item_id'] = -1;
         $temp_array['title'] = '';
         $retour[] = $temp_array;
         unset($temp_array);
         $temp_array = array();
         $community_list = $user_item->getRelatedCommunityList();
         if ( $community_list->isNotEmpty() ) {
            $temp_array['item_id'] = -1;
            $temp_array['title'] = $this->_translator->getMessage('MYAREA_COMMUNITY_INDEX').'';
            $retour[] = $temp_array;
            unset($temp_array);
            $community_item = $community_list->getFirst();
            while ($community_item) {
               $temp_array = array();
               $temp_array['item_id'] = $community_item->getItemID();
               $title = $community_item->getTitle();
               $temp_array['title'] = $title;
               if ( $community_item->getItemID() == $this->_environment->getCurrentContextID()
                    and !$selected
                  ) {
                  $temp_array['selected'] = true;
                  $selected = true;
               }
               $retour[] = $temp_array;
               unset($temp_array);
               unset($community_item);
               $community_item = $community_list->getNext();
            }
            $temp_array = array();
            $temp_array['item_id'] = -1;
            $temp_array['title'] = '';
            $retour[] = $temp_array;
            unset($community_list);
         }
         $portal_item = $this->_environment->getCurrentPortalItem();
         if ($portal_item->showTime()) {
            $project_list = $user_item->getRelatedProjectListSortByTimeForMyArea();
            include_once('classes/cs_list.php');
            $new_project_list = new cs_list();
            $grouproom_array = array();
            $project_grouproom_array = array();
            if ( $project_list->isNotEmpty() ) {
               $room_item = $project_list->getFirst();
               while ($room_item) {
                  if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
                     $grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
                     $linked_project_item_id = $room_item->getLinkedProjectItemID();
                     $project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
                  } else {
                     $new_project_list->add($room_item);
                  }
                  unset($room_item);
                  $room_item = $project_list->getNext();
               }
               unset($project_list);
               $project_list = $new_project_list;
               unset($new_project_list);
            }
            $future = true;
            $future_array = array();
            $no_time = false;
            $no_time_array = array();
            $current_time = $portal_item->getTitleOfCurrentTime();
            $with_title = false;
         } else {
            $project_list = $user_item->getRelatedProjectListForMyArea();
            include_once('classes/cs_list.php');
            $new_project_list = new cs_list();
            $grouproom_array = array();
            $project_grouproom_array = array();
            if ( $project_list->isNotEmpty() ) {
               $room_item = $project_list->getFirst();
               while ($room_item) {
                  if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
                     $grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
                     $linked_project_item_id = $room_item->getLinkedProjectItemID();
                     $project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
                  } else {
                     $new_project_list->add($room_item);
                  }
                  unset($room_item);
                  $room_item = $project_list->getNext();
               }
               unset($project_list);
               $project_list = $new_project_list;
               unset($new_project_list);
            }
         }
         unset($user_item);
         if ( $project_list->isNotEmpty() ) {
            $temp_array['item_id'] = -1;
            $temp_array['title'] = $this->_translator->getMessage('MYAREA_PROJECT_INDEX').'';
            $retour[] = $temp_array;
            unset($temp_array);
            $project_item = $project_list->getFirst();
            while ($project_item) {
               $temp_array = array();
               if ( $project_item->isA(CS_PROJECT_TYPE) ) {
                  $temp_array['item_id'] = $project_item->getItemID();
                  $title = $project_item->getTitle();
                  $temp_array['title'] = $title;
                  if ( $project_item->getItemID() == $this->_environment->getCurrentContextID()
                       and ( !$selected
                             or $selected_future == $project_item->getItemID()
                           )
                     ) {
                     $temp_array['selected'] = true;
                     if ( !empty($selected_future)
                          and $selected_future != 0
                          and $selected_future_pos != -1
                        ) {
                        $selected_future = 0;
                        unset($future_array[$selected_future_pos]['selected']);
                     }
                     $selected = true;
                  }

                  // grouprooms
                  if ( isset($project_grouproom_array[$project_item->getItemID()]) and !empty($project_grouproom_array[$project_item->getItemID()]) and $project_item->isGrouproomActive()) {
                     $group_result_array = array();
                     $project_grouproom_array[$project_item->getItemID()]= array_unique($project_grouproom_array[$project_item->getItemID()]);
                     foreach ($project_grouproom_array[$project_item->getItemID()] as $value) {
                        $group_temp_array = array();
                        $group_temp_array['item_id'] = $value;
                        $group_temp_array['title'] = '- '.$grouproom_array[$value];
                        if ( $value == $this->_environment->getCurrentContextID()
                             and ( !$selected
                                   or $selected_future == $value
                                 )
                           ) {
                           $group_temp_array['selected'] = true;
                           $selected = true;
                           if ( !empty($selected_future)
                                and $selected_future != 0
                                and $selected_future_pos != -1
                              ) {
                              $selected_future = 0;
                              unset($future_array[$selected_future_pos]['selected']);
                           }
                        }
                        $group_result_array[] = $group_temp_array;
                        unset($group_temp_array);
                     }
                  }
               } else {
                  $with_title = true;
                  $temp_array['item_id'] = -2;
                  $title = $project_item->getTitle();
                  if (!empty($title) and $title != 'COMMON_NOT_LINKED') {
                     $temp_array['title'] = $this->_translator->getTimeMessage($title);
                  } else {
                     $temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
                     $no_time = true;
                  }
                  if (!empty($title) and $title == $current_time) {
                     $future = false;
                  }
               }
               if ($portal_item->showTime()) {
                  if ($no_time) {
                     $no_time_array[] = $temp_array;
                     if ( isset($group_result_array) and !empty($group_result_array) ) {
                        $no_time_array = array_merge($no_time_array,$group_result_array);
                        unset($group_result_array);
                     }
                  } elseif ($future) {
                     if ($temp_array['item_id'] != -2) {
                        $future_array[] = $temp_array;
                        if ( !empty($temp_array['selected']) and $temp_array['selected'] ) {
                           $selected_future = $temp_array['item_id'];
                           $selected_future_pos = count($future_array)-1;
                        }
                        if ( isset($group_result_array) and !empty($group_result_array) ) {
                           $future_array = array_merge($future_array,$group_result_array);
                           unset($group_result_array);
                        }
                     }
                  } else {
                     $retour[] = $temp_array;
                     if ( isset($group_result_array) and !empty($group_result_array) ) {
                         $retour = array_merge($retour,$group_result_array);
                         unset($group_result_array);
                     }
                  }
               } else {
                  $retour[] = $temp_array;
                  if ( isset($group_result_array) and !empty($group_result_array) ) {
                     $retour = array_merge($retour,$group_result_array);
                     unset($group_result_array);
                  }
               }
               unset($temp_array);
               unset($project_item);
               $project_item = $project_list->getNext();
            }
            unset($project_list);
            if ($portal_item->showTime()) {

               // special case, if no room is linked to a time pulse
               if (isset($with_title) and !$with_title) {
                  $temp_array = array();
                  $temp_array['item_id'] = -2;
                  $temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
                  $retour[] = $temp_array;
                  unset($temp_array);
                  $retour = array_merge($retour,$future_array);
                  $future_array = array();
               }

               if (!empty($future_array)) {
                  $future_array2 = array();
                  $future_array3 = array();
                  foreach ($future_array as $element) {
                     if ( !in_array($element['item_id'],$future_array3) ) {
                        $future_array3[] = $element['item_id'];
                        $future_array2[] = $element;
                     }
                  }
                  $future_array = $future_array2;
                  unset($future_array2);
                  unset($future_array3);
                  $temp_array = array();
                  $temp_array['title'] = $this->_translator->getMessage('COMMON_IN_FUTURE');
                  $temp_array['item_id'] = -2;
                  $future_array_begin = array();
                  $future_array_begin[] = $temp_array;
                  $future_array = array_merge($future_array_begin,$future_array);
                  unset($temp_array);
                  $retour = array_merge($retour,$future_array);
               }

               if (!empty($no_time_array)) {
                  $retour = array_merge($retour,$no_time_array);
               }
            }
         }
         unset($portal_item);
         $this->translatorChangeToCurrentContext();
         return $retour;
      }
   }

   private function translatorChangeToPortal () {
      $current_portal = $this->_environment->getCurrentPortalItem();
      if (isset($current_portal)) {
         $this->_translator->setContext(CS_PORTAL_TYPE);
         $this->_translator->setRubricTranslationArray($current_portal->getRubricTranslationArray());
         $this->_translator->setEmailTextArray($current_portal->getEmailTextArray());
       }
   }

   private function translatorChangeToCurrentContext () {
      $current_context = $this->_environment->getCurrentContextItem();
      if (isset($current_context)) {
         if ($current_context->isCommunityRoom()) {
            $this->_translator->setContext(CS_COMMUNITY_TYPE);
         } elseif ($current_context->isProjectRoom()) {
            $this->_translator->setContext(CS_PROJECT_TYPE);
         } elseif ($current_context->isPortal()) {
            $this->_translator->setContext(CS_PORTAL_TYPE);
         } else {
            $this->_translator->setContext(CS_SERVER_TYPE);
         }
         $this->_translator->setRubricTranslationArray($current_context->getRubricTranslationArray());
         $this->_translator->setEmailTextArray($current_context->getEmailTextArray());
      }
   }

   private function _getCustomizedRoomList ($user_item) {
      $retour = array();
      $current_context_id = $this->_environment->getCurrentContextID();
      $own_room_item = $user_item->getOwnRoom();
      $temp_array = array();
      $temp_array['title'] = '----------------------------';
      $temp_array['item_id'] = '-1';
      $retour[] = $temp_array;
      $customized_room_list = $own_room_item->getCustomizedRoomList();
      if ( isset($customized_room_list) ) {
         $room_item = $customized_room_list->getFirst();
         while ($room_item) {
            $temp_array = array();
            if ( $room_item->isGrouproom() ) {
               $temp_array['title'] = '- '.$room_item->getTitle();
            } else {
               $temp_array['title'] = $room_item->getTitle();
            }
            if ( mb_strlen($temp_array['title']) > 28 ) {
               $temp_array['title'] = mb_substr($temp_array['title'],0,28);
               $temp_array['title'] .= '...';
            }
            $temp_array['item_id'] = $room_item->getItemID();
            if ($current_context_id == $temp_array['item_id']){
               $temp_array['selected'] = true;
            }
            $retour[] = $temp_array;
            $room_item = $customized_room_list->getNext();
         }
      }
      return $retour;
   }

   private function _getContextArrayAsOptionHTML ($context_array,$select_room = '') {
      $retour = '';
      $first_time = true;
      foreach ($context_array as $con) {
         if ($con['item_id'] == -2) {
            $additional = ' class="disabled" disabled="disabled" style="font-style:italic;"';
            $con['item_id'] = -1;
            if ($first_time) {
               $first_time = false;
            } else {
               $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>&nbsp;</option>'.LF;
            }
            $con['item_id'] = -2;
         }
         if ( !empty($select_room)
              and $select_room == $con['item_id']
            ) {
            $con['selected'] = true;
         }
         $retour .= $this->_getOptionAsHTML($con);
      }

/* - wenn, dann nicht hier, irgendwie anders
      if (!$this->_current_user->isUser() and $this->_current_user->getUserID() != "guest") {
         $context = $this->_environment->getCurrentContextItem();
         if (!empty($context_array)) {
            $retour .= '            <option value="-1" class="disabled" disabled="disabled">&nbsp;</option>'.LF;
         }
         $retour .= '            <option value="-1" class="disabled" disabled="disabled">----'.$this->_translator->getMessage('MYAREA_CONTEXT_GUEST_IN').'----</option>'.LF;
         $retour .= '            <option value="'.$context->getItemID().'" selected="selected">'.$context->getTitle().'</option>'."\n";
      }
*/
      return $retour;
   }

   private function _getOwnRoomAsOptionHTML ($user_item,$select_room = '') {
      $retour = '';
      $own_room_item = $user_item->getOwnRoom();
      if ( isset($own_room_item) ) {
         $con = array();
         $con['title'] = $own_room_item->getTitle();
         $con['item_id'] = $own_room_item->getItemID();
         if ( !empty($select_room)
              and $select_room == $own_room_item->getItemID()
            ) {
            $con['selected'] = true;
         }
         $retour .= $this->_getOptionAsHTML($con);
         unset($con);
      }
      return $retour;
   }

   private function _getOptionAsHTML ( $con ) {
      $retour = '';

      $title = encode(AS_HTML_SHORT,$con['title']);
      $additional = '';
      if (isset($con['selected']) and $con['selected']) {
         $additional = ' selected="selected"';
      }
      if ($con['item_id'] == -1) {
         $additional = ' class="disabled" disabled="disabled"';
         if (!empty($con['title'])) {
            $title = '----'.encode(AS_HTML_SHORT,$con['title']).'----';
         } else {
            $title = '&nbsp;';
         }
      }
      if ($con['item_id'] == -2) {
         $additional = ' class="disabled" disabled="disabled" style="font-style:italic;"';
         if (!empty($con['title'])) {
            $title = encode(AS_HTML_SHORT,$con['title']);
         } else {
            $title = '&nbsp;';
         }
         $con['item_id'] = -1;
      }
      $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>'.$title.'</option>'.LF;

      return $retour;
   }
}
?>