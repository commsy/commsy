<?PHP
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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy todo detail-views
 */
class cs_todo_detail_view extends cs_detail_view {

 /** array of ids in clipboard*/
   var $_clipboard_id_array=array();


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_todo_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function _getClipboardIDArray() {
      return $this->_clipboard_id_array;
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
      $html  = LF.'<!-- BEGIN OF TODO ITEM DETAIL -->'.LF;
      $user = $this->_environment->getCurrentUser();
      $context = $this->_environment->getCurrentContextItem();
      $formal_data = array();
      $original_date = $item->getDate();
      $date = getDateTimeInLang($original_date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$this->_translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }

      // Members
      $member_html = '';
      $members = $item->getProcessorItemList();
      if ( $members->isEmpty() ) {
         $member_html .= '   '.$this->_translator->getMessage('TODO_NO_PROCESSOR').LF;
      } else {
         $member = $members->getFirst();
         $count = $members->getCount();
         $counter = 0;
         while ($member) {
            $counter++;
            if ( $member->isUser() ){
               $linktext = $member->getFullname();
               if ( $member->maySee($user) ) {
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $member_html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
                  unset($params);
               } else {
                  $member_html .= '<span class="disabled">'.$linktext.'</span>'.LF;
               }
               if ( $counter != $count) {
                  $member_html .= ', ';
               }
            }
            $member = $members->getNext();
         }
      }

      $temp_array[0] = $this->_translator->getMessage('TODO_VALIDITY_DATE');
      $temp_array[1] = $date;
      $formal_data[] = $temp_array;
      $temp_array[0] = $this->_translator->getMessage('TODO_PROCESSORS');
      $temp_array[1] = $member_html;
      $formal_data[] = $temp_array;
      $temp_array[0] = $this->_translator->getMessage('TODO_STATUS');
      $temp_array[1] = $item->getStatus();
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

      // Description
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($desc);
         $desc = $this->_show_images($desc,$item,true);
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      $html  .= '<!-- END OF TODO ITEM DETAIL -->'.LF.LF;
      return $html;
   }

   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string navigation as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDetailItemActionsAsHTML ($item) {
	   $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $mod = $this->_with_modifying_actions;
      $html  = '';

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
      // Enter or leave the topic
      if ( $item->isProcessor($current_user) ) {
         if ($mod) {
            $params['iid'] = $item->getItemID();
            $params['todo_option'] = '2';
            $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.getMessage('TODO_LEAVE').'"/>';
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'todo',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TODO_LEAVE')).LF;
         } else {
            $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_NO_ACTION').'"/>';
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ($current_user->isUser() and $mod ) {
            $params['iid'] = $item->getItemID();
            $params['todo_option'] = '1';
            $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.getMessage('TODO_ENTER').'"/>';
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'todo',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TODO_ENTER')).LF;
         } else {
            $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_NO_ACTION').'"/>';
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      if ( $item->mayEdit($current_user)  and $this->_with_modifying_actions ) {
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


      return $html.'&nbsp;&nbsp;&nbsp;';
   }

}
?>