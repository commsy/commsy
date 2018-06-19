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
class cs_form_view_plain extends cs_view {

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

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
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

   /** get headline as HTML - internal, do not use
    * this method returns a string contains a headline in HMTL-Code
    *
    * @param array value form element: headline, see class cs_form
    *
    * @return string headline as HMTL
    */
   function _getHeadLineAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: headline -->'.LF;
      $html .= '   <h2>'.LF;
      $html .= '      '.$this->_text_as_html_short($form_element['label']).LF;
      if (!empty($form_element['description'])) {
         $html .= '      <span class="small">('.$this->_text_as_html_short($form_element['description']).')</span>'.LF;
      }
      $html .= '   </h2>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: headline -->'.LF;
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
      $html .= '<input type="submit" name="'.$button_name.'"';
      $html .= ' value="'.$this->_text_as_html_short($button_text).'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= '/>';
      return $html;
   }

   /** get buttonbar as HTML - internal, do not use
    * this method returns a string contains a buttonbar (save, cancel and delete) in HMTL-Code
    *
    * @param array value form element: buttonbar, see class cs_form
    *
    * @return string buttonbar as HMTL
    */
   function _getButtonBarAsHTML ($form_element) {
      $html = '';
      if (!empty($form_element['labelSave'])) {
         $html .= '   &nbsp;'.$this->_getButtonAsHTML($form_element['labelSave'],$form_element['name'])."\n";
      }
      if (!empty($form_element['labelSecondSave'])) {
         $html .= '   &nbsp;'.$this->_getButtonAsHTML($form_element['labelSecondSave'],$form_element['name'])."\n";
      }
      if (!empty($form_element['labelCancel'])) {
         $html .= '   &nbsp;'.$this->_getButtonAsHTML($form_element['labelCancel'],$form_element['name'])."\n";
      }
      if (!empty($form_element['labelDelete'])) {
         $html .= '                   '.$this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name']).'&nbsp;'."\n";
      }
      return $html;
   }

   /** get emptyline as HTML - internal, do not use
    * this method returns a string contains an emptyline in HMTL-Code
    *
    * @param array value form element: emptyline, see class cs_form
    *
    * @return string emptyline as HMTL
    */
   function _getEmptyLineAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- empty line -->';
      $html .= '&nbsp;'.LF;
      return $html;
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
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"/>'."\n";
      return $html;
   }

   /** get textline as HTML - internal, do not use
    * this method returns a string contains a text in HMTL-Code
    *
    * @param array value form element: text, see class cs_form
    *
    * @return string textline as HMTL
    */
   function _getTextAsHTML ($form_element) {
      $html  = '';
      if (!empty($form_element['anchor'])){
         $html='<a name="'.$form_element['anchor'].'"></a>';
      }
      if (!empty($form_element['value'])) {
         if ($form_element['isbold']) {
            $html .= '<b>'.$this->_text_as_html_long($form_element['value']).'<b>';
         } else {
            $html .= $this->_text_as_html_long($form_element['value']);
         }
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '         &nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'',false)."\n";
         }
         $html .= '<br />'."\n";
      }
      return $html;
   }

   /** get form element as HTML and in commsy-style- internal, do not use
    * this method returns a string contains a form element in commsy-style in HMTL-Code
    *
    * @param array value form element: form element, see class cs_form
    *
    * @return string form element in commsy-style as HMTL
    */
   function _getFormElementAsHTML ($form_element) {
      // prepare form element array for combined form elements
      $form_element_array = array();
      if (!isset($form_element[0]['type'])) {
         $form_element_array[] = $form_element;
      } else {
         $form_element_array = $form_element;
      }

      // html code
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n";
#      if (empty($this->_description)){
         if (isset($form_element_array[0]['text-align']) and $form_element_array[0]['text-align'] == 'right') {
            $html .= '   <div class="form_view_plain_formelement_right">'."\n";
         } else {
            $html .= '   <div class="form_view_plain_formelement">'."\n";
         }
#      }
      if (!empty($form_element_array[0]['label'])) {
         if (isset($form_element_array[0]['failure'])) {
            $label = '<span class="bold">'.$form_element_array[0]['label'].'</span>';
         } else {
            $label = $form_element_array[0]['label'];
         }
         $html .= $label;
         if (!empty($form_element_array[0]['mandatory'])) {
            $html.= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>'."\n";
         }
         $html .= '<br />'."\n";
      }

      // form element
      if (isset($form_element_array[0]['combine']) and $form_element_array[0]['combine'] == 'horizontal') {
         $horizontal = true;
         $html .= '<table class="form_view_plain_combine" summary="Layout">'."\n";
         $html .= '   <tr>'."\n";
      } else {
         $horizontal = false;
      }

      $first = true;
      foreach ($form_element_array as $form_element) {
         if ($first) {
            $first = false;
         } else {
            $html .= '<!-- COMBINED FIELDS -->'."\n";
         }

         if ($horizontal) {
            if ($form_element['type']=='radio' and (isset($form_element['combine_direct']))){
               $html .= '      <td class="form_view_plain_combine">'."\n";
            }
            elseif (isset($form_element['text-align']) and $form_element['text-align'] == 'right') {
               $html .= '      <td class="right" >'."\n";
            } else {
               $html .= '      <td>'."\n";
            }
         }

         if (!empty($form_element['before_form_text'])) {
            $html .= '         '.$form_element['before_form_text']."\n";
         }

         if ($form_element['type'] == 'textarea') {
            $html .= '         '.$this->_getTextAreaAsHTML($form_element);
         } elseif ($form_element['type'] == 'textfield') {
            $html .= '         '.$this->_getTextFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'password') {
            $html .= '         '.$this->_getPasswordAsHTML($form_element);
         } elseif ($form_element['type'] == 'select') {
            $html .= '         '.$this->_getSelectAsHTML($form_element);
         } elseif ($form_element['type'] == 'selectgroup') {
            $html .= '         '.$this->_getSelectGroupAsHTML($form_element);
         }  elseif ($form_element['type'] == 'checkbox') {
            $html .= '         '.$this->_getCheckboxAsHTML($form_element)."\n";
         } elseif ($form_element['type'] == 'checkboxgroup') {
            $html .= $this->_getCheckboxGroupAsHTML($form_element);
         } elseif ($form_element['type'] == 'file') {
            $html .= '         '.$this->_getFileFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'radio') {
            $html .= $this->_getRadioGroupAsHTML($form_element);
         } elseif ($form_element['type'] == 'datetime') {
            $html .= $this->_getDateTimeFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'emptyline') {
            $html .= '         '.$this->_getEmptyLineAsHTML($form_element);
         } elseif ($form_element['type'] == 'buttonbar') {
            $html .= $this->_getButtonBarAsHTML($form_element);
         } elseif ($form_element['type'] == 'button') {
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name']);
         } elseif ($form_element['type'] == 'textline') {
            $html .= '         '.$this->_getTextLineAsHTML($form_element);
         } elseif ($form_element['type'] == 'text') {
            $html .= '         '.$this->_getTextAsHTML($form_element);
         } elseif ($form_element['type'] == 'color_table') {
            $html .= '         '.$this->_getColorTableAsHTML();
         }

         if ($horizontal) {
            $html .= '      </td>'."\n";
         }
      }

      if ($horizontal) {
         $html .= '   </tr>'."\n";
         $html .= '</table>'."\n";
      }

      // if buttonbar with delete button, delete button will be set into the descripiton field
      // see the _getButtonBarAsHTML() methode
      if (!empty($form_element_array[0]['example'])) {
         $html .= '<br />'."\n";
         $html .= $form_element_array[0]['example'];
      }
#      if (empty($this->_description)){
         $html .= '</div>'."\n";
#      }
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n\n";
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
      $html .= '<form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .= '<table class="form_view_plain" summary="Layout">'."\n";
      $html .= '   <tr>'."\n";
      $html .= '      <td class="form_view_plain">'."\n";

      // first all hidden elements
      $form_element = $this->_form_elements->getFirst();
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }

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
      foreach ($form_element_array as $form_element) {
         if (!isset($form_element[0]['type']) and $form_element['type'] == 'headline') {
            $html .= $this->_getHeadLineAsHTML($form_element);
         } else {
            if ($bool and !empty($this->_description)){
               $bool = false;
               $html .= '<table class="form_view_plain" summary="Layout">'."\n";
               $html .= '   <tr>'."\n";
               $html .= '      <td class="form_view_plain_left">'."\n";
            }
            $html .= $this->_getFormElementAsHTML($form_element);
         }
      }
      if (!empty($this->_description)){
         $html .= '      </td>'."\n";
         $html .= '      <td class="form_view_plain_right">'."\n";
         $html .= $this->_text_as_html_short($this->_description);
         $html .= '      </td>'."\n";
         $html .= '   </tr>'."\n";
         $html .= '</table>'."\n";
      }


      $html .= '      </td>'."\n";
      $html .= '   </tr>'."\n";
      $html .= '</table>'."\n";
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
      if ($this->_with_javascript) {
         $html .= '   <script type="text/javascript">'.LF;
         $html .= '      <!--'."\n";
         // jQuery
         //$html .= '         function setfocus() { document.f.'.$this->_getFirstInputFieldName().'.focus(); }'.LF;
         $html .= '         function setfocus() { jQuery("input[name=\''.$this->_getFirstInputFieldName().'\'], f").focus(); }'.LF;
         // jQuery
         $html .= '      -->'."\n";
         $html .= '   </script>'."\n";
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
      if ($this->_with_javascript) {
         $html .= ' onload="setfocus()"';
      }
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

   function getTitle () {
      $retour = $this->_form->getHeadline();
      return $retour;
   }
}
?>