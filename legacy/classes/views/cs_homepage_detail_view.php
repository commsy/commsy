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
class cs_homepage_detail_view extends cs_detail_view {

   private $_is_form = false;
   private $_form_values = array();
   private $_form_post_values = array();
   private $_form_mandatory_values = array('title','description');

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_homepage_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   public function switch2Form () {
      $this->_is_form = true;
   }

   public function loadValues () {
      if ( isset($this->_item) ) {
         $this->_form_values['iid'] = $this->_item->getItemID();
         $this->_form_values['rid'] = $this->_item->getFatherID();
         $this->_form_values['title'] = $this->_item->getTitle();
         $this->_form_values['description'] = $this->_item->getDescription();
         $this->_form_values['public'] = $this->_item->isPublic();
         $this->_form_values['file_id_array'] = $this->_item->getFileIDArray();
      }

      if ($this->_form_post_values) {
         $this->_form_values = $this->_form_post_values;
      }
   }

   public function setFormPost ($value) {
      $this->_form_post_values = $value;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML ($item) {
      $html = LF.'<!-- BEGIN OF HOMEPAGE ITEM DETAIL -->'.LF;
      if (!$this->_is_form) {
         $desc = $item->getDescription();
         if ( !empty($desc) ) {
            $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
            $desc = $this->_show_images($desc, $item, true);
            $html .= $desc.LF;
         }
      } else {
         $html .= $this->_getHiddenFieldAsHTML('iid');
         $html .= $this->_getHiddenFieldAsHTML('rid');
         $html .= $this->_getHiddenFieldAsHTML('file_id_array');
         $html .= $this->_getRubrikButtonAsHTML('option').LF;
         $html .= $this->_getTextAreaAsHTML('description').LF;
         $html .= $this->_getSCDButtonsAsHTML('option').LF;
      }

      // children and files
      $child_html = $this->_getChildrenAsHTML($item);
      $file_html  = $this->_getFilesAsHTML($item);
      if (!empty($child_html) or !empty($file_html) ) {
         $html .= BRLF.BRLF.'<table style="clear:both" summary="Layout">'.LF;
         if ( !empty($child_html) ) {
            $html .= '<tr>'.LF;
            $html .= $child_html;
            $html .= $this->_getFilesAsHTML($item,'padding-left:50px;');
            $html .= '</tr>'.LF;
         } elseif ( !empty($file_html) ) {
            $html .= '<tr>'.LF;
            $html .= $file_html;
            $html .= '</tr>'.LF;
         }
         $html .= '</table>'.LF;
      }

      $html  .= '<!-- END OF HOMEPAGE ITEM DETAIL -->'.LF.LF;

      return $html;
   }

   /** get hiddenfield as HTML - internal, do not use
    * this method returns a string contains an hiddenfield in HMTL-Code
    *
    * @param array value form element: hiddenfield, see class cs_form
    *
    * @return string hiddenfield as HMTL
    */
   private function _getHiddenFieldAsHTML ($name) {
      $form_element = array();
      $form_element['name'] = $name;
      if ( !empty($this->_form_values[$name]) ) {
         $form_element['value'] = $this->_form_values[$name];
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

   private function _getTextAreaAsHTML ($name) {
      $form_element = array();
      $form_element['name'] = $name;
      $form_element['vsize'] = '94';
      $form_element['hsize'] = '25';
      $form_element['wrap'] = 'virtual';
      $form_element['tabindex'] = 2;
      if ( !empty($this->_form_values[$name]) ) {
         $form_element['value'] = $this->_form_values[$name];
      }

      $html  = '';

      $normal = '<textarea name="'.$form_element['name'].'"';
      $normal .= ' cols="'.$form_element['vsize'].'"';
      $normal .= ' rows="'.$form_element['hsize'].'"';
      $normal .= ' wrap="'.$form_element['wrap'].'"';
      $normal .= ' tabindex="'.$form_element['tabindex'].'"';
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $normal .= ' disabled="disabled"';
      }
      $normal .= '>';
     if ( !empty($form_element['value']) ) {
       $normal .= $this->_text_as_form($form_element['value']);
     } else {
        $form_element['value'] = '';
     }
      $normal .= '</textarea>'.LF;
      $normal .= LF;

     $current_module = $this->_environment->getCurrentModule();
     $current_function = $this->_environment->getCurrentFunction();
      $current_context = $this->_environment->getCurrentContextItem();
     $html_status = $current_context->getHtmlTextAreaStatus();
     $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
     $html .= $normal;
      return $html;
   }

   function _getTitleAsHTML () {
      $html = '';
      if (!$this->_is_form) {
         $item = $this->getItem();
         if ( isset($item) ){
            $html = $item->getTitle();
         } else {
            $html = 'NO ITEM';
         }
         $html = $this->_text_as_html_short($html).LF;
      } else {
         $html .= $this->_getTextFieldAsHTML('title').LF;
      }
      return $html;
   }

   private function _getRubrikButtonAsHTML ($name) {
      $retour = '';
      $form_element = array();
      $form_element['name'] = $name;
      $form_element['label'] = $this->_translator->getMessage('HOMEPAGE_RUBRIK_BUTTON');
      if ( ( isset($this->_form_value['iid']) and $this->_form_value['iid'] == 'NEW' )
             or ( isset($this->_item) and $this->_item->isSpecialPage() )
             or ( empty($this->_form_values['iid']) )
         ) {
         $retour .= $this->_getButtonAsHTML($form_element['label'],$form_element['name'],9).LF;
      } else {
         $retour .= $this->_getButtonAsHTML($form_element['label'],$form_element['name'],9,true).LF;
      }
      return $retour;
   }

   private function _getSCDButtonsAsHTML ($name) {
      $retour = '';
      $form_element = array();
      $form_element['name'] = $name;
      $form_element['labelSave'] = $this->_translator->getMessage('HOMEPAGE_SAVE_BUTTON');
      $form_element['labelCancel'] = $this->_translator->getMessage('COMMON_CANCEL_BUTTON');
      $form_element['labelDelete'] = $this->_translator->getMessage('HOMEPAGE_DELETE_BUTTON');

      $retour .= '<div style="width: 100%;">'.LF;
      $retour .= '<span style="float:right">'.LF;
      if ( ( isset($this->_form_value['iid']) and $this->_form_value['iid'] == 'NEW' )
             or ( isset($this->_item) and $this->_item->isSpecialPage() )
             or ( empty($this->_form_values['iid']) )
         ) {
         $retour .= $this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name'],5,true).LF;
      } else {
         $retour .= $this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name'],5).LF;
      }
      $retour .= '</span>'.LF;
      $retour .= '<span>'.LF;
      $retour .= $this->_getButtonAsHTML($form_element['labelSave'],$form_element['name'],3).LF;
      $retour .= $this->_getButtonAsHTML($form_element['labelCancel'],$form_element['name'],4).LF;
      $retour .= '</span>'.LF;
      $retour .= '</div>'.LF;

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

   private function _getTextFieldAsHTML ($name) {
      $retour = '';

      $form_element = array();
      $form_element['name'] = $name;
      if ( !empty($this->_form_values[$name]) ) {
         $form_element['value'] = $this->_form_values[$name];
      } else {
         $form_element['value'] = '';
      }
      $form_element['maxlength'] = '';
      $form_element['size'] = 50;
      $form_element['tabindex'] = 1;

      $retour .= '<input type="text" name="'.$form_element['name'].'"';
      $retour .= ' value="'.$this->_text_as_form($form_element['value']).'"';
      if ( !empty($form_element['maxlength']) ) {
         $retour .= ' maxlength="'.$form_element['maxlength'].'"';
      }
      $retour .= ' size="'.$form_element['size'].'"';
      $retour .= ' tabindex="'.$form_element['tabindex'].'"';
      $retour .= ' class="form_title"';
      $retour .= '/>';

      return $retour;
   }

   function _getFiles ($item, $padding='') {
      $file_list='';
      if ( isset($item) ) {
         $files = $item->getFileList();
      } elseif ( !empty($this->_form_values['file_id_array']) ) {
         $file_manager = $this->_environment->getFileManager();
         $file_manager->setIDArrayLimit($this->_form_values['file_id_array']);
         $file_manager->select();
         $files = $file_manager->get();
      } elseif ( !isset($this->_form_values['file_id_array']) and !empty($this->_form_values['iid']) ) {
         $homepage_manager = $this->_environment->getHomepageManager();
         $item = $homepage_manager->getItem($this->_form_values['iid']);
         $files = $item->getFileList();
      }
      if ( isset($files) and !$files->isEmpty() ) {
         $file = $files->getFirst();
         $file_list .= '<span class="bold">'.$this->_translator->getMessage('HOMEPAGE_DETAIL_FILE_LIST').'</span>'.LF;
         $file_list .= '<ul class="detail">'.LF;
         while ($file) {
            $url = $file->getUrl();
            $displayname = $file->getDisplayName();
            $filesize = $file->getFileSize();
            $fileicon = $file->getFileIcon();
            $file_list .= '<li>'.'<a href="'.$url.'" title="'.$displayname.' ('.$filesize.' kb)" target="blank" >'.$fileicon.' '.$displayname.' ('.$filesize.' kb)'.'</a>'.'</li>'.LF;
            $file = $files->getNext();
         }
         $file_list .= '</ul>'.LF;
      }
      if ( !empty($file_list) ) {
         $file_list = '<td style="vertical-align: top;'.$padding.'">'.LF.$file_list;
         $file_list .= '</td>'.LF;
      }
      return $file_list;
   }

   private function _getFilesAsHTML ($item, $padding='') {
      $retour = '';
      $file_list_html = $this->_getFiles($item,$padding);
      if ( !empty($file_list_html) ) {
         $retour .= $file_list_html.LF;
      }
      return $retour;
   }

   private function _getChildrenAsHTML ($item) {
      $retour = '';
      $homepage_manager = $this->_environment->getHomepageManager();
      if ( isset($item) ) {
          $item_id = $item->getItemID();
      } elseif ( !empty($this->_form_values['iid']) ) {
         $item_id = $this->_form_values['iid'];
      } else {
         $item_id = 'NEW';
      }
      if ( !empty($item_id) and $item_id != 'NEW') {
         $child_list = $homepage_manager->getChildList($item_id);
         if (!$child_list->isEmpty()) {
            $retour .= '<span class="bold">'.$this->_translator->getMessage('HOMEPAGE_DETAIL_SUBNAVIGATION').'</span>'.LF;
            $retour .= '<ul class="detail">'.LF;
            $child_item = $child_list->getFirst();
            while ($child_item) {
               $params = array();
               $params['iid'] = $child_item->getItemID();
               $link = ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$child_item->getTitle());
               $retour .= '<li>'.$link.'</li>'.LF;
               unset($params);
               $child_item = $child_list->getNext();
            }
            $retour .= '</ul>'.LF;
         }
      }
      if ( !empty($retour) ) {
         $retour = '<td style="vertical-align: top;">'.LF.$retour;
         $retour .= '</td>'.LF;
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

     if ($this->_is_form) {
       $action = curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),array());
       $html .= '<form action="'.$action.'" method="post" enctype="multipart/form-data" name="f">'.LF;
     }

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

     if ($this->_is_form) {
       $html .= '</form>'.LF;
     }

      return $html;
   }

   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for setFocus on first input field
    */
   function getInfoForHeaderAsHTML () {
      $html  = '';
     $with_javascript = false;
     $session = $this->_environment->getSessionItem();
     if ($session->issetValue('javascript')) {
       $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
         $with_javascript = true;
       }
     }

      if ($with_javascript) {
         $html .= '   <script type="text/javascript">'.LF;
         $html .= '      <!--'.LF;
         $html .= '         function setfocus() {'.LF;
         // jQuery
         //$html .= '           document.f.elements["title"].focus(); '.LF;
         $html .= '           jQuery("input[name=\'title\'], f").focus(); '.LF;
         // jQuery
         $html .= '           alert("test"); '.LF;
         $html .= '         }'.LF;
         $html .= '      -->'.LF;
         $html .= '   </script>'.LF;
      }
      return $html;
   }

   /** get information for body as HTML
    * this method returns information in HTML-Code needs for the body of the HTML-Page
    *
    * @return string  needed for setFocus on first input field
    */
   function getInfoForBodyAsHTML () {
      $html  = '';
     $with_javascript = false;
     $session = $this->_environment->getSessionItem();
     if ($session->issetValue('javascript')) {
       $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
         $with_javascript = true;
       }
     }
      if ($with_javascript and $this->_environment->getCurrentFunction() == 'edit') {
         $html .= ' onload="setfocus()"';
      }
      return $html;
   }

   public function check () {
      $retour = true;
      if ( !empty($this->_form_mandatory_values) ) {
         foreach ($this->_form_mandatory_values as $field_name) {
            if ( empty($this->_form_values[$field_name]) ) {
               $retour = false;
            }
         }
      }
      return $retour;
   }
}
?>