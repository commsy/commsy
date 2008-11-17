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

include_once('classes/cs_detail_view.php');
include_once('functions/curl_functions.php');

/**
 *  class for CommSy detail view: topic
 */
class cs_topic_detail_view extends cs_detail_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_topic_detail_view ($params) {
      $environment = $params['environment'];
      $with_modifying_actions = true;
      if ( isset($params['with_modifying_actions']) ) {
         $with_modifying_actions = $params['with_modifying_actions'];
      }
      $creatorInfoStatus = array();
      if ( isset($params['creator_info_status']) ) {
         $creatorInfoStatus = $params['creator_info_status'];
      }
      $this->cs_detail_view($environment, 'topic', $with_modifying_actions,$creatorInfoStatus );
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML($item) {
      $user = $this->_environment->getCurrentUser();

      $html  = LF.'<!-- BEGIN OF TOPIC ITEM DETAIL -->'.LF;

      $desc = $this->_item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($desc);
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      // PATH - BEGIN
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->withPath() and $item->isPathActive() ) {
         $item_list = $item->getPathItemList();
         if ( !$item_list->isEmpty() ) {
            $html .= '<h3>'.$this->_translator->getMessage('TOPIC_PATH').':</h3>'.LF;
            $html .= '<ol>'.LF;
            $linked_item = $item_list->getFirst();
            while ($linked_item) {
               $params = array();
               $params['iid'] = $linked_item->getItemID();
               $params['path'] = $item->getItemID();
               $mod = type2Module($linked_item->getItemType());
               $type = $linked_item->getItemType();
               if ($type == 'date') {
                  $type .= 's';
               }

               $temp_type = strtoupper($type);
               switch ($temp_type)
               {
                  case 'ANNOUNCEMENT':
                     $type = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
                     break;
                  case 'DATES':
                     $type = $this->_translator->getMessage('COMMON_DATES');
                     break;
                  case 'DISCUSSION':
                     $type = $this->_translator->getMessage('COMMON_DISCUSSION');
                     break;
                  case 'GROUP':
                     $type = $this->_translator->getMessage('COMMON_GROUP');
                     break;
                  case 'INSTITUTION':
                     $type = $this->_translator->getMessage('COMMON_INSTITUTION');
                     break;
                  case 'MATERIAL':
                     $type = $this->_translator->getMessage('COMMON_MATERIAL');
                     break;
                  case 'PROJECT':
                     $type = $this->_translator->getMessage('COMMON_PROJECT');
                     break;
                  case 'TODO':
                     $type = $this->_translator->getMessage('COMMON_TODO');
                     break;
                  case 'TOPIC':
                     $type = $this->_translator->getMessage('COMMON_TOPIC');
                     break;
                  case 'USER':
                     $type = $this->_translator->getMessage('COMMON_USER');
                     break;
                  default:
                     $type = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_topic_detail('.__LINE__.') ');
                     break;
               }

               $html .= '<li>'.LF;
               $html .= $type.':&nbsp;';
               $user = $this->_environment->getCurrentUser();
               if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                   $activating_date = $linked_item->getActivatingDate();
                   if (strstr($activating_date,'9999-00-00')){
                      $link_creator_text = getMessage('COMMON_NOT_ACTIVATED');
                   }else{
                      $link_creator_text = getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($path_item->getActivatingDate());
                   }
                   $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                        $mod,
                                        'detail',
                                        $params,
                                        $linked_item->getTitle(),
                                        $link_creator_text,
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        'class="disabled"',
                                        '',
                                        '',
                                        true).BRLF;
                }else{
                   $html .= ahref_curl($this->_environment->getCurrentContextID(),$mod,'detail',$params,$linked_item->getTitle()).BRLF;
               }
               $html .= '</li>'.LF;
               unset($params);
               $linked_item = $item_list->getNext();
            }
            $html .= '</ol>'.LF;
         }
      }
      // PATH - END

      // Auskommentierung der Mitglieder eines Themas
/*      if ( !$this->_environment->inPrivateRoom()){
         // Members
         $html .= '<h3>'.$this->_translator->getMessage('TOPIC_MEMBERS').'</h3>'.LF;
         $html .= '<ul>'.LF;
         $members = $item->getMemberItemList();
         $context_item = $this->_environment->getCurrentContextItem();
         if ( $members->isEmpty() ) {
            $html .= '   <li><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></li>'.LF;
         } else {
           $member = $members->getFirst();
            while ($member) {
               if ( $member->isUser() ) {
                  $linktext = $member->getFullname();
                  $member_title = $member->getTitle();
                  if ( !empty($member_title) ) {
                     $linktext .= ', '.$member_title;
                  }
                  $html .= '   <li>';
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  if ( $this->_environment->inCommunityRoom() and $member->maySee($user) ) {
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  } elseif ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  } else {
                     $html .= '<span class="disabled">'.$linktext.'</span>';
                  }
                  unset($params);

                  $html .= '</li>'.LF;
               }
               $member = $members->getNext();
            }
         }
         $html .= '</ul>'.LF;
      }*/
      $html  .= '<!-- END OF TOPIC ITEM DETAIL -->'."\n\n";

      return $html;
   }


   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $mod = $this->_with_modifying_actions;
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('COMMON_ACTIONS').'</div>';
      $html .= '         </noscript>';
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
      if ( $item->mayEdit($current_user) ) {
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         $html .= '> '. ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'detail',
                                          $params,
                                          $this->_translator->getMessage('COMMON_DELETE_ITEM')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_DELETE_ITEM').'</span>'.BRLF;
      }
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
      $params['download']='zip';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_DOWNLOAD')).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }


   function _is_always_visible ($rubric) {
      return true;
   }

   function _has_attach_link ($rubric) {
      return true;
   }
}
?>