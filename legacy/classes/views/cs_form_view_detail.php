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
class cs_form_view_detail extends cs_form_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_form_view::__construct($params);
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
      if (isset($form_element_array[0]['text-align']) and $form_element_array[0]['text-align'] == 'right') {
         $html .= '   <div class="form_view_detail_formelement_right">'.LF;
      } else {
         $html .= '   <div class="form_view_detail_formelement">'.LF;
      }
      if (!empty($form_element_array[0]['label'])) {
         if ( isset($form_element_array[0]['failure']) ) {
            $label = '<span class="bold">'.$form_element_array[0]['label'].'</span>';
         } else {
            $label = $form_element_array[0]['label'];
         }
         if ( $form_element_array[0]['type'] == 'titlefield' ) {
            $label = '<span class="titlefield">'.$label.'</span>';
         }
         
         if(   $this->_environment->getCurrentModule() == 'discussion' &&
               $this->_environment->getCurrentFunction() == 'detail' &&
               isset($form_element_array[0]['type']) &&
               $form_element_array[0]['type'] == 'checkboxgroup') {
            $html .= '<table>';
            $html .= '<tr>';
            $html .= '      <td class="key" style="width:10%; vertical-align:baseline; ">';
            $html .= $label . ':';
            $html .= '</td>';
            $html .= '<td>';
         } else {
            $html .= $label;
         }
         
         if (!empty($form_element_array[0]['mandatory'])) {
            if ( $label[strlen($label)-1] == ':' ) {
               $red_star = '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               $html = str_replace($label,substr($label,0,strlen($label)-1).$red_star.':',$html);
            } else {
               $html.= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>'."\n";
            }
         }
      }

      // form element
      $counter = 0;
      $first = true;
      foreach ($form_element_array as $form_element) {
         $counter++;
         if ($first) {
            $first = false;
         }

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
         } elseif ($form_element['type'] == 'anchor') {
            $html .= $this->_getAnchorAsHTML($form_element);
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
      }

      if ( !empty($form_element_array[0]['example'])
           and count($form_element_array)
           and count($form_element_array) == $counter
         ) {
         $html .= '<br />'.LF;
         $html .= $form_element_array[0]['example'];
      }
      if(   $this->_environment->getCurrentModule() == 'discussion' &&
            $this->_environment->getCurrentFunction() == 'detail' &&
            isset($form_element_array[0]['type']) &&
            $form_element_array[0]['type'] == 'checkboxgroup') {
         $html .= '</td>';
         $html .= '</tr>';
         $html .= '</table>';
      }
      $html .= '</div>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'.LF.LF;
      return $html;
   }

   /** get buttonbar as HTML - internal, do not use
    * this method returns a string containing a buttonbar (save, cancel and delete) in HMTL-Code
    *
    * @param array value form element: buttonbar, see class cs_form
    *
    * @return string buttonbar as HMTL
    */
   function _getButtonBarAsHTML ($form_element) {
      trigger_error('this method is not implemented yet', E_USER_ERROR);
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
      $html .= '<!-- BEGIN OF FORM-VIEW DETAIL -->'.LF;
      $html .= '<form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'.LF;
      $html .= '<table class="form_view_detail" summary="Layout">'.LF;
      $html .= '   <tr>'.LF;
      $html .= '      <td class="form_view_detail_left">'.LF;
      $html .= '      '.$this->_getItemPictureAsHTML($this->_environment->getCurrentUserItem());
      $html .= '      </td>'.LF;
      $html .= '      <td class="form_view_detail">'.LF;

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
            $html .= $this->_getFormElementAsHTML($form_element);
         }
      }

      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '</table>'.LF;
      $html .= '</form>'.LF;
      $html .= '<!-- END OF FORM-VIEW DETAIL -->'.LF.LF;

      return $html;
   }

   /**
    * fomr cs_detail_view.php
    */
   private function _getItemPictureAsHTML ( $item ) {
      $picture_url = '';
      if ( method_exists($item,'getPictureUrl') ) {
         $picture_url = $item->getPictureUrl();
      }
      $picture = $item->getPicture();
      $linktext = '';
      if ( !empty($picture) ) {
         $disc_manager = $this->_environment->getDiscManager();
         if ($disc_manager->existsFile($picture)){
            $image_array = getimagesize($disc_manager->getFilePath().$picture);
            $pict_height = $image_array[1];
            if ($pict_height > 60){
               $height = 60;
            }else{
               $height = $pict_height;
            }
         }else{
            $height = 60;
         }

         if ( empty($picture_url) ) {
            $params = array();
            $params['picture'] = $picture;
            $curl = curl($this->_environment->getCurrentContextID(),
                         'picture', 'getfile', $params,'');
            unset($params);
         } else {
            $curl = $picture_url;
         }
         $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="'.$curl.'" style="vertical-align:middle; width: '.$height.'px;"/>'.LF;
         if ($item->isA(CS_USER_TYPE)) {
            $linktext = str_replace('"','&quot;',encode(AS_HTML_SHORT,$item->getFullName()));
         } else {
            $linktext = $this->_translator->getMessage('USER_PICTURE_UPLOADFILE');
         }
      }else{
         $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="images/commsyicons/common/user_unknown.gif" style="vertical-align:middle;  width: 60px;"/>'.LF;
         if ($item->isA(CS_USER_TYPE)) {
            $linktext = $this->_translator->getMessage('USER_PICTURE_NO_PICTURE',str_replace('"','&quot;',encode(AS_HTML_SHORT,$item->getFullName())));
         } else {
            $linktext = $this->_translator->getMessage('USER_PICTURE_UPLOADFILE');
         }
      }
      $params = array();
      $params['iid'] = $item->getItemID();
      $html = ahref_curl(	$this->_environment->getCurrentContextID(),
      						CS_USER_TYPE,
      						'detail',
      						$params,
      						$html,
      						$linktext,'', '', '', '', '', '', '', '');
      return $html;
   }
}
?>