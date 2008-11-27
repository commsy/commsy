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

/** upper class of the form view
 */
$this->includeClass(FORM_VIEW);
$this->includeClass(ERRORBOX_VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_profile_form_view extends cs_form_view {


   /** get form element as HTML ROW and in commsy-style- internal, do not use
    * this method returns a string contains a form element in commsy-style in HMTL-Code
    *
    * @param array value form element: form element, see class cs_form
    *
    * @return string form element in commsy-style as HMTL
    *
    * @author CommSy Development Group
    */
   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML () {
      $html  = '';
      $netnavigation_array = array();
      $html .='<div id="profile_content">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['show_profile']);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .= '<h2 id="profile_title" style="float:right">'.$title.'</h2>';
      $html .= '<h2 id="profile_title">'.getMessage('COMMON_PROFILE_EDIT').'</h2>';

      $html .= '<form style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .='<div style="width:100%; padding:10px;">'.LF;

      #$html .= '<div class="formdate">'.$date_array[2].'. '.$month.' '.$date_array[0].'</div>';
      if (count($this->_error_array) > 0) {
         $html .= $this->_getErrorBoxAsHTML();
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


         $temp_array = array();
         foreach ($form_element_array as $form_element) {
            $temp_array[] = $form_element;
         }


      //Berechnung der Buttonleiste
      $form_element_array = $temp_array;
      $temp_array=array();
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'buttonbar' ) {
            $this->_count_form_elements = $this->_count_form_elements + 100;
            $buttonbartext = $this->_getButtonBarAsHTML($form_element);
            $this->_count_form_elements = $this->_count_form_elements - 100;
         }else{
            $temp_array[] = $form_element;
         }
      }
      $form_element_array = $temp_array;

      $temp_array = array();
      $html .='<div style="width: 100%;">'.LF;
      $funct = $this->_environment->getCurrentFunction();
      $html .='<div style="width:100%; margin-top:5px; vertical-align:bottom;">'.LF;
      $html .= '<!-- BEGIN OF FORM-VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and $form_element['display']) {
            $html .= '<div id="form_title">';
            if (isset($form_element_array[0]['label'])) {
               if (isset($form_element_array[0]['failure'])) {
                  $label = '<span class="required">'.$form_element_array[0]['label'].'</span>';
               } else {
                  $label = $form_element_array[0]['label'];
               }
               $html .= '&nbsp;'.$label;
               if ( !empty($label) ) {
                  $html .= ':';
               }
               if (!empty($form_element_array[0]['mandatory'])) {
                  $html .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               }
            }
            $html .= '&nbsp;'.$this->_getTitleFieldAsHTML($form_element);
            $show_title_field = true;
            $html .= '</div>';
         }elseif ( isset($form_element['type']) and $form_element['type'] == 'titletext') {
            $html .='<div style="width:100%;">'.LF;
            $html .= $this->_getTitleTextAsHTML($form_element);
            $show_title_field = true;
            $html .= '</div>';
         } elseif ( isset($form_element[0]['type']) and $form_element[0]['type'] == 'titlefield' and $form_element[0]['display']) {
            $html .= '<div style="padding-bottom:0px; ">';
            $html .= '<table summary="Layout">';
            $html .= '<tr>';
            $html .= '<td style="padding:0px;">';
            if (isset($form_element_array[0][0]['label'])) {
               if (isset($form_element_array[0][0]['failure'])) {
                  $label = '<span class="required">'.$form_element_array[0][0]['label'].'</span>';
               } else {
                  $label = $form_element_array[0][0]['label'];
               }
               $html .= '<span class="key">'.$label.'</span>';
               if ( !empty($label) ) {
                  $html .= ':';
               }
               if (!empty($form_element_array[0][0]['mandatory'])) {
                  $html .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               }
            }
            $html .= '</td>';
            $html .= '<td style="padding:0px;">';
            $html .= '&nbsp;'.$this->_getTitleFieldAsHTML($form_element[0]);
            $show_title_field = true;
            if ($form_element[1]['type'] == 'checkbox') {
               $html .= '</td>';
               $html .= '</tr>';
               $html .= '<tr>';
               $html .= '<td style="padding:0px;">';
               $html .= '</td>';
               $html .= '<td style="padding:0px;">';
               $html .= '         '.$this->_getCheckboxAsHTML($form_element[1])."\n";
               $html .= '</td>';
               $html .= '</tr>';
            } elseif ($form_element[1]['type'] == 'textfield') {
               $form_element[1]['display']=true;
               $html .= '         '.$this->_getTitleFieldAsHTML($form_element[1])."\n";
               $html .= '</td>';
               $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '</div>';
         } elseif ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and !$form_element['display']) {
            $html .= $this->_getTitleFieldAsHTML($form_element);
            $show_title_field = true;
         } else {
            $temp_array[] = $form_element;
         }
      }

      $form_element_array = $temp_array;

      $html .= '<table id="form" summary="layout">'.LF;
      $form_element = $this->_form_elements->getFirst();
      $html .= '<tr>'.LF;
      $html .= '<td style="border:0px; padding:0px;" colspan="4">'.LF;
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $temp_array = array();
      // now get the html code
      $first = true;
      $second = false;
      $temp_array = $form_element_array;
      $i=0;
      $without_description=0;
      foreach ($form_element_array as $form_element) {
         if (!isset($form_element[0]['type']) and $form_element['type'] == 'headline') {
            $headline_right = $this->_getHeadLineAsHTML($form_element,$form_element['size']);
         } else {
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')) {
               if ( isset($form_element['type']) and $form_element['type'] == 'textarea' ) {
                  $text = '   <tr class="textarea">'.LF;
               } elseif ( isset($form_element['type']) and $form_element['type'] == 'radio' ) {
                  $text = '   <tr class="radio">'.LF;
               } elseif ( isset($form_element['type']) and $form_element['type'] == 'checkboxgroup' ) {
                  $text = '   <tr class="checkboxgroup">'.LF;
               } else {
                  $text = '   <tr>'."\n";
               }
            }
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')
               and !(isset($form_element[0]['name']) and $form_element[0]['name'] == 'buzzwordlist')
               and !(isset($form_element[0]['name']) and $form_element[0]['name'] == 'buzzword')
               and !(isset($form_element[0]['name']) and $form_element[0]['name'] == 'taglist')
               and (!isset($form_element['type']) or $form_element['type'] != 'titlefield')
               and !(isset($form_element[0]['name']) and $form_element[0]['name'] == 'tag')
            ) {
               $html .= $text.$this->_getFormElementAsHTML($form_element).'   </tr>'.LF;
            }
         }
         $i++;
      }
      $html .= '</table>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both; width:100%;">&nbsp;'.LF;
      $html .='</div>'.LF;
      if (isset($buttonbartext) and !empty($buttonbartext) and $this->_environment->getCurrentModule() !='buzzwords' and $this->_environment->getCurrentModule() !='labels'){
         $html .= '<div style="width: 100%; clear:both;">'.LF;
         $html .= '<table style="width: 100%; border-collapse:collapse;">'.LF;
         $html .= '<tr>'.LF;
         if (!$this->_display_plain) {
            if ($this->_special_color) {
               $html .='      <td colspan="2">';
            } else {
               if ($this->_warn_changer) {
                  $html .='      <td colspan="2" style="background-color:#FF0000;">';
               } else {
                  $html .='      <td colspan="2" class="buttonbar">';
               }
            }
         } else {
            if ($this->_special_color) {
               $html .='      <td colspan="2" style="border-bottom: none; xwhite-space:nowrap;">';
            } else {
               $html .='      <td colspan="2" style="border-bottom: none; xwhite-space:nowrap;">';
            }
         }
         $html .= '<span class="required" style="font-size:16pt;">*</span> <span class="key" style="font-weight:normal;">'.getMessage('COMMON_MANDATORY_FIELDS').'</span> '.$buttonbartext;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</form>'.BRLF;
      $html .= '</div>'.BRLF;
      return $html;
   }



}
?>