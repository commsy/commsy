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

include_once('classes/cs_index_view.php');
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy short list view: task
 */
class cs_task_short_view extends cs_index_view {


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_task_short_view ($environment, $with_modifying_actions) {
      $this->cs_index_view($environment,$with_modifying_actions);
	  if (!($this->_environment->inPortal() and $this->_environment->getCurrentModule() == 'home')) {
         $this->setTitle($this->_translator->getMessage('PREFERENCES_TITLE'));
	  }
      $this->_with_form_fields = false;
   }

   function _getTableheadAsHTML () {
      $html  = '   <tr class="head">'.LF;
      $html .= '      <td class="head" colspan="3">'.LF;
      $html .= '<span class="head">';
      $html .= $this->_translator->getMessage('TASKS_TITLE');
      $html .= '</span>'.LF;
      $html .= $this->_getDescriptionAsHTML();
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
     # $this->_setColspan(3);
      return $html;
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {
      return ' <span class="desc">('.$this->_translator->getMessage('TASKS_TITLE_SHORT_DESC').')</span>';
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
      $html  = '   <tr>'.LF;
	  if ($this->_environment->inPortal()) {
         $html .= '      <td width="20%">'.$this->_getItemDate($item).'</td>'.LF;
	  } else {
         $html .= '      <td width="20%">'.$this->_getItemDate($item).BR.$this->_getItemStatus($item).'</td>'.LF;
	  }
      $html .= '      <td width="50%">'.$this->_getItemTitle($item).BR.$this->_getItemInfos($item).'</td>'.LF;
	  if ($this->_environment->inPortal()) {
         $html .= '      <td width="30%">'.$this->_getItemActions($item).'</td>'.LF;
	  } else {
         $html .= '      <td width="30%">'.$this->_getItemUser($item).BR.$this->_getItemActions($item).'</td>'.LF;
	  }
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the item date
    * this method returns the item date in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemDate ($item) {
      $date = getDateTimeinLang($item->getModificationDate());
      return $this->_text_as_html_short($date);
   }

   /** get the item title
    * this method returns the item title in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemTitle ($item) {
      $title = '';
      $title_front_label = '';
      $title_back_label = '';
      $task_title = $this->_text_as_html_short($item->getTitle());
      $ref_item = $item->getLinkedItem();
      if (!empty($ref_item) and $ref_item->isA('user')) {
         $explanation = $this->_text_as_html_short($ref_item->getUserComment());
         $title = $ref_item->getUserID();
         if  (!empty($explanation)) {
            $title .= ' <img src="images/unknown.gif" border="0" width="12" title="'.$explanation.'">';
         }
         $title_front_label = $this->_translator->getMessage('USER_USER_ID').': ';
         $title_back_label = ' '.$this->_translator->getMessage('ACCOUNT_REQUESTED');
      } elseif (!empty($ref_item) and ($ref_item->isA(CS_PROJECT_TYPE) or $ref_item->isA(CS_COMMUNITY_TYPE))) {
         $title = $this->_text_as_html_short($ref_item->getTitle());
      } elseif (!empty($ref_item) and $ref_item->isA('material')) {
         $title = $ref_item->getTitle();
         $title_front_label = $this->_translator->getMessage('COMMON_MATERIAL').': ';
         if ($ref_item->isDeleted()) {
            $title_back_label = ' ['.$this->_translator->getMessage('MATERIAL_WAS_DELETED').']';
         }
      }
      if (!empty($title_front_label)) {
         $title = '<span class="disabled">'.$title_front_label.'</span>'.$title;
      }
      if (!empty($title_back_label)) {
         $title .= '<span class="disabled">'.$title_back_label.'</span>';
      }
      return $title;
   }

   /** get the item user
    * this method returns the item user in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemUser ($item) {
      $text = '';
      $front_label = '';
      $back_label = '';
      $task_title = $this->_text_as_html_short($item->getTitle());
      $ref_item = $item->getLinkedItem();

      if (!empty($ref_item) and $ref_item->isA('user')) {
         $text = $this->_text_as_html_short($ref_item->getFullname());
         $front_label = $this->_translator->getMessage('COMMON_BY').': ';
      } elseif (!empty($ref_item) and ($ref_item->isA(CS_PROJECT_TYPE) or $ref_item->isA(CS_COMMUNITY_TYPE))) {
         $creator = $item->getCreatorItem();
         $text = $this->_text_as_html_short($creator->getFullname());
         $front_label = $this->_translator->getMessage('COMMON_BY').': ';
      } elseif (!empty($ref_item) and $ref_item->isA('material')) {
         $modificator = $ref_item->getModificatorItem();
         $text = $this->_text_as_html_short($modificator->getFullname());
         $front_label = $this->_translator->getMessage('COMMON_BY').': ';
      }
      if (!empty($front_label)) {
         $text = '<span class="list_view_description">'.$front_label.'</span>'.$text;
      }
      if (!empty($back_label)) {
         $text .= '<span class="list_view_description">'.$back_label.'</span>';
      }
      return $text;
   }

   /** get the item status
    * this method returns the item status in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemStatus ($item) {
      $status = $item->getStatus();
      return $this->_text_as_html_short($status);
   }

   /** get the item information
    * this method returns the item information in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemInfos ($item) {
      $text = '';
      $front_label = '';
      $back_label = '';
      $ref_item = $item->getLinkedItem();
      if (!empty($ref_item) and $ref_item->isA('user')) {
         $text = '<a href="mailto:'.$ref_item->getEmail().'">'.$this->_text_as_html_short($ref_item->getEmail()).'</a>';
      } elseif (!empty($ref_item) and ($ref_item->isA(CS_PROJECT_TYPE) or $ref_item->isA(CS_COMMUNITY_TYPE))) {
         $title = $this->_text_as_html_short($item->getTitle());
         if ( $title == $this->_translator->getMessage('TASK_ROOM_MOVE')) {
        	$back_label = ' '.$this->_translator->getMessage('ROOM_MOVE_REQUESTED');
         }
         if ( $ref_item->moveWithLinkedRooms() ) {
            $explanation = $this->_translator->getMessage('PORTAL_MOVE_ROOM_DESC_WITH_LINKED_ROOMS',$ref_item->getTitle());
         } elseif ( $ref_item->isLockedForMove() ) {
            $explanation = $this->_translator->getMessage('PORTAL_MOVE_ROOM_DESC',$ref_item->getTitle());
         }
         if  (!empty($explanation)) {
            $back_label .= ' <img src="images/unknown.gif" border="0" width="12" title="'.$explanation.'">';
         }
      } elseif (!empty($ref_item) and $ref_item->isA('material')) {
         $text = '';
         if (stristr($item->getTitle(),'REQUEST')) {
            if (!stristr($item->getTitle(),'NEW_VERSION')) {
               $back_label = ' '.$this->_translator->getMessage('TASK_MATERIAL_REQUESTED');
            } else {
               $back_label = ' '.$this->_translator->getMessage('TASK_MATERIAL_NEW_VERSION_REQUESTED');
            }
         } else {
            $back_label = ' '.$this->_translator->getMessage('TASK_MATERIAL_CANCELED');
         }
      }
      if (!empty($front_label)) {
         $text = '<span class="disabled">'.$front_label.'</span>'.$text;
      }
      if (!empty($back_label)) {
         $text .= '<span class="disabled">'.$back_label.'</span>';
      }
      return $text;
   }

   /** get the possible actions
    * this method returns the possible actions in the right formatted style
    *
    * @return string item date
    *
    * @author CommSy Development Group
    */
   function _getItemActions ($item) {
      $actions = '';
      $task_title = $this->_text_as_html_short($item->getTitle());
      $ref_item = $item->getLinkedItem();
      if (empty($ref_item) or $ref_item->isDeleted()) {
         // do nothing
      } elseif (!empty($ref_item) and $ref_item->isA('user')) {
         $params = array();
         $params['iid'] = $ref_item->getItemID();
         $actions = ahref_curl($this->_environment->getCurrentContextID(),'account','detail',$params,$this->_translator->getMessage('COMMON_LOOK_AT'));
         if ($ref_item->isRequested()) {
            $params['status'] = 'free';
            $free_url = ahref_curl($this->_environment->getCurrentContextID(),'account','automatic',$params,$this->_translator->getMessage('ADMIN_USER_FREE'));
            $params['status'] = 'reject';
            $reject_url = ahref_curl($this->_environment->getCurrentContextID(),'account','automatic',$params,$this->_translator->getMessage('ADMIN_USER_REJECT'));
            $actions .= ' - '.$free_url.' - '.$reject_url;
         }
         unset($params);
      } elseif (!empty($ref_item) and ($ref_item->isA(CS_PROJECT_TYPE) or $ref_item->isA(CS_COMMUNITY_TYPE))) {
         $title = $this->_text_as_html_short($item->getTitle());
         if ( $title == $this->_translator->getMessage('TASK_ROOM_MOVE')) {
            if ( $item->getStatus() == 'REQUEST' ) {
               $params = array();
               $params['iid'] = $ref_item->getItemID();
               $params['tid'] = $item->getItemID();
               $params['modus'] = 'agree';
               $admin_url = ahref_curl($this->_environment->getCurrentContextID(),'configuration','move2',$params,$this->_translator->getMessage('COMMON_ACCEPT'));
               $params['modus'] = 'reject';
               $admin_url .= BR.ahref_curl($this->_environment->getCurrentContextID(),'configuration','move2',$params,$this->_translator->getMessage('COMMON_REJECT'));
               unset($params);
            } else {
               $admin_url = '<span class="disabled">'.$this->_translator->getMessage('COMMON_ACCEPT').'</span>';
               $admin_url = BR.'<span class="disabled">'.$this->_translator->getMessage('COMMON_REJECT').'</span>';
            }
         }
         $actions = $admin_url;
      } elseif (!empty($ref_item) and $ref_item->isA('material')) {
         if ($ref_item->isNotRequestedForPublishing()) {
            $module = 'material';
         } else {
            $module = 'material_admin';
         }
         $params = array();
         $params['iid'] = $ref_item->getItemID();
         $actions = ahref_curl( $this->_environment->getCurrentContextID(),
                              $module,
                              'detail',
                              $params,
                              $this->_translator->getMessage('COMMON_LOOK_AT'));
         unset($params);
         if ($ref_item->isRequestedForPublishing() and $item->getStatus() != 'CLOSED') {
            $actions .= ' - ';
            $params = array();
            $params['mode'] = 'public';
            $params['id'] = $ref_item->getItemID();
            $params['automail'] = 'true';
            $link = ahref_curl( $this->_environment->getCurrentContextID(),
                                'task',
                                'material',
                                $params,
                                $this->_translator->getMessage('COMMON_ACCEPT'));
            $actions .= $link.' - ';
            $params['mode'] = 'not_public';
            $link = ahref_curl( $this->_environment->getCurrentContextID(),
                                'task',
                                'material',
                                $params,
                                $this->_translator->getMessage('COMMON_REJECT'));
            $actions .= $link;
         }
      }
      return $actions;
   }
}
?>