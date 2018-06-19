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
class cs_search_short_view extends cs_view {

   /**
    * string - containing the URL where data will post to
    */
   var $_action = NULL;

   /**
    * string - containing the action type
    */
   var $_action_type = 'post';

   /**
    * array - array of form elements from class cs_form, headline of the form view is the first element of this array
    */
   var $_form_elements;

   /**
    * object - a form object
    */
   var $_form = NULL;

  /**
   * array - containing strings of error messages
   */
   var $_error_array = array();

  /**
   * int - number of form elements
   */
   var $_count_form_elements;

  /**
   * boolean - adds header infos if true
   */
   var $_with_javascript = true;

   var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  einvironment           einvironemnt of the commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function __construct ($params) {
      cs_view::__construct($params);
      $this->setViewName('search');
      $this->_view_title = $this->_translator->getMessage('CAMPUS_SEARCH_INDEX');
   }

   /** set URL of the form view
    * this method sets the URL where the data will post to
    *
    * @param string value <form action="URL">
    */
   function setAction ($value) {
      $this->_action = (string)$value;
   }

   /** set action type of the form view
    * this method sets the action type
    *
    * @param string value <form ... method="action_type">
    */
   function setActionType ($value) {
      $this->_action_type = (string)$value;
   }

   /** set form form the form view
    * this method sets the form for the form view
    *
    * @param array value form elements
    */
   function setForm ($value) {
      $this->_form = $value;
      $this->_form_elements = $this->_form->getFormElements();
      $this->_error_array = $this->_form->getErrorArray();
   }


   /** get hiddenfield as HTML - internal, do not use
    * this method returns a string contains an hiddenfield in HMTL-Code
    *
    * @param array value form element: hiddenfield, see class cs_form
    *
    * @return string hiddenfield as HMTL
    */
   function _getHiddenFieldAsHTML ($form_element) {
      $html  = '';
      $html .= '   <input type="hidden" name="'.$form_element['name'].'"';
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"/>'.LF;
      return $html;
   }

   /** get button as HTML - internal, do not use
    * this method returns a string contains a button in HMTL-Code
    *
    * @param array value form element: button, see class cs_form
    *
    * @return string button as HMTL
    */
   function _getButtonAsHTML ($button_text, $button_name) {
      $html  = '';
      $html .= '<input type="submit" style="font-size:8pt; width:70px;" name="'.$button_name.'"';
      $html .= ' value="'.$button_text.'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= '/>';
      return $html;
   }

   /** get selectbox as HTML - internal, do not use
    * this method returns a string contains an selectbox in HMTL-Code
    *
    * @param array value form element: selectbox, see class cs_form
    *
    * @return string selectbox as HMTL
    */
   function _getSelectAsHTML ($form_element) {
      $html = '';
      if ($form_element['multiple']) {
         $form_element['name'] .= '[]';
      }
      $html .= '<select name="'.$form_element['name'].'"';
      $html.=' style="width:10em; font-size:8pt;"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= '>'."\n";
      $options = $form_element['options'];
      $option = reset($options);
      while ($option) {
         if (!isset($option['value'])) {
            $option['value'] = $option['text'];
         }
         $html .= '            <option value="'.$this->_text_as_form($option['value']).'"';
         if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
            $html .= ' selected';
         }
         $html .= '>';
         $html .= $this->_text_as_html_short($option['text']);
         $html .= '</option>'."\n";
         $option = next($options);
      }
      $html .= '         </select>'."\n";
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
      return $html;
   }

   /** get textfield as HTML - internal, do not use
    * this method returns a string contains an textfield in HMTL-Code
    *
    * @param array value form element: textfield, see class cs_form
    *
    * @return string textfield as HMTL
    */
   function _getTextFieldAsHTML ($form_element) {
      $html = '<input type="text" name="'.$form_element['name'].'"';
      if (!empty($form_element['value'])) {
         $html .= ' value="'.$this->_text_as_form($form_element['value']).'"';
      } else {
         $html .= ' value=""';
      }
      $html .= ' style="width:13.7em; font-size:10pt;"';
      $html .= ' maxlength="'.$form_element['maxlength'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= ' class="text"';
      $html .= '/>';
      return $html;
   }

   /** get checkbox as HTML - internal, do not use
    * this method returns a string contains an checkbox in HMTL-Code
    *
    * @param array value form element: checkbox, see class cs_form
    *
    * @return string checkbox as HMTL
    */
   function _getCheckboxAsHTML ($form_element) {
      $html  = '';
      $html .= '<input type="checkbox" name="'.$form_element['name'].'" value="'.$this->_text_as_form($form_element['value']).'"';
      if ($form_element['ischecked']) {
         $html .= ' checked';
      }
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $html .= ' disabled="disabled"';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
         $html .= ' '.$form_element['extention'];
      }
      $html .= ' style="margin-left:0px;"/>&nbsp;';
      $text = $form_element['text'];
      if ( !empty($form_element['no_html_decode']) and $form_element['no_html_decode'] ) {
         $html .= $text;
      } else {
         $html .= $this->_text_as_html_short_coding_format($text);
      }
      return $html;
   }

   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML () {
      $html  = '';
      if (count($this->_error_array) > 0) {
         $html .= $this->_getErrorBoxAsHTML();
      }
      $html .= '<!-- BEGIN OF FORM-VIEW PLAIN -->'."\n";
      $html .= '<form style="margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="form">'."\n";
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box" style="height:90px;">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="height:90px;">'.LF;
      $form_element = $this->_form_elements->getFirst();
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }
      // first all hidden elements

      // prepare form elements, especially combine form fields
      $form_element_array = array();
      $form_element = $this->_form_elements->getFirst();
      $temp_array = array();
      $failure = false;
      $mandatory = false;
      $this->_count_form_elements = 0;
      while ($form_element) {
         if ($form_element['type'] != 'hidden') {
            if (!empty($form_element['combine']) and $form_element['combine']) {
               $temp_array[] = $form_element;
               if (!empty($form_element['failure']) and $form_element['failure']) {
                  $failure = true;
               }
               if (!empty($form_element['mandatory']) and $form_element['mandatory']) {
                  $mandatory = true;
               }
            } else {
               $temp_array[] = $form_element;
               if (count($temp_array) == 1) {
                  $form_element_array[] = $temp_array[0];
               } else {
                  if (!empty($form_element['failure']) and $form_element['failure']) {
                     $failure = true;
                  }
                  if (!empty($form_element['mandatory']) and $form_element['mandatory']) {
                     $mandatory = true;
                  }
                  if ($failure) {
                     $temp_array[0]['failure'] = true;
                     $failure = false;
                  }
                  if ($mandatory) {
                     $temp_array[0]['mandatory'] = true;
                     $mandatory = false;
                  }
                  $form_element_array[] = $temp_array;
               }
               $temp_array = array();
            }
         }
         $this->_count_form_elements++;
         $form_element = $this->_form_elements->getNext();
      }

      // now get the html code
      $bool = true;
      $html .= '<div style="padding-top:10px;">';
      $html .= ''.LF;
       foreach ($form_element_array as $form_element) {
         if (!isset($form_element[0]['type']) and $form_element['type'] == 'headline') {
         } elseif($form_element['type'] == 'textfield') {
             $html .= '<input style="width:184px; font-size:10pt; margin-bottom:5px;" name="'.$form_element['name'].'" type="text" size="20" value="'.$form_element['value'].'"/>'.LF;
         } elseif($form_element['type'] == 'select') {
            $html .= $this->_getSelectAsHTML($form_element).BRLF;
         } elseif($form_element['type'] == 'checkbox') {
            $html .= $this->_getCheckboxAsHTML($form_element).BRLF;
         }
      }
     $html .= '</div>'.LF;
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</form>'."\n";
      $html .= '<!-- END OF FORM-VIEW PLAIN -->'."\n\n";
      return $html;
   }

  /** get first input field
    * this method returns the name of the first input field, needed for setFocus
    *
    * @return string name of first input field
    */
   function _getFirstInputFieldName() {
      $form_element = $this->_form_elements->getFirst();
      $result = '';
      while ( $form_element and $result == '') {
         if ( $form_element['type'] != 'hidden' and $form_element['type'] != 'text' and $form_element['type'] != 'headline' and $form_element['type'] != 'radio') {
            if ($form_element['type'] == 'datetime') {
               $result = $form_element['firstName'];
            } else {
               $result = $form_element['name'];
            }
         }
         $form_element = $this->_form_elements->getNext();
      }
      return $result;
   }

   function withoutJavascript () {
      $this->_with_javascript = false;
   }


   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for setFocus on first input field
    */
   function getInfoForHeaderAsHTML () {
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $conf = $context_item->getHomeRightConf();
      if ( !empty($conf) ) {
         $rubrics = explode(',', $conf);
      } else {
         $rubrics = array();
      }
      $set_focus = true;
      foreach ( $rubrics as $rubric ) {
         $rubric_array = explode('_', $rubric);
         if ( $rubric_array[0] == 'search' and ($rubric_array[1] == 'tiny' or $rubric_array[1] == 'nodisplay') ) {
            $set_focus = false;
         }
      }
/*      if ($this->_with_javascript and $set_focus) {
         $html .= '   <script type="text/javascript">'."\n";
         $html .= '      <!--'."\n";
         $html .= '         function setfocus() { document.form.'.$this->_getFirstInputFieldName().'.focus(); }'."\n";
         $html .= '      -->'."\n";
         $html .= '   </script>'."\n";
      } */
      return $html;
   }

   /** get information for body as HTML
    * this method returns information in HTML-Code needs for the body of the HTML-Page
    *
    * @return string  needed for setFocus on first input field
    */
   function getInfoForBodyAsHTML () {
      $html  = '';
#      if ($this->_with_javascript) {
#         $html .= ' onload="setfocus()"';
#      }
      return $html;
   }

  /** internal method to create errorbox if there are errors, INTERNAL
    * this method creates an errorbox with messages form the error array
    */
   function _getErrorBoxAsHTML () {
      $params = array();
      $params['environment'] = $this->_environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $this->_class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $first = true;
      $error_string = '';
      foreach ($this->_error_array as $error) {
         $error_string .= $error."\n";
      }
      $errorbox->setText($error_string);
      return $errorbox->asHTML();
   }
}
?>