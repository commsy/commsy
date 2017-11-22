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
$this->includeClass(CONFIGURATION_FORM_VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_configuration_rubric_form_view extends cs_configuration_form_view {


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_configuration_form_view::__construct($params);
   }


   /** get selectbox as HTML - internal, do not use
    * this method returns a string contains an selectbox in HMTL-Code
    *
    * @param array value form element: selectbox, see class cs_form
    *
    * @return string selectbox as HMTL
    *
    * @author CommSy Development Group
    */
   function _getSelectAsHTML ($form_element) {
      if ($form_element['multiple']) {
         $form_element['name'] .= '[]';
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $color = $context_item->getColorArray();
      $bgcolor = $color['tabs_background'];
      $color = $color['tabs_title'];
      $html  = '';
      $html .= BRLF;
      if (!empty($form_element['descriptionText'])){
#         $html .='<table><tr><td style="border-bottom: none;" summary="Layout">';
#         $html .= $this->_text_as_html_short_coding_format($form_element['descriptionText']).'';
#         $html .= '&nbsp;'.$this->_translator->getMessage('SECTION_CHOOSE_POSITION');
      }
      $html .= '<select name="'.$form_element['name'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      if ($form_element['multiple']) {
         $html .= ' multiple';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $width = '12em';
      if ($this->_environment->inCommunityRoom()){
      $width = '12em';
     }elseif ($this->_environment->inPrivateRoom()){
      $width = '12em';
     }
     $html.=' style="width:'.$width.'; font-size: 10pt; background-color:'.$bgcolor.'; color:'.$color.';font-weight:bold;"';

      // jQuery
      //$html .= $form_element['event'] ? " onChange='javascript:document.f.submit()'" : '';
      $html .= $form_element['event'] ? " id='submit_form'" : '';
      // jQuery
      $html .= '>'.LF;
      $options = $form_element['options'];
      $option = reset($options);
      while ($option) {
         if (!isset($option['value'])) {
            $option['value'] = $option['text'];
         }
         $html .= '            <option';
         if (isset($option['value']) and $option['value'] == 'disabled') {
            $html .= ' disabled="disabled"';
         } else {
            $html .= ' value="'.$this->_text_as_form($option['value']).'"';
         }
         if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
            $html .= ' selected';
         }
         $html .= '>';
         $html .= $this->_text_as_html_short($option['text']);
         $html .= '</option>'."\n";
         $option = next($options);
      }
      $html .= '         </select>'.BRLF;
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
      if (!empty($form_element['descriptionText'])){
#         $html .='</td></tr></table>';
      }
      return $html;
   }


   /** get form element as HTML and in commsy-style- internal, do not use
    * this method returns a string contains a form element in commsy-style in HMTL-Code
    *
    * @param array value form element: form element, see class cs_form
    *
    * @return string form element in commsy-style as HMTL
    *
    * @author CommSy Development Group
    */
   function _getFormElementAsHTML ($form_element, $without_description=false) {
      if ( !isset($form_element[0]['type']) or $form_element[0]['type'] != 'select' ){
         return parent:: _getFormElementAsHTML ($form_element, $without_description=false);
     }else{
      // prepare form element array for combined form elements
      $form_element_array = array();
      $description_span=0;
      if (isset($form_element['description_span'])){
         $description_span = $form_element['description_span'];
      }
      if (!isset($form_element[0]['type'])) {
         $form_element_array[] = $form_element;
      } else {
         $form_element_array = $form_element;
      }
      // html code
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n";

      $html .= '      <td colspan="3" class="formfield" style="border-bottom:none;">';
      $first = true;
      $context_item = $this->_environment->getCurrentContextItem();
      $context_color = $context_item->getColorArray();
      $bgcolor = $context_color['tabs_focus'];
      $color = $context_color['tabs_title'];
      foreach ($form_element_array as $form_element) {
         if ($first) {
            if ($form_element['type'] == 'select') {
            $html .= '<div style="border-bottom: 1px solid black; margin-bottom:10px; font-weight:bold;"> <div style="background-color:'.$bgcolor.'; color:'.$color.'; padding-top:3px; padding-bottom:1px; padding-left: 4px;padding-right: 4px; font-size:10pt;">'.$this->_translator->getMessage('HOME_INDEX').'</div>'.LF;
            }else{
            $html .= '<div style="padding-left:10em; font-weight:bold;">';
         }
          $first = false;
         } else {
            $html .= '<!-- COMBINED FIELDS -->'."\n";
         }
         if ($form_element['type'] == 'select') {
            $html .= '         '.$this->_getSelectAsHTML($form_element);
         } elseif ($form_element['type'] == 'textline') {
         #   $html .= '     hallo    '.$this->_getTextLineAsHTML($form_element).BRLF;
         }

      }
      $html .= '      </div></td>'."\n";
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n";
      return $html;
      }
   }

}
?>