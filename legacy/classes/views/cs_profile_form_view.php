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

   private $_language = NULL;

   function __construct($params) {
      cs_form_view::__construct($params);
      $this->_language = $this->_environment->getSelectedLanguage();
   }

   public function setLanguage ( $value ) {
      $this->_language = (string)$value;
   }

   function _getLinkRowAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div id="profile_tabs_frame" >'.LF;
      $html .= '<div id="profile_tablist">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['is_saved']);
      if (!isset($_GET['show_no_account'])  or empty($_GET['show_no_account'])){
		  $params['profile_page'] = 'account';
		  $title = ahref_curl( $this->_environment->getCurrentContextID(),
							   $this->_environment->getCurrentModule(),
							   $this->_environment->getCurrentFunction(),
							   $params,
							   $this->_translator->getMessageInLang($this->_language,'PROFILE_ACCOUNT_DATA'));
		  if (!isset($_GET['profile_page']) or $_GET['profile_page'] == 'account'){
			 $html .= '<div class="profile_tab_current">'.$title.'</div>'.LF;
				  }else{
			 $html .= '<div class="profile_tab">'.$title.'</div>'.LF;
		  }
	  }
      $params['profile_page'] = 'user';
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           $this->_translator->getMessageInLang($this->_language,'PROFILE_USER_DATA'));
      if (isset($_GET['profile_page']) and $_GET['profile_page'] == 'user'){
         $html .= '<div class="profile_tab_current">'.$title.'</div>'.LF;
      }else{
         $html .= '<div class="profile_tab">'.$title.'</div>'.LF;
      }
      $current_user_item = $this->_environment->getCurrentUserItem();
      $params['profile_page'] = 'newsletter';
      $private_room = $current_user_item->getOwnRoom();
      if ( !isset( $private_room )
           or (
                !$private_room->isPrivateRoomNewsletterActive()
                and !$current_user_item->isRoomMember()
              )
         ) {
         $title = '<a title="'.$this->_translator->getMessageInLang($this->_language,'COMMON_NO_ACTION').'" class="disabled">'.$this->_translator->getMessageInLang($this->_language,'PROFILE_NEWSLETTER_DATA').'</a>';
      } else {
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              $this->_environment->getCurrentFunction(),
                              $params,
                              $this->_translator->getMessageInLang($this->_language,'PROFILE_NEWSLETTER_DATA'));
      }
      unset($private_room);
      if (isset($_GET['profile_page']) and $_GET['profile_page'] == 'newsletter'){
         $html .= '<div class="profile_tab_current">'.$title.'</div>'.LF;
      }else{
         $html .= '<div class="profile_tab">'.$title.'</div>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;

      unset($current_user_item);
      return $html;
   }



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
      unset($params['is_saved']);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .='<div>'.LF;
      $html .= '<div class="profile_title" style="float:right">'.$title.'</div>';
      $html .= '<h2 id="profile_title">'.$this->_translator->getMessageInLang($this->_language,'COMMON_PROFILE_EDIT').'</h2>';
      $html .='</div>'.LF;

      $html .= $this->_getLinkRowAsHTML();

      $html .= '<form style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .='<div style="width:95%; padding:10px;">'.LF;
      if ($this->_item_saved){
         $html .='<div style="width:100%; text-align:center; font-weight:bold; color:red; font-size:14pt;">'.LF;
         $html .= $this->_translator->getMessageInLang($this->_language,'COMMON_ITEM_SAVED').LF;
         $html .='</div>'.LF;
      }

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
      $buttonbar_counter = 0;
      $buttonbar_counter2 = 0;
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'buttonbar' ) {
            $buttonbar_counter++;
         }
      }
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'buttonbar' ) {
            $buttonbar_counter2++;
            $this->_count_form_elements = $this->_count_form_elements + 100;
            $buttonbartext = $this->_getButtonBarAsHTML($form_element);
            $this->_count_form_elements = $this->_count_form_elements - 100;
            if ( $buttonbar_counter > 1
                 and $buttonbar_counter != $buttonbar_counter2
               ) {
               $temp_array[] = $form_element;
            }
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
                  $html .= '<span class="required">'.$this->_translator->getMessageInLang($this->_language,'MARK').'</span>';
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
                  $html .= '<span class="required">'.$this->_translator->getMessageInLang($this->_language,'MARK').'</span>';
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

      $html .= '<table summary="layout">'.LF;
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
            if ( isset($form_element['type'])
                 and $form_element['type'] == 'buttonbar'
               ) {
               $html .= '<tr>'.LF;
               $html .='      <td>';
               $html .='      </td>';
               if (!$this->_display_plain) {
                  if ($this->_special_color) {
                     $html .='      <td>';
                  } else {
                     if ($this->_warn_changer) {
                        $html .='      <td style="background-color:#FF0000;">';
                     } else {
                        $html .='      <td class="buttonbar">';
                     }
                  }
               } else {
                  if ($this->_special_color) {
                     $html .='      <td style="border-bottom: none; xwhite-space:nowrap;">';
                  } else {
                     $html .='      <td style="border-bottom: none; xwhite-space:nowrap;">';
                  }
               }
               $html .= $this->_getButtonBarAsHTML($form_element);
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;
            } elseif ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')
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
      if (isset($buttonbartext) and !empty($buttonbartext) and $this->_environment->getCurrentModule() !='buzzwords' and $this->_environment->getCurrentModule() !='labels'){
         $html .= '<tr>'.LF;
         $html .='      <td>';
         $html .='      </td>';
         if (!$this->_display_plain) {
            if ($this->_special_color) {
               $html .='      <td>';
            } else {
               if ($this->_warn_changer) {
                  $html .='      <td style="background-color:#FF0000;">';
               } else {
                  $html .='      <td class="buttonbar">';
               }
            }
         } else {
            if ($this->_special_color) {
               $html .='      <td style="border-bottom: none; xwhite-space:nowrap;">';
            } else {
               $html .='      <td style="border-bottom: none; xwhite-space:nowrap;">';
            }
         }
         $html .= $buttonbartext;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
      }
      $html .= '</table>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both; width:100%;">&nbsp;'.LF;
      $html .='</div>'.LF;
      $html .= '</form>'.BRLF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _getCheckboxGroupAsHTML ($form_element) {
      $html  = '';
      if (!empty($form_element['anchor'])){
         $html='<a name="'.$form_element['anchor'].'"></a>';
      }
      $options = $form_element['value'];
      $option = reset($options);
      if (!empty($form_element['columns'])) {
         $html .= '<table summary="Layout" style="font-size:'.$form_element['font_size'].'pt;">'."\n";
         $num_of_options = count($options);
         $width = floor(100/$form_element['columns']);
         $num_of_column = 1;
         if ($form_element['horizontal']) {
            while ($option) {
               if ( !empty($form_element['chunk_text']) and $form_element['chunk_text'] > 1 ) {
       $option['chunk_text'] = $form_element['chunk_text'];
         }
               if ( !empty($form_element['no_html_decode']) and $form_element['no_html_decode'] ) {
       $option['no_html_decode'] = $form_element['no_html_decode'];
         }
               if ($num_of_column == 1) {
                  $html .= '<tr>'."\n";
               }
               $option['name'] = $form_element['name'].'[]';
               if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                  $option['ischecked'] = true;
               } else {
                  $option['ischecked'] = false;
               }

               $html .= '<td style="font-size:'.$form_element['font_size'].'pt; padding-left: 0px; padding-top: 0px; padding-bottom: 0px; padding-right: 10px; border-bottom: none;">'.$this->_getCheckboxAsHTML($option,$form_element['font_size']).'</td>'."\n";
               if ($num_of_column == $form_element['columns']) {
                  $html .= '</tr>'."\n";
                  $num_of_column = 0;
               }
               $num_of_column++;
               $option = next($options);
            }
         } else {
            $maximum = ceil($num_of_options/$form_element['columns']);
            $num_of_column = 1;
            for ($i=0; $i<$maximum; $i++) {
               if ($num_of_column == 1) {
                  $html .= '<tr>'.LF;
               }
               for ($j=0; $j<$form_element['columns'];$j++) {
                  $id = $i + ($j*$maximum);
                  if ($id<count($options)) {
                     $option = $options[$id];
                     if ( !empty($form_element['chunk_text']) and $form_element['chunk_text'] > 1 ) {
             $option['chunk_text'] = $form_element['chunk_text'];
               }
                     if ( !empty($form_element['no_html_decode']) and $form_element['no_html_decode'] ) {
             $option['no_html_decode'] = $form_element['no_html_decode'];
               }
                     $option['name'] = $form_element['name'].'[]';
                     if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                        $option['ischecked'] = true;
                     } else {
                        $option['ischecked'] = false;
                     }
                     $html .= '<td style="font-size:'.$form_element['font_size'].'pt; padding-left: 0px; padding-top: 0px; padding-bottom: 0px; padding-right: 10px; border-bottom: none;">'.$this->_getCheckboxAsHTML($option,$form_element['font_size']).'</td>'."\n";
                  }
               }
               if ($num_of_column == $form_element['columns'] or $i+1 == $maximum) {
                  $html .= '</tr>'.LF;
                  $num_of_column = 0;
               }
            }
         }
         $html .= '</table>'.LF;
      } else {
         $counter = 1;
         if (isset($form_element['with_dhtml']) and $form_element['with_dhtml']){
            $html .= '<ul id="MySortableRoom" unselectable="on" style="padding-top:0px; margin-top:0px;">'.LF;
         }
         while ($option) {
            $option['name'] = $form_element['name'].'[]';
            if ( !empty($form_element['chunk_text']) and $form_element['chunk_text'] > 1 ) {
               $option['chunk_text'] = $form_element['chunk_text'];
            }
            if ( !empty($form_element['no_html_decode']) and $form_element['no_html_decode'] ) {
               $option['no_html_decode'] = $form_element['no_html_decode'];
            }
            if (!isset($form_element['with_dhtml']) or !$form_element['with_dhtml']){
               if ( !empty($form_element['up_and_down']) and $form_element['up_and_down'] ) {
                  $option['up_and_down'] = $form_element['up_and_down'];
                  $option['counter'] = $counter;
                  if ( $counter == 1 ) {
                     $option['up_and_down_position'] = 'first';
                  } elseif ( $counter == count($options) ) {
                     $option['up_and_down_position'] = 'last';
                  }
                  if ( $counter == 1 and $counter == count($options) ) {
                     $option['up_and_down_position'] = 'first and last';
                  }
               }
               if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                  $option['ischecked'] = true;
               } else {
                  $option['ischecked'] = false;
               }
               if (isset($form_element['font_size'])){
                  $html .= '         '.$this->_getCheckboxAsHTML($option,$form_element['font_size']);
               }else{
                  $html .= '         '.$this->_getCheckboxAsHTML($option,10);
               }
               if (!$form_element['horizontal'] and !empty($option)) {
                  $html .= '<br />';
               }
               $html .= "\n";
            }else{
               $html .='<li class="form_checkbox_dhtml">'.LF;
               if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                  $option['ischecked'] = true;
               } else {
                  $option['ischecked'] = false;
               }
               $html .= '         '.$this->_getCheckboxAsHTML($option,10);
               $html .='</li>'.LF;
            }
            $option = next($options);
            $counter++;
         }
         if (isset($form_element['with_dhtml']) and $form_element['with_dhtml']){
            $html .= '</ul>'.LF;
            $html .= '<script type="text/javascript">'.LF;
         $html .= 'jQuery(document).ready(function(){jQuery(\'#MySortableRoom\').sortable();$("#MySortableRoom").disableSelection();});';
//            $html .='var MySortables = Sortables.extend({
//                        start: function(event, element) {
//                           if (event.target.tagName != \'A\'
//                               && event.target.tagName != \'INPUT\'
//                               && event.target.tagName != \'SELECT\'
//                               && event.target.tagName != \'TEXTAREA\'
//                           ) {
//                              this.parent(event, element);
//                           }
//                        }
//                     });
//                     window.addEvent(\'domready\', function(){
//                        new MySortables($(\'MySortable\'), {
//                           initialize: function(){
//                              var step = 0;
//                              this.elements.each(function(element, i){
//                              element.setStyle(\'width\', \'400px\');
//                           });
//                        },
//                        onDragStart: function(element, ghost){
//                           ghost.setStyle(\'width\', \'395px\');
//                           ghost.setStyle(\'list-style\', \'none\');
//                        }
//                        });
//                     });'.'</script>';
            $html .= '</script>';
         }

      }
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '         &nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
      return $html;
   }
}
?>