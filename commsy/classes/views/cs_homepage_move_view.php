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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy detail-view: homepage
 */
class cs_homepage_move_view extends cs_detail_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct ($params) {
      $this->cs_detail_view($params);
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML ($item) {

      $html = LF.'<!-- BEGIN OF HOMEPAGE ITEM MOVE -->'.LF;

     $homepage_manager = $this->_environment->getHomepageManager();
     $father_list = $homepage_manager->getFatherItemList($this->_item->getItemID());

     if ( !$father_list->isEmpty() ) {
        $father_count = $father_list->getCount();
        $father_item = $father_list->getFirst();
        $padding = 30;
        while ($father_item) {
          $title = $father_item->getTitle();
          if ( $father_item->isRootPage() ) {
            $title = $this->_translator->getMessage('HOMEPAGE_TITLE_ROOT_PAGE');
          }
          $html .= '<ul style="padding-left:'.$padding.'px; margin:0px;"><li>'.$title.'</li></ul>'.LF;
          $padding = $padding + 20;
             $father_item = $father_list->getNext();
        }

        $father_item = $father_list->getLast();
        $html .= $this->_getChildrenAsHTML($father_item,$padding,$father_count);
     }

     // form
     $html .= '<form action="'.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),'').'" method="post">'.LF;
     $html .= $this->_getHiddenFieldAsHTML('iid',$this->_item->getItemID());
     $html .= BRLF.$this->_getEndButtonAsHTML('option');
     $html .= '</form>'.LF;

      $html .= '<!-- END OF HOMEPAGE ITEM MOVE -->'.LF.LF;

      return $html;
   }

   function _getTitleAsHTML () {
      $html = '';
      $item = $this->getItem();
      $html = $item->getTitle();
      $html = $this->_text_as_html_short($html).LF;
      return $html;
   }

   private function _getEndButtonAsHTML ($name) {
      $retour = '';
      $form_element = array();
      $form_element['name'] = $name;
      $form_element['label'] = $this->_translator->getMessage('HOMEPAGE_MOVE_END_BUTTON');
      $retour .= $this->_getButtonAsHTML($form_element['label'],$form_element['name'],9).LF;
      return $retour;
   }

   private function _getButtonAsHTML ($button_text, $button_name, $tabindex='', $is_disabled='') {
      $html  = '';
      $html .= '<input type="submit" name="'.$button_name.'"';
      $html .= ' value="'.$button_text.'"';
      $html .= ' tabindex="'.$tabindex.'"';
      if ( $is_disabled ){
         $html .= ' disabled="disabled"';
      }
      $html .= '/>';
      return $html;
   }

   /** get hiddenfield as HTML - internal, do not use
    * this method returns a string contains an hiddenfield in HMTL-Code
    *
    * @param array value form element: hiddenfield, see class cs_form
    *
    * @return string hiddenfield as HMTL
    */
   private function _getHiddenFieldAsHTML ($name, $value='') {
      $form_element = array();
      $form_element['name'] = $name;
      if ( !empty($value) ) {
         $form_element['value'] = $value;
      } else {
         $form_element['value'] = '';
      }

      $html  = '';
     if ( !is_array($form_element['value']) ) {
         $html .= '   <input type="hidden" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form($form_element['value']).'"/>'.LF;
     } else {
       foreach ($form_element['value'] as $element_value) {
            $html .= '   <input type="hidden" name="'.$form_element['name'].'[]"';
            $html .= ' value="'.$this->_text_as_form($element_value).'"/>'.LF;
       }
     }
      return $html;
   }

   private function _getChildrenAsHTML ($item, $padding, $layer) {
      $retour = '';
      $homepage_manager = $this->_environment->getHomepageManager();
      if ( isset($item) ) {
          $item_id = $item->getItemID();
      } elseif ( !empty($this->_form_values['iid']) ) {
         $item_id = $this->_form_values['iid'];
      } else {
         $item_id = 'NEW';
      }
      $child_list = $homepage_manager->getChildList($item_id);
      if (!$child_list->isEmpty()) {
         $retour .= '<ul style="padding-left:'.$padding.'px; margin:0px;">'.LF;
         $child_item = $child_list->getFirst();
         $first = true;
         $count_list = $child_list->getCount();
         $counter = 1;
         while ($child_item) {
            if ($child_item->getItemID() != $this->_item->getItemID()) {
               $link = $child_item->getTitle();
            } else {
               $link = '<span class="bold">'.$child_item->getTitle().'</span>&nbsp;';
               if ($first) {
                  $icon = '&nbsp;<img src="images/browse_left_grey2.gif">&nbsp;';
                  $link .= $icon;
               } else {
                  $params = array();
                  $params['iid'] = $child_item->getItemID();
                  $params['direction'] = 'up';
                  $icon = '&nbsp;<img src="images/browse_left2.gif">&nbsp;';
                  $link .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$icon);
                  unset($params);
               }
               if ($count_list == $counter) {
                  $icon = '&nbsp;<img src="images/browse_right_grey2.gif">&nbsp;';
                  $link .= $icon;
               } else {
                  $params = array();
                  $params['iid'] = $child_item->getItemID();
                  $params['direction'] = 'down';
                  $icon = '&nbsp;<img src="images/browse_right2.gif">&nbsp;';
                  $link .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$icon);
                  unset($params);
               }
               if ($layer == 1) {
                  $icon = '&nbsp;<img src="images/browse_left_grey3.gif">&nbsp;';
                  $link .= $icon;
               } else {
                  $params = array();
                  $params['iid'] = $child_item->getItemID();
                  $params['direction'] = 'left';
                  $icon = '&nbsp;<img src="images/browse_left3.gif">&nbsp;';
                  $link .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$icon);
                  unset($params);
               }
               if ($first) {
                  $icon = '&nbsp;<img src="images/browse_right_grey3.gif">&nbsp;';
                  $link .= $icon;
               } else {
                  $params = array();
                  $params['iid'] = $child_item->getItemID();
                  $params['direction'] = 'right';
                  $icon = '&nbsp;<img src="images/browse_right3.gif">&nbsp;';
                  $link .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$icon);
                  unset($params);
               }
            }
            $retour .= '<li>'.$link.'</li>'.LF;
            $child_item = $child_list->getNext();
            $counter++;
            if ($first) {
               $first = false;
            }
         }
         $retour .= '</ul>'.LF;
      }
      return $retour;
   }

   /** get detail view as HTML
    * this method returns the detail view in HTML-Code
    *
    * @returns string detail view as HMTL
    */
   function asHTML () {
      $item = $this->getItem();

      $html  = LF.'<!-- BEGIN OF DETAIL VIEW -->'.LF;

      // Title
     $html .= LF.'<h2>'.LF;
     $html .= $this->_getTitleAsHTML();
     $html .= '</h2>'.LF.LF;

      // The Item
      if ( isset($item) or $this->_is_form) {

         $html .= '<div class="item">'.LF;
         $html .= $this->_getItemAsHTML($item);
         $html .= '</div>'.LF;

      } else {
         $html .= '<!-- No item set! -->'.LF;

     }

      return $html;
   }
}
?>