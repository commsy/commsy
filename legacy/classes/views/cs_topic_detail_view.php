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
   function __construct($params) {
      cs_detail_view::__construct($params);
   }

   function _getNewestLinkedItemsAsHTML($item){
      $current_context = $this->_environment->getCurrentContextItem();
      $path_shown = false;
      $html = '';
      if ( $current_context->withPath() and $item->isPathActive() ) {
         $item_list = $item->getPathItemList();
         if ( !$item_list->isEmpty() ) {
            $path_shown = true;
            $html .= '<h3 class="subitemtitle" style="margin-top:0px; margin-bottom:5px;">'.$this->_translator->getMessage('TOPIC_PATH');
            $html .= '</h3>'.LF;

            $i = 0;
            $html .='<ul style="list-style-type: none; list-style-position:inside; font-size:8pt; padding-left:0px; margin-left:0px; margin-top:0px; margin-bottom:20px; padding-bottom:0px;">  '.LF;
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

               $temp_type = mb_strtoupper($type, 'UTF-8');
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
                      $link_creator_text = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
                   }else{
                      $link_creator_text = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($linked_item->getActivatingDate());
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
                   $html_text = ahref_curl($this->_environment->getCurrentContextID(),$mod,'detail',$params,$linked_item->getTitle(),$type.' - '.$linked_item->getTitle()).LF;
               }
               $html .= '      <li style="font-size:10pt;">'.($i+1).'. '.$html_text.'</li>'.LF;
               unset($params);
               $linked_item = $item_list->getNext();
               $i++;
            }
            $html .= '</ul>'.LF.LF;
         }
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
      $current_context = $this->_environment->getCurrentContextItem();
      $html  = LF.'<!-- BEGIN OF TOPIC ITEM DETAIL -->'.LF;

      // Files
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }
      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
         $html .= BRLF;
      }
      $desc = $this->_item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      $html .= $this->_getNewestLinkedItemsAsHTML($item);


      // PATH - BEGIN

      // PATH - END
      $html  .= '<!-- END OF TOPIC ITEM DETAIL -->'.LF.LF;

      return $html;
   }


   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();

      $html  = '';
      $html .= $this->_getEditAction($item,$current_user);
      $html .= $this->_getDeleteAction($item,$current_user);
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getPrintAction($item,$current_user);
      #if ( !$this->_environment->inPrivateRoom() ) {
      #   $html .= $this->_getMailAction($item,$current_user);
      #}
      $html .= $this->_getDownloadAction($item,$current_user);
      $html .= $this->_getNewAction($item,$current_user);

      $html .= $this->_initDropDownMenus();
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