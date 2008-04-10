<?PHP
// $Id:
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

include_once('classes/cs_form_view.php');
include_once('functions/curl_functions.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy news detail-views
 */
class cs_labels_form_view extends cs_form_view {


   /** constructor: cs_form_view
    * the only available constructor, initial values for internal variables
    *
    * @author CommSy Development Group
    */
   function cs_labels_form_view ($environment, $with_modifying_actions = true) {
      $this->cs_form_view($environment, $with_modifying_actions);
      $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 'material',
                                 'index',
                                 '',
                                 $this->_translator->getMessage('COMMON_BACK'));
      $this->addAction($anAction);
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
      if (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'buttonbar') {
          $html .='      <td class="key" style="border-bottom: none; width: 1%">';
      }else{
          $html .='      <td class="key" style="width: 1%">';
      }
      if (isset($form_element_array[0]['label'])) {
        $html .= $form_element_array[0]['label'];
      }
      $html .= '</td>'."\n";

      if (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'buttonbar') {
         $html .='      <td style="border-bottom: none; width: 100%">';

      } else {
         $html .= '      <td style="width: 100%">'."\n";
      }
      $first = true;
      foreach ($form_element_array as $form_element) {
         if ($first) {
            $first = false;
         } else {
            $html .= '<!-- COMBINED FIELDS -->'."\n";
         }
         if ($form_element['type'] == 'textfield') {
            $html .= '         '.$this->_getTextFieldAsHTML($form_element);
         } elseif ($form_element['type'] == 'select') {
            $html .= '         '.$this->_getSelectAsHTML($form_element);
         } elseif ($form_element['type'] == 'buttonbar') {
            $html .= $this->_getButtonBarAsHTML($form_element);
         } elseif ($form_element['type'] == 'button') {
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name']);
         } elseif ($form_element['type'] == 'text') {
            $html .= '         '.$this->_getTextAsHTML($form_element);
         }
      }
      $html .= '      </td>'."\n";

      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n";
      return $html;
   }


}
?>