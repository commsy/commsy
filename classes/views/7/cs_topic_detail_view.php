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
 *  class for CommSy detail view: topic
 */
class cs_topic_detail_view extends cs_detail_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_topic_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   function _getNewestLinkedItemsAsHTML($item){
      $current_context = $this->_environment->getCurrentContextItem();
      $path_shown = false;
      if ( $current_context->withPath() and $item->isPathActive() ) {
         $item_list = $item->getPathItemList();
         if ( !$item_list->isEmpty() ) {
            $path_shown = true;
            $html = '</div>'.LF.LF;
            $html .= '</div>';
            $html .= '<h3 class="annotationtitle" style="margin-top:40px; margin-bottom:5px;">'.$this->_translator->getMessage('TOPIC_PATH');
            $html .= '</h3>'.LF;
            $html .= '<div id="newest_link_box">'.LF;
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

            $linked_item = $item_list->getFirst();
            while ($linked_item) {
               if ($i%2 == 0){
                  $style='class="odd"';
               }else{
                  $style='class="even"';
               }
               $html .= '   <tr class="list">'.LF;
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

               $user = $this->_environment->getCurrentUser();
               if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                   $activating_date = $linked_item->getActivatingDate();
                   if (strstr($activating_date,'9999-00-00')){
                      $link_creator_text = getMessage('COMMON_NOT_ACTIVATED');
                   }else{
                      $link_creator_text = getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($path_item->getActivatingDate());
                   }
                   $html_text = ahref_curl( $this->_environment->getCurrentContextID(),
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
                                        true).LF;
                }else{
                   $html_text = ahref_curl($this->_environment->getCurrentContextID(),$mod,'detail',$params,$linked_item->getTitle()).BRLF;
               }
               $html .= '      <td '.$style.' style="font-size:10pt;">'.($i+1).'. '.$html_text.'</td>'.LF;
               unset($params);
               $html .= '      <td '.$style.' style="font-size:8pt;">'.$type.'</td>'.LF;
               $html .= '      <td '.$style.' style="font-size:8pt;">' .LF;
               $params = array();
               $params['iid'] = $linked_item->getCreatorItem()->getItemID();
               $fullname = $linked_item->getCreatorItem()->getFullname();
               $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       'user',
                                       'detail',
                                       $params,
                                       $fullname);
               $html .= '</td>'.LF;
               $html .= '      <td '.$style.' style="font-size:8pt;">' .LF;
               $link_created = $this->_translator->getDateInLang($linked_item->getCreationDate());
               $html .= $link_created;
               $html .= '</td>'.LF;
               $html .= '   </tr>'.LF;
               $linked_item = $item_list->getNext();
               $i++;
            }
            $html .= '</table>'.LF.LF;
            $html .= '</div>'.LF.LF;
            $html .= '</div>';
            $html .= '<div>'.LF.LF;
            $html .= '<div>&nbsp;'.LF.LF;
         }
      }
      if (!$path_shown){
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
      }
      return $html;
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

      // PATH - END
      $html  .= '<!-- END OF TOPIC ITEM DETAIL -->'."\n\n";

      return $html;
   }


   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      if ( $current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         $image = '<img src="images/commsyicons/22x22/new.png" style="float:right; vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $image,
                                    getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
      } else {
         $image = '<img src="images/commsyicons/22x22/new_grey.png" style="float:right; vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
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