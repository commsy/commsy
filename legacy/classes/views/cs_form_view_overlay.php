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
$this->includeClass(FORM_VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_form_view_overlay extends cs_form_view {

   private $_back_link = '';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct ($params) {
      cs_form_view::__construct($params);
   }

   public function setBackLink ($value) {
      $this->_back_link = $value;
   }

   private function _getBackLink () {
      $retour = '';
      if ( !empty($this->_back_link) ) {
         include_once('functions/curl_functions.php');
         $retour = ahref_curl2($this->_back_link,'X',
                               '','', '', '', '', 'class="titlelink"');
      } else {
         $params = $this->_environment->getCurrentParameterArray();
         $retour = ahref_curl( $this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              $this->_environment->getCurrentFunction(),
                              $params,
                              'X',
                              '','', '', '', '', '', 'class="titlelink"');
      }
      return $retour;
   }

   public function _getHeadline () {
      $retour = '';
      if ( !empty($this->_form) ) {
         $retour = $this->_form->getHeadline();
      }
      return $retour;
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
      $html .= '<!-- BEGIN OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'.LF;
      $html .= '<div class="form_view_overlay_formelement">'.LF;
      if (!empty($form_element_array[0]['label'])) {
         $html .= '<div class="overlay_form_element_title">'.LF;
         if ( isset($form_element_array[0]['failure']) ) {
            $label = '<span class="bold">'.$form_element_array[0]['label'].'</span>';
         } else {
            $label = $form_element_array[0]['label'];
         }
         if ( $form_element_array[0]['type'] == 'titlefield' ) {
            $label = '<span class="titlefield">'.$label.'</span>';
         }
         $html .= $label;
         if (!empty($form_element_array[0]['mandatory'])) {
            if ( $label[strlen($label)-1] == ':' ) {
               $red_star = '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               $html = str_replace($label,substr($label,0,strlen($label)-1).$red_star.':',$html);
            } else {
               $html.= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>'.LF;
            }
         }
         $html .= '</div>'.LF;
      }

      // form element
      $counter = 0;
      $first = true;
      foreach ($form_element_array as $form_element) {
         $counter++;
         if ($first) {
            $first = false;
         }

         $html .= '<div class="overlay_form_element_content">'.LF;
         if (!empty($form_element['before_form_text'])) {
            $html .= '         '.$form_element['before_form_text'].LF;
         }
         if ($form_element['type'] == 'textarea') {
            $html .= '         '.$this->_getTextAreaAsHTML($form_element);
            $html .= $this->_getTextFormatingInformationAsHTML($form_element);
         } elseif ($form_element['type'] == 'titlefield') {
            $html .= '         '.$this->_getTitleFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'textfield') {
            $html .= '         '.$this->_getTextFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'password') {
            $html .= '         '.$this->_getPasswordAsHTML($form_element);
         } elseif ($form_element['type'] == 'select') {
            $html .= '         '.$this->_getSelectAsHTML($form_element);
         } elseif ($form_element['type'] == 'selectgroup') {
            $html .= '         '.$this->_getSelectGroupAsHTML($form_element);
         }  elseif ($form_element['type'] == 'checkbox') {
            $html .= '         '.$this->_getCheckboxAsHTML($form_element).LF;
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
         $html .= '</div>'.LF;
      }

      if ( !empty($form_element_array[0]['example'])
           and count($form_element_array)
           and count($form_element_array) == $counter
         ) {
         $html .= '<div class="overlay_form_element_example">'.LF;
         $html .= $form_element_array[0]['example'];
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'.LF.LF;
      return $html;
   }

   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-VIEW OVERLAY -->'.LF;
      $html .= '<div class="overlay_box">'.LF;
      $html .= '   <div>'.LF;
      $html .= '   <div class="overlay_title_backlink">'.$this->_getBackLink().'</div>';
      $title = $this->_getHeadline();
      if ( !empty($title) ) {
         $html .= '      <h2 class="overlay_title">'.$this->_text_as_html_short($title).'</h2>';
      }
      $html .= '   </div>'.LF;
      $html .= '   <div class="overlay_content">'.LF;

      if (count($this->_error_array) > 0) {
         $html .= $this->_getErrorBoxAsHTML();
      }

      $html .= '<form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'.LF;

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
         $html .= $this->_getFormElementAsHTML($form_element);
      }

      $html .= '</form>'.LF;

      $html .= '   </div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END OF FORM-VIEW OVERLAY -->'.LF.LF;

      return $html;
   }

   public function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '   <link rel="stylesheet" href="css/commsy_overlay_css.php?cid='.$this->_environment->getCurrentContextID().'" />';
      return $retour;
   }
}
?>