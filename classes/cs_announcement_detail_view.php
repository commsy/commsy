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

include_once('classes/cs_detail_view.php');
include_once('functions/curl_functions.php');

/**
 *  class for CommSy detail-view: announcement
 */
class cs_announcement_detail_view extends cs_detail_view {

 /** array of ids in clipboard*/
   var $_clipboard_id_array=array();

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_announcement_detail_view ($environment, $with_modifying_actions=true,$creatorInfoStatus=array()) {
      $this->cs_detail_view($environment, 'announcement', $with_modifying_actions,$creatorInfoStatus);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function _getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }


   function _getDetailActionsAsHTML ($item) {
	   $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
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

      if ( $current_user->isUser() and !in_array($item->getItemID(), $this->_getClipboardIdArray()) ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['add_to_announcement_clipboard'] = $item->getItemID();
         $html .= '> '. ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'announcement',
                                    'detail',
                                    $params,
                                    $this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'</span>'.BRLF;
      }

      if ( !$this->_environment->inPrivateRoom() ){
         if ( $current_user->isUser() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $html .= '> '. ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'rubric',
                                    'mail',
                                    $params,
                                    $this->_translator->getMessage('COMMON_EMAIL_TO')).BRLF;
            unset($params);
         } else {
            $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_EMAIL_TO').'</span>'.BRLF;
         }
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

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
      $html  = LF.'<!-- BEGIN OF ANNOUNCEMENT ITEM DETAIL -->'.LF;
      $formal_data = array();
      $temp_array[0] = $this->_translator->getMessage('ANNOUNCEMENT_VALIDITY_DATE');
      $temp_array[1] = getDateTimeInLang($item->getSeconddateTime());
      $formal_data[] = $temp_array;

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
         $desc = $this->_text_as_html_long($desc);
         $desc = $this->_show_images($desc,$item,true);
         $html .= $this->getScrollableContent($desc,$item,'',true);
      }
      $html  .= '<!-- END OF ANNOUNCEMENT ITEM DETAIL -->'.LF.LF;
      return $html;
   }


}
?>