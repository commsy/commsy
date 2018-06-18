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
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_form_view extends cs_view {

   /**
    * string - containing the URL where data will post to
    */
   var $_action = NULL;

   var $_rubric_connections = array();

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


   var $_item = NULL;

  /**
   * boolean - adds header infos if true
   */
   var $_with_javascript = true;
   var $_with_anchor = false;

  /**
   * string - contains which element (if any) is to be focused upon loading
   */
   var $_focus_element_onload ='';

  /**
   * string - contains which anchor (if any) is to be focused upon loading
   */
   var $_focus_element_anchor ='';

   var $_display_plain = false;

   var $_special_color = false;

   /**
   * boolean - flag: switch warning when moderators may violate copyright when editing
   *           because of special rights (admin etc)
   */
   var $_warn_changer = false;

   /**
    * array - containing the actions of the form view
    */
   var $_actions = NULL;

   var $_action_title = '';

  /**
   * string - adds description infos if true
   */
   var $_description_text ='';

  /**
   * boolean - with description or without
   * default = true
   */
   var $_with_description = true;

   var $_display_title = true;

   var $_item_saved = false;

   var $_with_form_title = false;

   /** constructor: cs_form_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
         $first = array();
         $secon = array();
         foreach ( $room_modules as $module ) {
            $link_name = explode('_', $module);
            if ( $link_name[1] != 'none'
                 and $context_item->withRubric($link_name[0])
                 and $link_name[0] != CS_USER_TYPE
                 and $link_name[0] != CS_MYROOM_TYPE
               ) {
               $rubric_connections[] = $link_name[0];
            }
         }
         if ($this->_environment->getCurrentModule() != CS_DISCARTICLE_TYPE and $this->_environment->getCurrentModule() != CS_SECTION_TYPE){
            $this->_rubric_connections = $rubric_connections;
         }
      }
    }

   function setItemIsSaved(){
      $this->_item_saved = true;
   }

   /** set URL of the form view
    * this method sets the URL where the data will post to
    *
    * @param string value <form action="URL">
    *
    * @author CommSy Development Group
    */
   function setAction ($value) {
      $this->_action = (string)$value;
   }

   /** set action type of the form view
    * this method sets the action type
    *
    * @param string value <form ... method="action_type">
    *
    * @author CommSy Development Group
    */
   function setActionType ($value) {
      $this->_action_type = (string)$value;
   }

   /**
    * Set an array of connected rubrics to be shown in the network
    * navigation area on the right side. Set for the main item and
    * subitems seperately.
    */
   function setRubricConnections ($rc) {
   #   $this->_rubric_connections = $rc;
   }

   function getRubricConnections () {
      return $this->_rubric_connections;
   }


   function setItem ($value) {
      $this->_item = $value;
   }

    /** set the actions of the form
    * this method sets the actions of the list
    *
    * @param array  $this->_action_array
    *
    * @author CommSy Development Group
    */
    function addAction($action){
       $this->_actions[] = $action;
    }

   /** set form elements of the form view
    * this method sets the form elements as an array for the form view
    *
    * @param array value form elements
    *
    * @author CommSy Development Group
    */
   function setFormElements ($value) {
      $this->_form_elements = (object)$value;
   }

   /** set form form the form view
    * this method sets the form for the form view
    *
    * @param array value form elements
    */
   function setDescription ($description_text) {
      $this->_description_text = $description_text;
   }

   /** set color the form view
    * this method sets the color the form view
    *
    */
   function setSpecialColor () {
      $this->_special_color = true;
   }

   /** set display of warning message to true
   *
   * switch warning when moderators may violate copyright when editing
   * because of special rights (admin etc)
   */
   function warnChanger() {
      $this->_warn_changer = true;
   }

   /** set color the form view
    * this method sets the color the form view
    *
    */
   function unsetSpecialColor () {
      $this->_special_color = false;
   }

   /** set form form the form view
    * this method sets the form for the form view
    *
    * @param array value form elements
    *
    * @author CommSy Development Group
    */
   function setForm ($value) {
      $this->_form = $value;
      $this->_form_elements = $this->_form->getFormElements();
      $this->_error_array = $this->_form->getErrorArray();
   }

   function setDisplayToPlain () {
      $this->_display_plain = true;
   }

   /** get headline as HTML - internal, do not use
    * this method returns a string contains a headline in HMTL-Code
    *
    * @param array value form element: headline, see class cs_form
    *
    * @return string headline as HMTL
    */
   function _getHeadLineAsHTML ($form_element,$size=2) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: headline -->'.LF;
      $style = '';
      if ( $form_element['type'] == 'subheadline' ) {
         $style = ' style="margin: 0px; padding: 0px;"';
      }
      $colspan = '';
      if ( $this->_with_description ) {
         $colspan = ' colspan="2"';
      }
      $html .= '       <h'.$size.$style.'>'.$form_element['label'].'</h'.$size.'>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: headline -->'.LF;
      return $html;
   }

   function _getTitleTextAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: headline -->'.LF;
      $html .= '<h2 class="pagetitle">'.$form_element['label'].'</h2>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: headline -->'.LF;
      return $html;
   }

   function _getAnchorAsHTML ($form_element) {
      $html  = ''.LF;
      $html .= '         <!-- BEGIN OF FORM-ELEMENT: anchor -->'.LF;
      $html .= '         <a name="'.$form_element['name'].'" />'.LF;
      $html .= '         <!-- END OF FORM-ELEMENT: anchor -->'.LF;
      return $html;
   }

   /** get textline as HTML - internal, do not use
    * this method returns a string contains a textline in HMTL-Code
    *
    * @param array value form element: textline, see class cs_form
    *
    * @return string textline as HMTL
    *
    * @author CommSy Development Group
    */
   function _getTextLineAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: textline -->'."\n";
      $html .= '   <td colspan="3" style="border-bottom: none;">'."\n";
      $html .= '      '.$form_element['description']."\n";
      $html .= '   </td>'."\n";
      $html .= '<!-- END OF FORM-ELEMENT: textline -->'."\n";
      return $html;
   }


   /** get textline as HTML - internal, do not use
    * this method returns a string contains a textline in HMTL-Code
    *
    * @param array value form element: textline, see class cs_form
    *
    * @return string textline as HMTL
    *
    * @author CommSy Development Group
    */
   function _getLineAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: textline -->'."\n";
      $html .= '   <td colspan="3" style="border-bottom: none;">'."\n";
      $html .= '      '.$form_element['description']."\n";
      $html .= '   </td>'."\n";
      $html .= '<!-- END OF FORM-ELEMENT: textline -->'."\n";
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
      if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
         $html .= ' <div '.$form_element['extention'].'>';
      }
      if (!empty($form_element['anchor'])){
        $html .='<a name="'.$form_element['anchor'].'"></a>';
      }
      if (!empty($form_element['value'])) {
         if ($form_element['isbold']) {
            $html .= '<b>'.$this->_text_as_form2($form_element['value']).'<b>';
         } else {
            $html .= $this->_text_as_form2($form_element['value']);
         }
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '         &nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'',false).LF;
         }
         if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
            $html .= ' </div >';
         }else{
            $html .= BRLF;
         }
      }
      return $html;
   }

   /** get button as HTML - internal, do not use
    * this method returns a string contains a button in HMTL-Code
    *
    * @param array value form element: button, see class cs_form
    *
    * @return string button as HMTL
    */
   function _getButtonAsHTML ($button_text, $button_name, $width = '', $is_disabled = false, $style='', $font_size='10', $text_after='',$javascript ='') {
      $html  = '';
      $html .= '<input type="submit" name="'.$button_name.'"';
      $html .= ' value="'.$button_text.'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'" ';
      $this->_count_form_elements++;
      if ( empty($font_size) ) {
         $font_size = 10;
      }
      if ( !empty($width) ){
         $button_width = $width/13;
         $html .= 'style="width:'.$button_width.'em; font-size:'.$font_size.'pt;"';
      } else {
         $html .= 'style="font-size:'.$font_size.'pt;"';
      }
      if ( $is_disabled ){
         $html .= ' disabled="disabled"';
      }
      if ( !empty($javascript) ){
         $html .= $javascript;
      }
      $html .= '/>'.LF;
      if ( isset($text_after) and !empty($text_after) ) {
         $html .= '&nbsp;'.$text_after.LF;
      }

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
      $html  = '';
      $style='';
      if (!empty($form_element['style'])){
         $style = $form_element['style'];
      }
      if (!empty($form_element['labelSave'])) {
         $html .= $this->_getButtonAsHTML($form_element['labelSave'],$form_element['name'],'',$form_element['is_disabled'],$style,'','',$form_element['javascript'])."\n";
      }
      if (!empty($form_element['labelSecondSave'])) {
         $html .= $this->_getButtonAsHTML($form_element['labelSecondSave'],$form_element['name'],'',$form_element['is_disabled'],$style)."\n";
      }
      if (!empty($form_element['labelCancel'])) {
         $html .= $this->_getButtonAsHTML($form_element['labelCancel'],$form_element['name'],'',$form_element['is_disabled'],$style)."\n";
      }

      $current_user = $this->_environment->getCurrentUser();
      if ( $current_user->isAutoSaveOn()
           and $this->_environment->getCurrentFunction() == 'edit'
           and ( $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                 or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                 or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                 or $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                 or $this->_environment->getCurrentModule() == CS_SECTION_TYPE
                 or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                 or $this->_environment->getCurrentModule() == CS_DISCARTICLE_TYPE
                 or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
                 or $this->_environment->getCurrentModule() == CS_INSTITUTION_TYPE
                 or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
                 or $this->_environment->getCurrentModule() == CS_ANNOTATION_TYPE
               )
         ) {
         $html .= '<span class="formcounter">'.LF;

         global $symfonyContainer;

         $c_autosave_mode = $symfonyContainer->getParameter('commsy.autosave.mode');
         $c_autosave_limit = $symfonyContainer->getParameter('commsy.autosave.limit');

         if ( $c_autosave_mode == 1 ) {
            $currTime = time();
            $sessEnds = $currTime + ($c_autosave_limit * 60);
            $sessEnds = date("H:i", $sessEnds);
            $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' '.$sessEnds.LF;
         } elseif ( $c_autosave_mode == 2 ) {
            $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' <input type="text" size="5" name="timerField" value="..." class="formcounterfield" />'.LF;
         }
         $html .= '</span>'.LF;
      }

      if (!empty($form_element['labelDelete'])) {
         $html .= '</td>'.LF;
         if (!$this->_display_plain) {
            if ($this->_special_color) {
               $html .= '                <td  style="border-bottom: none; text-align: right;"><div>'.LF;
            } else {
               if($this->_warn_changer) {
                  $html .='      <td  class="buttonbar" style="padding-top:2px; background-color:#FF0000; text-align: right;">';
               } else {
                  $html .='      <td  class="buttonbar" style="padding-top:2px; border-bottom: none; text-align: right;">';
               }
            }
         } else {
            if ($this->_special_color) {
               $html .= '                <td  style="padding-top:2px; border-bottom: none; text-align: right;">'."\n";
            } else {
               $html .= '                <td  class="buttonbar" style="padding-top:2px; border-bottom: none; text-align: right;">'."\n";
            }
         }
         $html .= '                   '.$this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name'],'',$form_element['is_disabled'],$style).'&nbsp;'."\n";
      }elseif ( empty($_GET['show_profile'])
                or $_GET['show_profile'] != 'yes'
              ) {
         if ($this->_special_color) {
            $html .= '                <td  style="padding-top:2px; border-bottom: none; text-align: right;">'."\n";
         } else {
               if($this->_warn_changer) {
                  $html .= '                <td  class="buttonbar" style="background-color:#FF0000; padding-top:2px; border-bottom: none; text-align: right;">'."\n";
               } else {
                  $html .= '                <td  class="buttonbar" style="padding-top:2px; border-bottom: none; text-align: right;">'."\n";
               }
         }
      }
      return $html;
   }

   /** get emptyline as HTML - internal, do not use
    * this method returns a string contains an emptyline in HMTL-Code
    *
    * @param array value form element: emptyline, see class cs_form
    *
    * @return string emptyline as HMTL
    *
    * @author CommSy Development Group
    */
   function _getEmptyLineAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- EMPTY LINE -->';
      $html .= '&nbsp;'.LF;
      return $html;
   }


   /** get datetimefield as HTML - internal, do not use
    * this method returns a string contains a datetimefield in HMTL-Code
    *
    * @param array value form element: datetimefield, see class cs_form
    *
    * @return string datetimefield as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDateTimeFieldAsHTML ($form_element) {
      $html  = '';
      $textfield['name']  = $form_element['firstName'];
      $textfield['size']  = $form_element['firstFieldSize'];
      $textfield['maxlength']  = $form_element['firstFieldMaxLength'];
      $textfield['value'] = reset($form_element['value']);
      if ($form_element['isFirstMandatory']) {
         $form_element['firstLabel'] .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
      }
      if (!empty($form_element['failure_element']) and in_array('0',$form_element['failure_element'])) {
         $form_element['firstLabel'] = '<b>'.$form_element['firstLabel'].'</b>';
      }
      $html .= '         '.$form_element['firstLabel'].'&nbsp;'.$this->_getTextFieldAsHTML($textfield);

      if ($form_element['horizontal'] ) {
         if($form_element['withWhiteSpace']){
            $html .= '         &nbsp;&nbsp;'."\n";
         }else{
            $html .= ''."\n";
         }
      } else {
         $html .= '         <br />'."\n";
      }

      $textfield['name']  = $form_element['secondName'];
      $textfield['size']  = $form_element['secondFieldSize'];
      $textfield['maxlength']  = $form_element['secondFieldMaxLength'];
      $textfield['value'] = next($form_element['value']);
      if ($form_element['isSecondMandatory']) {
         $form_element['secondLabel'] .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
      }
      if (!empty($form_element['failure_element']) and in_array('1',$form_element['failure_element'])) {
         $form_element['secondLabel'] = '<b>'.$form_element['secondLabel'].'</b>';
      }
      if (!empty($form_element['second_field_type']) and $form_element['second_field_type'] == 'password') {
         $html .= '         '.$form_element['secondLabel'].'&nbsp;'.$this->_getPasswordAsHTML($textfield);
      } else {
         $html .= '         '.$form_element['secondLabel'].'&nbsp;'.$this->_getTextFieldAsHTML($textfield);
      }
      return $html;
   }

   /** get radiogroup as HTML - internal, do not use
    * this method returns a string contains an radiogroup in HMTL-Code
    *
    * @param array value form element: radiogroup, see class cs_form
    *
    * @return string radiogroup as HMTL
    *
    * @author CommSy Development Group
    */
   function _getRadioGroupAsHTML ($form_element) {
      $html  = '';
      $options = $form_element['value'];
      $option = current($options);
#      $html.='<table class="form" summary="Layout"><tr><td style="border-bottom: none; vertical-align:top;">';
      while ($option) {
         $html .= '         <input type="radio" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form($option['value']).'"';
         if ($form_element['checked'] == $option['value'] or $form_element['checked'] === $option['text']) {
            $html .= ' checked';
         }
         $html .= ' tabindex="'.$this->_count_form_elements.'"';
         $this->_count_form_elements++;
         if ( isset($option['extention']) and !empty($option['extention']) ) {
            $html .= ' '.$option['extention'];
         }
         if ( isset($form_element['is_disabled']) and !empty($form_element['is_disabled']) and $form_element['is_disabled'] ) {
            $html .= ' disabled=disabled';
         }
         if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
            $html .= ' '.$form_element['extention'];
         }
         $html .= '/>'.$option['text'];
         if (!$form_element['horizontal']) {
            $html .= '<br />';
         }
         $html .= LF;
         $option = next($options);
      }
#      $html.='</td><td style="border-bottom: none;">';
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '         &nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
#      $html.='</td></tr></table>';
      return $html;
   }

   /** get filefield as HTML - internal, do not use
    * this method returns a string contains an filefield in HMTL-Code
    *
    * @param array value form element: filefield, see class cs_form
    *
    * @return string filefield as HMTL
    */
   function _getFileFieldAsHTML ($form_element) {
      $html  = '';

      $html .= '<input type="file" name="'.$form_element['name'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;
      $html .= '/>';
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
        $html .= '&nbsp;'.$this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'125');
      }
      
      return $html;
   }

   /** get checkbox as HTML - internal, do not use
    * this method returns a string contains an checkbox in HMTL-Code
    *
    * @param array value form element: checkbox, see class cs_form
    *
    * @return string checkbox as HMTL
    */
   function _getCheckboxAsHTML ($form_element,$font_size = 8) {
      $html  = '';
      if ( !empty($form_element['counter']) ) {
   if ( $form_element['counter'] < 10 ) {
      $html .= '0';
   }
         $html .= $form_element['counter'].'.';
      }
      $html .= '<input type="checkbox" name="'.$form_element['name'].'" value="'.$this->_text_as_form($form_element['value']).'"';
      if ($form_element['ischecked']) {
         $html .= ' checked';
      }
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $html .= ' disabled="disabled"';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;
      if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
         $html .= ' '.$form_element['extention'];
      }
      $html .= '/>&nbsp;';
      $text = $form_element['text'];
      if ( !empty($form_element['chunk_text']) and $form_element['chunk_text'] > 1 ) {
   include_once('functions/text_functions.php');
         $text = chunkText($text,$form_element['chunk_text']);
      }
      if ( !empty($form_element['no_html_decode']) and $form_element['no_html_decode'] ) {
         $html .= $text;
      } else {
         $html .= '<span style="font-size:'.$font_size.'pt;">'.$this->_text_as_html_short_coding_format($text).'</span>';
      }
      return $html;
   }

   /** get checkboxgroup as HTML - internal, do not use
    * this method returns a string contains an checkboxgroup in HMTL-Code
    *
    * @param array value form element: checkboxgroup, see class cs_form
    *
    * @return string checkboxgroup as HMTL
    */
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
            $html .= '<ul id="MySortable">'.LF;
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

            $html .='var MySortables = Sortables.extend({
                        start: function(event, element) {
                           if (event.target.tagName != \'A\'
                               && event.target.tagName != \'INPUT\'
                               && event.target.tagName != \'SELECT\'
                               && event.target.tagName != \'TEXTAREA\'
                           ) {
                              this.parent(event, element);
                           }
                        }
                     });
                     window.addEvent(\'domready\', function(){
                        new MySortables($(\'MySortable\'), {
                           initialize: function(){
                              var step = 0;
                              this.elements.each(function(element, i){
                              element.setStyle(\'width\', \'400px\');
                           });
                        },
                        onDragStart: function(element, ghost){
                           ghost.setStyle(\'width\', \'395px\');
                           ghost.setStyle(\'list-style\', \'none\');
                        }
                        });
                     });'.'</script>';
         }

      }
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '         &nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
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
     if ( is_array($form_element['value'])) {
       foreach ($form_element['value'] as $key => $value) {
            $html .= '   <input type="hidden" name="'.$form_element['name'].'['.$key.']"';
            $html .= ' value="'.$this->_text_as_form($value).'"/>'.LF;
       }
     } else {
         $html .= '   <input type="hidden" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form($form_element['value']).'"/>'.LF;
     }
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
      $html  = '';
   	  if(!empty($form_element['before_form_text'])) {
         $html .= $form_element['before_form_text'].'&nbsp;'.LF;
      }

      if ($form_element['multiple']) {
         $form_element['name'] .= '[]';
      }

      if (!empty($form_element['descriptionText'])){
         $html .='<table summary="Layout"><tr><td style="border-bottom: none;">';
         $html .= $this->_text_as_html_short_coding_format($form_element['descriptionText']).'</td><td style="border-bottom: none;">';
         $html .= '&nbsp;'.$this->_translator->getMessage('SECTION_CHOOSE_POSITION');
      }

      $html .= '<select name="'.$form_element['name'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      if ($form_element['multiple']) {
         $html .= ' multiple';
      }
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $html .= ' disabled="disabled"';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;

      if (isset($form_element['width']) and !empty($form_element['width'])){
         $html.=' style="width:'.$form_element['width'].'em; font-size:'.$form_element['font_size'].'pt;"';
      }else{
         $html.=' style="font-size:'.$form_element['font_size'].'pt;"';
      }
      $html .= $form_element['event'] ? " onChange='javascript:document.f.submit()'" : '';
      if ( isset($form_element['extention']) and !empty($form_element['extention']) ) {
         $html .= ' '.$form_element['extention'];
      }
      $html .= '>'.LF;
      $options = $form_element['options'];
      $option = reset($options);
      $browser = $this->_environment->getCurrentBrowser();
      while ($option) {
         if ( !empty($option['text']) ) {
            if (!isset($option['value'])) {
               $option['value'] = $option['text'];
            }
            $html .= '            <option';
            if (isset($option['value']) and $option['value'] == 'disabled') {
               $html .= ' class="disabled" disabled="disabled"';
               if ($browser == 'MSIE') {
                  $html .= ' value="-1"';
               }
            } else {
               $html .= ' value="'.$this->_text_as_form($option['value']).'"';
            }
            if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
               $html .= ' selected';
            }
            $html .= '>';
            $html .= $this->_text_as_html_short($option['text']);
            $html .= '</option>'."\n";
         }
         $option = next($options);
      }
      $html .= '         </select>'."\n";
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         if ($form_element['noscript']){
            $html .='<noscript>'.LF;
         }
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'',false,'',$form_element['font_size'])."\n";
         if ($form_element['noscript']){
            $html .='</noscript>'.LF;
         }
      }
      if (!empty($form_element['descriptionText'])){
         $html .='</td></tr></table>';
      }
      return $html;
   }
   /** get selectgroupbox as HTML - internal, do not use
    * this method returns a string contains selectboxes in HMTL-Code
    *
    * @param array value form element: selectboxgroup, see class cs_form
    *
    * @return string selectbox as HMTL
    *
    * @author CommSy Development Group
    */
   function _getSelectGroupAsHTML ($form_element) {
      $html  = '';
      for ($i=0; $i<count($form_element['options']);$i++) {
          $element['type']          = 'select';
          $element['name']          = $form_element['names4select'][$i];
          $element['options']       = $form_element['options'][$i];
          if (isset($form_element['selected'][$i])) {
             $element['selected']      = (array)$form_element['selected'][$i];
          } else {
             $element['selected']      = array();
          }

          $element['size']          = $form_element['size'];
          $element['multiple']      = false;
          $element['event']         = $form_element['event'];
          $html .= $this->_getSelectAsHTML ($element);
      }

      return $html;
   }

   /** get passwordfield as HTML - internal, do not use
    * this method returns a string contains an passwordfield in HMTL-Code
    *
    * @param array value form element: passwordfield, see class cs_form
    *
    * @return string passwordfield as HMTL
    *
    * @author CommSy Development Group
    */
   function _getPasswordAsHTML ($form_element) {
      $html  = '';
      $html .= '<input type="password" name="'.$form_element['name'].'"';
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"';
      $html .= ' maxlength="'.$form_element['maxlength'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;
      $html .= ' class="password"';
      $html .= '/>';

      // Passwort Securitycheck
      if($form_element['name'] == 'password'){
      	$auth_source_manager = $this->_environment->getAuthSourceManager();
      	$auth_source = $auth_source_manager->_performQuery();
	      $auth_source_item = $auth_source_manager->getItem($auth_source[0]['item_id']);
	      if(!empty($auth_source_item) AND $auth_source_item->isPasswordSecureActivated()){
            $html .= '<div id="iSM">';
            $html .= '<ul class="weak">';
            $html .= '<li id="iWeak">'.$this->_translator->getMessage('COMMON_PASSWORD_SECURE_WEAK').'</li>';
            $html .= '<li id="iMedium">'.$this->_translator->getMessage('COMMON_PASSWORD_SECURE_MEDIUM').'</li>';
            $html .= '<li id="iStrong">'.$this->_translator->getMessage('COMMON_PASSWORD_SECURE_STRONG').'</li>';
            $html .= '</ul></div>';
	      }
	      unset($auth_source_manager);
	      unset($auth_source_item);
      }

      $html .= LF;
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
      $html  = '';

      if (!empty($form_element['before_form_text'])) {
         $html .= $form_element['before_form_text'].'&nbsp;'.LF;
      }

      $html .= '<input type="text" name="'.$form_element['name'].'"';
      if( isset($form_element['font_size']) ){
         $html .= ' style="font-size:'.$form_element['font_size'].'pt;"';
      }
      $html .= ' value="'.$this->_text_as_form1($form_element['value']).'"';
      $html .= ' maxlength="'.$form_element['maxlength'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;
      $html .= ' class="text"';
      if ( !empty($form_element['is_disabled']) and $form_element['is_disabled'] ) {
         $html .= 'disabled=disabled';
      }
      $html .= '/>'.LF;
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '&nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name']).LF;
      } else {
         $html .= LF;
      }
      if (!empty($form_element['after_form_text'])) {
         $html .= '&nbsp;'.$form_element['after_form_text'].LF;
      }
      return $html;
   }

   /** get as HTML - internal, do not use
    * this method returns a string contains an textfield in HMTL-Code
    *
    * @param array value form element: textfield, see class cs_form
    *
    * @return string textfield as HMTL
    *
    * @author CommSy Development Group
    */
   function _getTitleFieldAsHTML ($form_element) {
      $html  = '';
      if ($form_element['display']){
         $html .= '<input style="margin-top:5px;" type="text" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form1($form_element['value']).'"';
         $html .= ' maxlength="'.$form_element['maxlength'].'"';
         $html .= ' size="'.$form_element['size'].'"';
         $html .= ' tabindex="'.$this->_count_form_elements.'"';
         $this->_count_form_elements++;
         $html .= ' class="form_title_field"';

         $html .= '/>';
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '&nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name']).LF;
         } else {
            $html .= LF;
         }
      }else{
         $html.='<h2 class="pagetitle">'.$form_element['label'].'</h2>';
      }
      return $html;
   }

   /** get textarea as HTML - internal, do not use
    * this method returns a string contains an textarea in HMTL-Code
    *
    * @param array value form element: textarea, see class cs_form
    *
    * @return string textarea as HMTL
    *
    * @author CommSy Development Group
    */
   function _getTextAreaAsHTML ($form_element) {
      $html  = '';
      $vsize = '';
      $normal = '<textarea name="'.$form_element['name'].'"';
      $normal .= ' cols="'.$form_element['vsize'].'"';
      $normal .= ' rows="'.$form_element['hsize'].'"';
#      $normal .= ' wrap="'.$form_element['wrap'].'"';
      $normal .= ' tabindex="'.$this->_count_form_elements.'"';
      $this->_count_form_elements++;
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $normal .= ' disabled="disabled"';
      }
      $normal .= '>';

      $specialTextArea = false;
      $normal .= $this->_text_as_form($form_element['value'],$specialTextArea);
      $normal .= '</textarea>'.LF;
      $normal .= LF;


     $current_module = $this->_environment->getCurrentModule();
     $current_function = $this->_environment->getCurrentFunction();
     if ( ( $current_module == 'configuration' and $current_function == 'common' ) or
          ( $current_module == 'configuration' and $current_function == 'preferences' ) or
          ( $current_module == 'project' and $current_function == 'edit' ) or
          ( $current_module == 'community' and $current_function == 'edit' )
      ) {
         if ( isset($form_element['vsize']) and !empty($form_element['vsize']) ){
            $vsize = $form_element['vsize'];
         }
         $html_status = $form_element['with_html_area_status'];
         if ( !empty($html_status) and $html_status!='3' ){
            $with_htmltextarea = true; // control over $form_element['with_html_area']
         }else{
            $with_htmltextarea = false; // control over $form_element['with_html_area']
         }
     } else {
         $current_context = $this->_environment->getCurrentContextItem();
         $with_htmltextarea = $current_context->withHtmlTextArea();
         $html_status = $current_context->getHtmlTextAreaStatus();
     }
     $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
     $current_browser_version = $this->_environment->getCurrentBrowserVersion();
     if ( !$form_element['with_html_area']
          or !$with_htmltextarea
        ) {
        $html .= $normal;
     }
      return $html;
   }


   /** get image as HTML - internal, do not use
    * this method returns a string contains an image in HMTL-Code
    *
    * @param array value form element: image, see class cs_form
    *
    * @return string image as HMTL
    */
   function _getImageAsHTML ($form_element) {
      $retour = '';
      if ( !empty($form_element['filename']) ) {
         if ( !is_array($form_element['filename']) ) {
            $params = array();
            $params['picture'] = $form_element['filename'];
         if ( !empty($form_element['context_id']) ) {
            $context_id = $form_element['context_id'];
         } else {
            $context_id = $this->_environment->getCurrentContextID();
         }
            $curl = curl($context_id,
                         'picture', 'getfile', $params,'');
            unset($params);
         if ( $this->_environment->getCurrentModule() == 'user'
              or $this->_environment->getCurrentModule() == 'group'
              or $this->_environment->getCurrentModule() == 'institution'
            ) {
            $style = ' style="width:150px;"';
         } else {
            $style = '';
         }
            $disc_manager = $this->_environment->getDiscManager();
            if ($disc_manager->existsFile($form_element['filename'])){
               $image_array = getimagesize($disc_manager->getFilePath().$form_element['filename']);
               $pict_width = $image_array[0];
               if ($pict_width > 150){
                  $style = ' style="width:150px;"';
               }else{
                  $style = ' style="width:'.$pict_width.'px;"';
               }
            }else{
               $style = ' style="width:150px;"';
            }
            $retour .= '<img alt="Picture" src="'.$curl.'"'.$style.'/>'.LF;
            unset($curl);
            $hidden_element = array();
            $hidden_element['name']  = 'hidden_'.$form_element['name'];
            $hidden_element['value'] = $form_element['filename'];
            $retour .= $this->_getHiddenFieldAsHTML($hidden_element);
            unset($hidden_element);
            $file_element = array();
            $file_element['name'] = $form_element['name'];
            $file_element['size'] = 30;
            $retour .= BR.$this->_getFileFieldAsHTML($file_element);
            unset($file_element);
         } elseif ( !empty($form_element['filename']['filename']) ) {
            $hidden_element = array();
            $hidden_element['name']  = 'hidden_'.$form_element['name'];
            $hidden_element['value'] = $form_element['filename']['name'];
            $retour .= $this->_getHiddenFieldAsHTML($hidden_element);
            unset($hidden_element);
            $retour .= $form_element['filename']['filename'];
            $hidden_element['name']  = 'hidden_file_'.$form_element['name'];
            $hidden_element['value'] = $form_element['filename']['filename'];
            $retour .= $this->_getHiddenFieldAsHTML($hidden_element);
            unset($hidden_element);
         } elseif ( !empty($form_element['filename']['name']) ) {
            $retour .= $form_element['filename']['name'].LF;
            $hidden_element = array();
            $hidden_element['name']  = 'hidden_'.$form_element['name'].'_name';
            $hidden_element['value'] = $form_element['filename']['name'];
            $retour .= $this->_getHiddenFieldAsHTML($hidden_element);
            unset($hidden_element);
            $hidden_element = array();
            $hidden_element['name']  = 'hidden_'.$form_element['name'].'_tmpname';
            $hidden_element['value'] = $form_element['filename']['tmp_name'];
            $retour .= $this->_getHiddenFieldAsHTML($hidden_element);
            unset($hidden_element);
            $file_element = array();
            $file_element['name'] = $form_element['name'];
            $file_element['size'] = 30;
            $retour .= BR.$this->_getFileFieldAsHTML($file_element);
            unset($file_element);
         } else {
            $file_element = array();
            $file_element['name'] = $form_element['name'];
            $file_element['size'] = 30;
            $retour .= $this->_getFileFieldAsHTML($file_element);
            unset($file_element);
         }
      } else {
         $file_element = array();
         $file_element['name'] = $form_element['name'];
         $file_element['size'] = 30;
         $retour .= $this->_getFileFieldAsHTML($file_element);
         unset($file_element);
      }
      return $retour;
   }

   function _getWarningAsHTML ($form_element) {
      $params = array();
      $params['environment'] = $this->_environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $this->_class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($form_element['text']);
      return $errorbox->asHTML();
   }

   /** get form element as HTML and in commsy-style- internal, do not use
    * this method returns a string contains a form element in commsy-style in HMTL-Code
    *
    * @param array value form element: form element, see class cs_form
    *
    * @return string form element in commsy-style as HMTL
    */
   function _getFormElementAsHTML ($form_element, $without_description=false) {
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

      // Linke Spalte
      if ( $this->_with_description and !$without_description) {
         if (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'buttonbar') {
            if (!$this->_display_plain) {
               if ($this->_special_color) {
                  $html .='      <td class="key" style="border-bottom: none;">';
               } else {
                  if ($this->_warn_changer) {
                     $html .='      <td class="key" style="background-color:#FF0000;">';
                  } else {
                     $html .='      <td class="buttonbar">';
                  }
               }
            } else {
               if ($this->_special_color) {
                  $html .='      <td class="key" style="border-bottom: none;">';
               } else {
                  $html .='      <td class="key" style="border-bottom: none;">';
               }
            }
         } elseif ( isset($form_element_array[0]['type'])
                    and ($form_element_array[0]['type'] == 'textarea' and $form_element_array[0]['full_width'])
               ) {
                  $html .= '      <td class="key" colspan="2" style="width: 70%; ">';
         } elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titlefield'){
                  $html .= '      <td class="infoborder" class="key" colspan="2" style="width: 70%; "><div>';
         } elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titletext'){
                  $html .= '      <td class="infoborder" class="key" colspan="2" style="width: 70%; "><div>';
         } elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'emptyline'){
                  $html .= '      <td class="infoborder" colspan="2" style="width: 70%; ">';
         } else {
            if (!$this->_display_plain) {
               if ($this->_special_color) {
                  $html .= '      <td class="key" style="width:10%; vertical-align:baseline; ">';
               } else {
                  if (isset($form_element_array[0]['without_line']) AND $form_element_array[0]['without_line']) {
                     $html .= '      <td class="key" style="width:10%; vertical-align:baseline; border-bottom: none;">';
                  } else {
                     $html .= '      <td class="key" style="width:10%; vertical-align:baseline;">';
                  }
               }
            } else {
               if ($this->_special_color) {
                  $html .= '      <td class="key" style="width:10%;  vertical-align:baseline; border-bottom: none;">';
               } else {
                  $html .= '      <td class="key" style="width:10%;  vertical-align:baseline; border-bottom: none;">';
               }
            }
         }
         if ( isset($form_element_array[0]['label']) and $form_element_array[0]['type'] != 'subheadline') {
            if (isset($form_element_array[0]['failure'])) {
               $label = '<span class="required">'.$form_element_array[0]['label'].'</span>';
            } else {
               $label = $form_element_array[0]['label'];
            }
            $html .= $label;
            if ( !empty($label) ) {
               $html .= ':';
            }
            if (!empty($form_element_array[0]['mandatory'])) {
               $html .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
            }
         }
      } elseif ( $form_element_array[0]['type'] == 'emptyline' ) {
         $html .= '<td class="infoborder" style="width: 70%;">'.LF;
      }

      // form fields
      if (!(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titlefield')
          and !(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titletext')
          and !(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'emptyline')
          and !(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'textarea' and $form_element_array[0]['full_width']) ){
          if ( $this->_with_description  ) {
            $html .= '</td>'.LF;
         }

         // form element
         if (isset($form_element_array[0]['columnbackgroundcolor'])) {
            $html .= '      <td bgcolor='.$form_element_array[0]['columnbackgroundcolor'].'>'.LF;
         } else {
            if (!$this->_display_plain) {
               if ($this->_special_color) {
                  $html .= '      <td class="room_window_formfield" >'."\n";
               } else {
                   if (isset($form_element_array[0]['without_line']) AND $form_element_array[0]['without_line']) {
          $html .= '      <td class="formfield" style="border-bottom: none;">';
                  } else {
                     $html .= '      <td class="formfield">';
                  }
               }
            } else {
               if ($this->_special_color) {
                  $html .= '      <td class="room_window_formfield_plain" style="border-bottom: none;">'.LF;
               } else {
                  $html .= '      <td class="formfield_plain" style="border-bottom: none;">'.LF;
               }
            }
         }
      }

      $first = true;
      $show_drop_down = false;
      foreach ($form_element_array as $form_element) {
         if ($form_element['type'] == 'titlefield') {
            $html .= '         '.$this->_getTitleFieldAsHTML($form_element);
            if ($first) {
               $first = false;
            }
         }elseif ($form_element['type'] == 'titletext' AND $form_element['name'] != 'logdata') {
            $html .= '         '.$this->_getTitleTextAsHTML($form_element);
            if ($first) {
               $first = false;
            }
         } elseif ($form_element['type'] == 'emptyline') {
            #$html .= '&nbsp;';
            if ($first) {
               $first = false;
            }
         } else {
            if ($first) {
               if (isset($form_element['drop_down']) and $form_element['drop_down']){
                 $title = '&nbsp;'.$form_element['example'];
                 $html .= '<div style="padding-left:5px;">';
                 $text = '<div class="bold" style="padding:0px 0px 5px 0px;">'.$form_element['example'].':</div>';
                 $html .='<img id="toggle'.$form_element['name'].'" src="images/more.gif"/>';
                 $html .= $title;
                 $html .= '<div id="creator_information'.$form_element['name'].'">'.LF;
                 $html .= '<div style="padding:0px 0px 5px 0px;">'.LF;
                 $html .= '<div class="form_formatting_checkbox_box" style="width:400px">'.LF;
                 $show_drop_down = true;
                 $drop_down_name = $form_element['name'];
               }
               $html .= '<div style="font-size:10pt; text-align:left;">';
               $first = false;
            } else {
               $html .= '<!-- COMBINED FIELDS -->'.LF;
            }
            if ($form_element['type'] == 'textarea') {
               $html .= '         '.$this->_getTextAreaAsHTML($form_element);
               $html .= $this->_getTextFormatingInformationAsHTML($form_element);
            } elseif ($form_element['type'] == 'textfield') {
               $html .= '         '.$this->_getTextFieldAsHTML($form_element);
            } elseif ($form_element['type'] == 'password') {
               $html .= '         '.$this->_getPasswordAsHTML($form_element);
            } elseif ($form_element['type'] == 'select') {
               $html .= '         '.$this->_getSelectAsHTML($form_element);
            } elseif ($form_element['type'] == 'selectgroup') {
               $html .= '         '.$this->_getSelectGroupAsHTML($form_element);
            } elseif ($form_element['type'] == 'checkbox') {
               $html .= '         '.$this->_getCheckboxAsHTML($form_element,10).LF;
            } elseif ($form_element['type'] == 'checkboxgroup') {
               $html .= $this->_getCheckboxGroupAsHTML($form_element);
            } elseif ($form_element['type'] == 'file') {
               $html .= '         '.$this->_getFileFieldAsHTML($form_element);
            } elseif ($form_element['type'] == 'radio') {
               $html .= $this->_getRadioGroupAsHTML($form_element);
            } elseif ($form_element['type'] == 'radio_matrix') {
               $html .= $this->_getRadioMatrixAsHTML($form_element);
            } elseif ($form_element['type'] == 'anchor') {
               $html .= $this->_getAnchorAsHTML($form_element);
            } elseif ($form_element['type'] == 'datetime') {
               $html .= $this->_getDateTimeFieldAsHTML($form_element);
            } elseif ($form_element['type'] == 'subheadline') {
               $html .= $this->_getHeadlineAsHTML($form_element,$form_element['size']);
            } elseif ($form_element['type'] == 'warning') {
               $html .= $this->_getWarningAsHTML($form_element);
            } elseif ($form_element['type'] == 'button') {
               if ( isset($form_element['is_disabled']) and !empty($form_element['is_disabled']) ) {
                  $disabled = true;
               } else {
                  $disabled = false;
               }
               if ( !isset($form_element['text_after']) ) {
                  $form_element['text_after'] = '';
               }
               if ( isset($form_element['width']) and !empty($form_element['width']) ) {
                  $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name'],$form_element['width'],$disabled,'',$form_element['font_size'],$form_element['text_after']);
               } else {
                  $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name'],'',$disabled,'',$form_element['font_size'],$form_element['text_after']);
               }
            } elseif ($form_element['type'] == 'text') {
               $html .= '         '.$this->_getTextAsHTML($form_element);
            } elseif ($form_element['type'] == 'color_table') {
               $html .= '         '.$this->_getColorTableAsHTML();
            } elseif ($form_element['type'] == 'image') {
               $html .= '         '.$this->_getImageAsHTML($form_element);
            }
            if ( isset($form_element['combine']) and $form_element['combine'] == 'vertical') {
               $html .= '</div><div style="padding-top: 3px;">';
            }
         }
      }
      if ($show_drop_down){
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .='<script type="text/javascript">initTextFormatingInformation("'.$drop_down_name.'",false)</script>';
      }
      if (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'emptyline'){
         $html .= '</td>'.LF;
      }else{
         $html .= '</div></td>'.LF;
      }
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element_array[0]['name'].' ('.$form_element_array[0]['type'].') -->'."\n";
      return $html;
   }

   function _getTextFormatingInformationAsHTML($form_element){
      $show_text = true;
      if ( isset($form_element['help_text']) ){
         $show_text = $form_element['help_text'];
      }
      $html = '';
      $item = $this->_environment->getCurrentContextItem();
      $text = '';
       $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
       $html .= '<div style="padding-top:5px;">';
       $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
       $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
       $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
       $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
       $html .='<img id="toggle'.$item->getItemID().'" src="images/more.gif"/>';
       $html .= $title;
       $html .= '<div id="creator_information'.$item->getItemID().'">'.LF;
       $html .= '<div style="padding:2px;">'.LF;
       $html .= '<div id="form_formatting_box" style="width:480px">'.LF;
       $html .= $text;
       $html .= '</div>'.LF;
       $html .= '</div>'.LF;
       $html .= '</div>'.LF;
       $html .= '</div>'.LF;
      $html .='<script type="text/javascript">initTextFormatingInformation("'.$item->getItemID().'",false)</script>';
      $html .= '<!-- END OF FORM-VIEW -->'.LF;
      $current_module = $this->_environment->getCurrentModule();
      if ( ( $current_module == CS_DATE_TYPE or
             $current_module == CS_TODO_TYPE or
             $current_module == CS_MATERIAL_TYPE or
             $current_module == CS_USER_TYPE or
             $current_module == CS_DISCUSSION_TYPE or
             $current_module == CS_GROUP_TYPE or
             $current_module == CS_INSTITUTION_TYPE or
             $current_module == CS_TOPIC_TYPE or
             $current_module == CS_SECTION_TYPE or
             $current_module == CS_DISCARTICLE_TYPE or
             $current_module == CS_ANNOUNCEMENT_TYPE )
             and $show_text
      ){
         return $html;
      }else{
         return '';
      }
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
/*    function _getFormElementAsHTMLRow ($form_element) {

      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: '.$form_element['type'].' -->'."\n";
      $html .= "\n";
      if (isset($form_element['label'])) {
         if (isset($form_element['failure'])) {
            $label = '<b>'.$form_element['label'].'</b>';
         } else {
            $label = $form_element['label'];
         }
         $html .= $label;
      }
      if ($form_element['type'] == 'textarea') {
         $html .= '         '.$this->_getTextAreaAsHTML($form_element);
      } elseif ($form_element['type'] == 'textfield') {
         $html .= '         '.$this->_getTextFieldAsHTML($form_element);
      }elseif ($form_element['type'] == 'titlefield') {
         $html .= '         '.$this->_getTitleFieldAsHTML($form_element);
      } elseif ($form_element['type'] == 'password') {
         $html .= '         '.$this->_getPasswordAsHTML($form_element);
      } elseif ($form_element['type'] == 'select') {
         $html .= '         '.$this->_getSelectAsHTML($form_element);
      } elseif ($form_element['type'] == 'selectgroup') {
         $html .= '         '.$this->_getSelectGroupAsHTML($form_element);
      } elseif ($form_element['type'] == 'checkbox') {
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
      } elseif ($form_element['type'] == 'button') {
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name']);
      } elseif ($form_element['type'] == 'text') {
         $html .= '         '.$this->_getTextAsHTML($form_element);
      } elseif ($form_element['type'] == 'color_table') {
         $html .= '         '.$this->_getColorTableAsHTML();
      } elseif ($form_element['type'] == 'image') {
         $html .= '         '.$this->_getImageAsHTML($form_element);
      }
      $html .= "\n";
      $html .= '<!-- END OF FORM-ELEMENT: '.$form_element['type'].' -->'."\n";
      return $html;
   }

   function _getActionsAsHTML () {
      $html = '';
      if ( isset($this->_actions) ) {
         foreach($this->_actions as $key => $value){
            $html .= '   '.$value.''.LF;
         }
      }
      return $html;
   }    */

   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML () {
      $html  = '';
      $netnavigation_array = array();
      $html .= '<form style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .='<div style="width:100%;">'.LF;

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


      // Darstellung des Titelfelds
      $temp_array = array();
      $show_title_field = false;
      if ( $this->_with_form_title ) {
         $html .='<div style="width:100%;">'.LF;
         $html .= '<h2 class="pagetitle">'.$this->_form->getHeadline().'</h2>';
         $html .= '</div>';
         $show_title_field = true;
      }
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and $form_element['display']) {
            $html .= '<div style="padding-bottom:0px; white-space:nowrap;">';
            if (isset($form_element_array[0]['label'])) {
               if (isset($form_element_array[0]['failure'])) {
                  $label = '<span class="required">'.$form_element_array[0]['label'].'</span>';
               } else {
                  $label = $form_element_array[0]['label'];
               }
               $html .= '<span class="key">'.$label.'</span>';
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
               #$html .= '<tr>';
               #$html .= '<td style="padding:0px;">';
               #$html .= '</td>';
               #$html .= '<td style="padding:0px;">';
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

      if (!$show_title_field){
         $html .='<div style="width:100%;">'.LF;
         if ( $this->_environment->getCurrentFunction() == 'mail' ) {
            $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('COMMON_MAIL_FORM_TITLE').'</h2>';
         } else {
            $temp_mod_func = mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') . '_' . mb_strtoupper($this->_environment->getCurrentFunction(), 'UTF-8');
            $tempMessage = "";
            switch( $temp_mod_func  )
            {
               case 'ACCOUNT_PASSWORD':      // Password ändern
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_PASSWORD_FORM_TITLE');
                  break;
               case 'ACCOUNT_PREFERENCES':   // Benutzer, Einstellungen ändern
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_PREFERENCES_FORM_TITLE');
                  break;
               case 'ACCOUNT_STATUS':        // Status ändern (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_STATUS_FORM_TITLE');
                  break;
               case 'CONFIGURATION_AGB':     // Nutzungsvereinbarungen
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_AGB_FORM_TITLE');
                  break;
               case 'CONFIGURATION_AUTHENTICATION': // Authentifizierung einstellen (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_AUTHENTICATION_FORM_TITLE');
                  break;
               case 'CONFIGURATION_BACKUP':  // Backup eines Raumes einspielen (Server)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_BACKUP_FORM_TITLE');
                  break;
               case 'CONFIGURATION_CHAT':    // Raum-Chat einstellen
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_CHAT_FORM_TITLE');
                  break;
               case 'CONFIGURATION_DATES':   // Termindarstellung
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DATES_FORM_TITLE');
                  break;
               case 'CONFIGURATION_DEFAULTS': // Voreinstellungen für Räume
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DEFAULTS_FORM_TITLE');
                  break;
               case 'CONFIGURATION_DISCUSSION': // Art der Diskussion
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DISCUSSION_FORM_TITLE');
                  break;
               case 'CONFIGURATION_EXPORT':  // Raum exportieren (Server)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXPORT_FORM_TITLE');
                  break;
               case 'CONFIGURATION_EXTRA':   // Extras einstellen (Server)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXTRA_FORM_TITLE');
                  break;
               case 'CONFIGURATION_GROUPROOM': // Wenn das Extra "Gruppenräume" eingeschaltet ist
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_GROUPROOM_FORM_TITLE');
                  break;
               case 'CONFIGURATION_HOMEPAGE': // Raum-Webseite einstellen
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HOMEPAGE_FORM_TITLE');
                  break;
               case 'CONFIGURATION_HOME':    // Konfiguration der Home
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HOME_FORM_TITLE');
                  break;
               case 'CONFIGURATION_HTMLTEXTAREA': // FCK-Editor-Konfiguration ??
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE');
                   break;
               case 'CONFIGURATION_IMS':     // IMS-Account Einstellungen (Server)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_IMS_FORM_TITLE');
                  break;
               case 'CONFIGURATION_LANGUAGE': // Verfügbare Sprachen (Server)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_LANGUAGE_FORM_TITLE');
                  break;
               case 'CONFIGURATION_MAIL':    // E-Mail-Texte
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE');
                  break;
               case 'CONFIGURATION_MOVE':    // Raum auf anderes Portal umziehen (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_MOVE_FORM_TITLE');
                  break;
               case 'CONFIGURATION_NEWS':    // Ankündigungen bearbeiten (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_NEWS_FORM_TITLE');
                  break;
               case 'CONFIGURATION_PLUGIN':  // Sponsoren und Werbung
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_FORM_TITLE');
                  break;
               case 'CONFIGURATION_PORTALHOME': // Gestaltung der Raumübersicht (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PORTALHOME_FORM_TITLE');
                  break;
               case 'CONFIGURATION_PORTALUPLOAD': // Konfiguration des Uploads(Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PORTALUPLOAD_FORM_TITLE');
                  break;
               case 'CONFIGURATION_PREFERENCES': // Allgemeine Einstellungen bearbeiten (pers. Raum)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PREFERENCES_FORM_TITLE');
                  break;
               case 'CONFIGURATION_PRIVATEROOM_NEWSLETTER': // E-Mail-Newsletter (priv.)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PRIVATEROOM_NEWSLETTER_FORM_TITLE');
                  break;
               case 'CONFIGURATION_ROOM_OPENING': // Raumeröffnungen (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPENING_FORM_TITLE');
                  break;
               case 'CONFIGURATION_RUBRIC':  // Auswahl der Rubriken
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_FORM_TITLE');
                  break;
               case 'CONFIGURATION_SERVICE': // Handhabungssupport einstellen
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_SERVICE_FORM_TITLE');
                  break;
               case 'CONFIGURATION_TIME':    // Zeittakte bearbeiten (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_TIME_FORM_TITLE');
                  break;
               case 'CONFIGURATION_USAGEINFO': // Nutzungshinweise bearbeiten
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE');
                  break;
               case 'LABELS_EDIT':           // Nutzungshinweise bearbeiten
                  $tempMessage = $this->_translator->getMessage('COMMON_LABELS_EDIT_FORM_TITLE');
                  break;
               case 'BUZZWORDS_EDIT':        // Nutzungshinweise bearbeiten
                  $tempMessage = $this->_translator->getMessage('COMMON_BUZZWORDS_EDIT_FORM_TITLE');
                  break;
               case 'USER_ACTION':           // Personen E-Mail senden
                  $tempMessage = $this->_translator->getMessage('COMMON_USER_EMAIL_SEND_FORM_TITLE');
                  break;
               case 'USER_CLOSE':            // Personen E-Mail senden
                  $tempMessage = $this->_translator->getMessage('COMMON_USER_CLOSE_FORM_TITLE');
                  break;
               case 'ACCOUNT_CLOSE':            // Personen E-Mail senden
                  $tempMessage = $this->_translator->getMessage('COMMON_USER_CLOSE_FORM_TITLE');
                  break;
               case 'DATE_IMPORT':           // Externe Termine importieren
                  $tempMessage = $this->_translator->getMessage('COMMON_DATE_IMPORT_FORM_TITLE');
                  break;
               case 'USER_PREFERENCES':      //
                  $tempMessage = $this->_translator->getMessage('COMMON_USER_PREFERENCES_FORM_TITLE');
                  break;
               case 'MAIL_TO_MODERATOR':      //
                  $tempMessage = $this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_MODERATOR');
                  break;
               case 'TAG_EDIT':      //
                  $tempMessage = $this->_translator->getMessage('TAG_EDIT_FORM_TITLE');
                  break;
               case 'LANGUAGE_UNUSED':      //
                  $tempMessage = $this->_translator->getMessage('LANGUAGE_UNUSED_FORM_TITLE');
                  break;
               case 'MATERIAL_IMS_IMPORT':      //
                  $tempMessage = $this->_translator->getMessage('MATERIAL_IMS_IMPORT');
                  break;
               case 'CONFIGURATION_EXPORT_IMPORT': // Konfiguration des Uploads(Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXPORT_IMPORT_FORM_TITLE');
                  break;
               default:                      // "Bitte Messagetag-Fehler melden ..."
                  $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_form_view(1819) ";
                  break;
            }
            $html .= '<h2 class="pagetitle">' . $tempMessage . '</h2>';
         }
         $html .= '</div>';
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


      $html .='<div style="width: 100%;">'.LF;
      $funct = $this->_environment->getCurrentFunction();
      $html .='<div style="float:right; width:27%; margin-top:0px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      if ($user->isUser() and $funct !='info_text_form_edit' and $funct !='info_text_edit'){
         $html .='<div id="commsy_panels_form">'.LF;
         $rubric_info_array = $room->getUsageInfoFormArray();
         if (!is_array($rubric_info_array)) {
            $rubric_info_array = array();
         }
         if (!(in_array($this->_environment->getCurrentModule().'_no', $rubric_info_array)) ){
            $room = $this->_environment->getCurrentContextItem();
            $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
            $html .= $this->_getRubricFormInfoAsHTML($this->_environment->getCurrentModule());
            $html .='</div>'.LF;
         }
         if (  $this->_environment->getCurrentModule() !='buzzwords' and
               $this->_environment->getCurrentModule() !='labels' and
               $this->_environment->getCurrentFunction() !='close' and
               $this->_environment->getCurrentModule() !='configuration' and
               $this->_environment->getCurrentFunction() !='preferences' and
               $this->_environment->getCurrentFunction() !='to_moderator' and
               $this->_environment->getCurrentFunction() !='import' and
               !($this->_environment->inPortal() and $this->_environment->getCurrentModule() =='account') and
               $funct !='mail'
            ) {
            if ($this->_environment->getCurrentModule() != CS_USER_TYPE and
               $this->_environment->getCurrentModule() != CS_PROJECT_TYPE and
               $this->_environment->getCurrentModule() != CS_COMMUNITY_TYPE){
               foreach ($form_element_array as $form_element) {
                  if ( isset($form_element['type']) and $form_element['type'] == 'netnavigation' ) {
                     $netnavigation_array[] = $form_element;
                  }
               }
               $this->_count_form_elements = $this->_count_form_elements + 50;
                              foreach ($form_element_array as $form_element) {
                  if ( (isset($form_element[0]['name']) and $form_element[0]['name'] == 'buzzwordlist')
                    or (isset($form_element[0]['name']) and $form_element[0]['name'] == 'buzzword') ) {
                     $html .='<div class="commsy_panel" style="margin-bottom:1px; padding:0px;">'.LF;
                     $html .= $this->_getBuzzwordBoxAsHTML($form_element);
                     $html .='</div>'.LF;
                  }
               }
               foreach ($form_element_array as $form_element) {
                  if ( (isset($form_element[0]['name']) and $form_element[0]['name'] == 'taglist')
                    or (isset($form_element[0]['name']) and $form_element[0]['name'] == 'tag') ) {
                     $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
                     $html .= $this->_getTagBoxAsHTML($form_element);
                     $html .='</div>'.LF;
                  }
               }
               if ($this->_environment->getCurrentModule() != 'account'){
                  $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
                  $html .= $this->_getAllLinkedItemsAsHTML($netnavigation_array);
                  $html .='</div>'.LF;
               }
               $this->_count_form_elements = $this->_count_form_elements - 50;
            }
         }
         $html .='</div>'.LF;
      }
      $html .= '</div>'.LF;

      $html .='<div class="infoborder" style="width:71%; margin-top:5px; padding-top:10px; vertical-align:bottom;">'.LF;
      $html .= '<!-- BEGIN OF FORM-VIEW -->'.LF;
      $html .= '<table class="form" style="font-size:10pt; border-collapse:collapse; margin-bottom:10px;" summary="Layout">'.LF;
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
               and !(isset($form_element[0]['name']) and $form_element[0]['name'] == 'tag')
            ) {
               $html .= $text.$this->_getFormElementAsHTML($form_element).'   </tr>'.LF;
            }
         }
         $i++;
      }
      $html .= '</table>'.LF;
      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
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
               $html .='      <td colspan="2" style="border-bottom: none; xwhite-space:nowrap;">';
            } else {
               if ($this->_warn_changer) {
                  $html .='      <td colspan="2" style="background-color:#FF0000; xwhite-space:nowrap;">';
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
         $html .= '<span class="required" style="font-size:16pt;">*</span> <span class="key" style="font-weight:normal;">'.$this->_translator->getMessage('COMMON_MANDATORY_FIELDS').'</span> '.$buttonbartext;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</form>'.BRLF;
      return $html;
   }



   function _getBuzzwordBoxAsHTML ($form_element) {
      $error_display = false;
      if ( isset($this->_error_array) and !empty($this->_error_array) ){
         foreach ($this->_error_array as $error){
            if ($error == $this->_translator->getMessage('COMMON_ERROR_BUZZWORD_ENTRY')){
               $error_display = true;
            }
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $html_text = '';
      if ($current_context->isBuzzwordMandatory()){
        $html_text = ' *';
      }
      $html = '<div class="right_box">'.LF;
      $color = $current_context->getColorArray();
      if ($error_display){
         $html .= '<div class="right_box_title" style="color:'.$color['warning'].';">'.$this->_translator->getMessage('COMMON_BUZZWORD_BOX').$html_text.'</div>';
      }else{
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_BUZZWORD_BOX_EDIT').$html_text.'</div>';
      }
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<table style="margin:0px; padding:0px;"><tr><td>'.LF;
      $html .= $this->_getFormElementAsHTML($form_element,true);
      $html .= '</tr></table></div>'.LF;
      $html .= '</div>'.LF;
      unset($current_context);
      return $html;
   }


   function _getTagBoxAsHTML ($form_element) {
      $error_display = false;
      if ( isset($this->_error_array) and !empty($this->_error_array) ){
         foreach ($this->_error_array as $error){
            if ($error == $this->_translator->getMessage('COMMON_ERROR_TAG_ENTRY')){
               $error_display = true;
            }
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $color = $current_context->getColorArray();
      $html_text = '';
      if ($current_context->isTagMandatory()){
        $html_text = ' *';
      }
      $html = '<div class="right_box">'.LF;
      if ($error_display){
         $html .= '<div class="right_box_title" style="color:'.$color['warning'].';">'.$this->_translator->getMessage('COMMON_TAG_BOX').$html_text.'</div>';
      }else{
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAG_BOX_EDIT').$html_text.'</div>';
      }
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<table><tr><td>'.LF;
      $html .= $this->_getFormElementAsHTML($form_element,true);
      $html .= '</tr></table></div>'.LF;
      $html .= '</div>'.LF;
      unset($current_context);
      return $html;
   }


   function _getAllLinkedItemsAsHTML ($rubric_array) {
      if ( !empty($rubric_array) ) {
         $connections = $this->getRubricConnections();
      if ( !empty($connections) ) {
         $item = $this->_item;
         $html = '';
         $html .= '<div id="netnavigation1">'.LF;
         $html .= '<div class="netnavigation" >'.LF;
#         $html .= '         <noscript>';
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_NETNAVIGATION').'</div>';
#         $html .= '         </noscript>';
         $i = 0;
         $first = true;
         $pos = '-1';
         foreach ( $connections as $connection ) {
            if ( $connection != CS_USER_TYPE){
            $html .= $this->_getLinkedItemsAsHTML($rubric_array[$i], $connection, $this->_is_perspective($connection));
            if (isset($rubric_array[$i]['selected'])){
               $count = count($rubric_array[$i]['selected']);
            }else{
               $count = 0;
            }
            if (isset($_GET['backfrom']) and !empty($_GET['backfrom']) and $connection == $_GET['backfrom']){
               $pos = $i;
            }
            if ($connection != CS_SECTION_TYPE and $connection != CS_DISCARTICLE_TYPE){
               switch ( mb_strtoupper($connection, 'UTF-8') )
               {
                 case 'ANNOUNCEMENT':
                     $temp_title = $this->_translator->getMessage('ANNOUNCEMENTS');
                     break;
                  case 'DATE':
                     $temp_title = $this->_translator->getMessage('DATES');
                     break;
                  case 'DISCUSSION':
                     $temp_title = $this->_translator->getMessage('DISCUSSIONS');
                     break;
                  case 'GROUP':
                     $temp_title = $this->_translator->getMessage('GROUPS');
                     break;
                  case 'INSTITUTION':
                     $temp_title = $this->_translator->getMessage('INSTITUTIONS');
                     break;
                  case 'MATERIAL':
                     $temp_title = $this->_translator->getMessage('MATERIALS');
                     break;
                  case 'PROJECT':
                     $temp_title = $this->_translator->getMessage('PROJECTS');
                     break;
                  case 'TODO':
                     $temp_title = $this->_translator->getMessage('TODOS');
                     break;
                  case 'TOPIC':
                     $temp_title = $this->_translator->getMessage('TOPICS');
                     break;
                  case 'USER':
                     $temp_title = $this->_translator->getMessage('USERS');
                     break;
                  default:
                     $temp_title = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_form_view(2009) ';
                     break;
               }
            }else{
               $temp_title = $this->_translator->getMessage('MATERIALS');
               if ($connection == CS_DISCARTICLE_TYPE) {
                  $temp_title = $this->_translator->getMessage('DISCUSSIONS');
               }
            }
            if ($first){
               $first = false;
               $title_string = '"'.$temp_title;
               $title_string .= ' ('.$count.')"';
            }else{
               $title_string .= ',"'.$temp_title;
               $title_string .= ' ('.$count.')"';
            }
            $i ++;
         }
         }
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),"'.$pos.'","1");'.LF;
         $html .= '</script>'.LF;
         return $html;
      }
      }
   }

   /**
    * Internal methods for printing out connected rubrics.
    * Generally, these methods need not be overridden.
    */
   function _is_perspective ($rubric) {
      $in_array = in_array($rubric, array(CS_GROUP_TYPE,CS_TOPIC_TYPE, CS_INSTITUTION_TYPE)) ;
      if ($rubric == CS_INSTITUTION_TYPE) {
         $context = $this->_environment->getCurrentContextItem();
         $in_array = $context->withRubric(CS_INSTITUTION_TYPE);
      }
      return $in_array;
   }


   function _getLinkedItemsAsHTML ($link_array, $connection, $is_perspective=false, $always=true, $attach_link=false) {
      $current_context = $this->_environment->getCurrentContextItem();
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $mod = $this->_with_modifying_actions;
      $module = Type2Module($connection);
      if ($connection != CS_SECTION_TYPE and $connection != CS_DISCARTICLE_TYPE){
            switch ( mb_strtoupper($connection, 'UTF-8') )
            {
               case 'ANOUNCEMENT':
                  $temp_title = $this->_translator->getMessage('ANNOUNCEMENTS');
                  break;
               case 'DATE':
                  $temp_title = $this->_translator->getMessage('DATES');
                  break;
               case 'DISCUSSION':
                  $temp_title = $this->_translator->getMessage('DISCUSSIONS');
                  break;
               case 'GROUP':
                  $temp_title = $this->_translator->getMessage('GROUPS');
                  break;
               case 'INSTITUTION':
                  $temp_title = $this->_translator->getMessage('INSTITUTIONS');
                  break;
               case 'MATERIAL':
                  $temp_title = $this->_translator->getMessage('MATERIALS');
                  break;
               case 'PROJECT':
                  $temp_title = $this->_translator->getMessage('PROJECTS');
                  break;
               case 'TODO':
                  $temp_title = $this->_translator->getMessage('TODOS');
                  break;
               case 'TOPIC':
                  $temp_title = $this->_translator->getMessage('TOPICS');
                  break;
               case 'USER':
                  $temp_title = $this->_translator->getMessage('USERS');
                  break;
               default:
                  $temp_title = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_form_view(2009) ';
                  break;
            }
         }else{
            $temp_title = $this->_translator->getMessage('MATERIALS');
            if ($connection == CS_DISCARTICLE_TYPE) {
               $temp_title = $this->_translator->getMessage('DISCUSSIONS');
            }
         }

      $html .='		<div class="netnavigation_panel">     '.LF;
      $html .= '         <noscript>';
      $html .= '<div class="netnavigation_title">'.$temp_title.'</div>';
      $html .= '         </noscript>';
      $html .= '         <div>';
      $html .= '<div class="netnavigation_list" style="border-top:1px solid #B0B0B0;">'.LF;
      if ( empty($link_array['value']) ) {
         $html .= '<ul style="padding-top:0px;">'.LF;
         $html .= '   <li>';
         $html .= '   <span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span>'.LF;
         $html .= '   </li>';
         $html .= '   </ul>';
      }else{
         $html .= '<ul style="padding-top:0px; list-style:none; margin-left:0px; padding-left:0px;">'.LF;
         $html .= '   <li>';
         $html .= $this->_getCheckboxGroupAsHTML($link_array);
         $html .= '   </li>';
         $html .= '   </ul>';
      }
      $html .= '</div>'.LF;
      if ( $link_array['link_text']) {
         $html .= '<div style="border-top:0px; text-align:center; padding-bottom:3px;">'.LF;
         $params = array();
         $tempMessage = "";
         switch( mb_strtoupper($connection, 'UTF-8') )
         {
            case 'GROUP':                 // Button: Gruppen zuordnen (erst ab 20 vorhand. Gruppen) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_GROUP_BUTTON');
                break;
            case 'INSTITUTION':           // Button: Institutionen suchen (erst ab 20 vorhand. Institutionen) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_INSTITUTION_BUTTON');
               break;
            case 'MATERIAL':              // Button: Materialien zuordnen (erst ab 20 vorhand. Materialien) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_MATERIAL_BUTTON');
               break;
            case 'DATE':              // Button: DATEien zuordnen (erst ab 20 vorhand. DATEien) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_DATE_BUTTON');
               break;
            case 'ANNOUNCEMENT':              // Button: ANNOUNCEMENTien zuordnen (erst ab 20 vorhand. ANNOUNCEMENTien) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_ANNOUNCEMENT_BUTTON');
               break;
            case 'DISCUSSION':              // Button: DISCUSSIONien zuordnen (erst ab 20 vorhand. DISCUSSIONien) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_DISCUSSION_BUTTON');
               break;
            case 'TODO':              // Button: TODOien zuordnen (erst ab 20 vorhand. TODOien) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_TODO_BUTTON');
               break;
            case 'PROJECT':               // Button: Projekträume zuordnen (erst ab 20 vorhand. Projekträumen) OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_PROJECT_BUTTON');
               break;
            case 'TOPIC':                 // Button: Themen suchen (erst ab 20 vorhand. Themen)OK
               $tempMessage = $this->_translator->getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON');
               break;
            default:
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR') . " cs_form_view(2062) ";
               break;
         }
         $params['option'] = $tempMessage;
         $html .= '<input style="width:150px; font-size:8pt;" type="submit" name="option"';
         $html .= ' value="'.$tempMessage.'"';
         $html .= '/>';
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

  function _getRubricFormInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubricForm($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $array = $this->_environment->getCurrentParameterArray();
      $html .= '<div class="right_box_title">'.$room->getUsageInfoHeaderForRubricForm($act_rubric).'</div>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      #$html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $html .= $this->_text_as_html_long($info_text).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function asHTMLRow ($right = false) {
      if ($right) {
         $td_class = 'formasrow_right';
      } else {
         $td_class = 'formasrow';
      }
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-VIEW -->'."\n";
      $html .= '   <table border="0" cellspacing="0" cellpadding="3" width="100%" summary="Layout">'."\n";
      $html .= '      <form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      if ($right) {
         $html .= '      <tr><td width="100%"></td></tr>'."\n";
      }
      $html .= '      <tr>'."\n";
      $html .= '         <td class="'.$td_class.'" >';

      // first all hidden elements
      $form_element = $this->_form_elements->getFirst();
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }

      $num_of_buttons = 0;
      $form_element = $this->_form_elements->getFirst();
      while ($form_element) {
         if ($form_element['type'] == 'button') {
            $num_of_buttons++;
         }
         $form_element = $this->_form_elements->getNext();
      }

      $count_buttons = 0;
      $form_element = $this->_form_elements->getFirst();
      $this->_count_form_elements = 0;
      while ($form_element) {
         if ($form_element['type'] != 'hidden') {
            if ($form_element['type'] == 'button') {
               $html .= $this->_getFormElementAsHTMLRow($form_element);
               $count_buttons++;
               if ($count_buttons < $num_of_buttons) {
                  $html .='      </td>'."\n";
                  if ($count_buttons+1 == $num_of_buttons) {
                     $html .='      <td class="formasrow_right" >'."\n";
                  } else {
                     $html .='      <td class="'.$td_class.'" >'."\n";
                  }
               }
            } else {
               $html .= $this->_getFormElementAsHTMLRow($form_element);
            }
         }
         $this->_count_form_elements++;
         $form_element = $this->_form_elements->getNext();
      }
      $html .= '         </td>'."\n";
      $html .= '      </tr>'."\n";
      $html .= '      </form>'."\n";
      $html .= '   </table>'."\n";
      $html .= '<!-- END OF FORM-VIEW -->'."\n";

      return $html;
   }

   /** get form view as a column in HTML
    * this method returns the form view as a column in HTML-Code
    *
    * @param boolean right if true -> align=right
    *
    * @return string form view as column in HMTL
    *
    * @author CommSy Development Group
    */
   function asHTMLColumn ($right = false) {
      if ($right) {
         $td_class = 'form_as_column_right';
      } else {
         $td_class = 'form_as_column';
      }

      $html  = '';
      $html .= "\n".'<!-- BEGIN OF FORM-VIEW AS COLUMN -->'."\n";
      $html .= '   <table border="0" cellspacing="0" cellpadding="0" width="100%" summary="Layout">'."\n";
      $html .= '      <form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .= '      <tr>'."\n";
      //$html .= '         <td class="'.$td_class.'" >'."\n";
      $html .= '         <td >'."\n";

      // first all hidden elements
      $form_element = $this->_form_elements->getFirst();
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }
      $html .= '         </td>'."\n";
      $html .= '      </tr>'."\n";

      $form_element = $this->_form_elements->getFirst();
      $this->_count_form_elements = 0;
      while ($form_element) {
         if ($form_element['type'] != 'hidden') {
            $html .= '      <tr>'."\n";
            //$html .= '         <td class="'.$td_class.'" >';
            $html .= '         <td >'."\n";
            $html .= $this->_getFormElementAsHTMLRow($form_element)."\n";
            $html .= '         </td>'."\n";
            $html .= '      </tr>'."\n";
         }
         $form_element = $this->_form_elements->getNext();
         $this->_count_form_elements++;
      }
      $html .= '      </form>'."\n";
      $html .= '   </table>'."\n";
      $html .= '<!-- END OF FORM-VIEW AS COLUMN -->'."\n";

      return $html;
   }

   /** get first input field
    * this method returns the name of the first input field, needed for setFocus
    *
    * @return string name of first input field
    *
    * @author CommSy Development Group
    */
   function _getFirstInputFieldName() {
      $form_element = $this->_form_elements->getFirst();
      $result = '';
      while ( $form_element and $result == '') {
         if ( $form_element['type'] != 'hidden' and $form_element['type'] != 'text'
              and $form_element['type'] != 'textline' and $form_element['type'] != 'headline' and $form_element['type'] != 'subheadline'
              and $form_element['type'] != 'radio') {
            $result = $form_element['name'];
         }
         $form_element = $this->_form_elements->getNext();
      }
      return $result;
   }

   function withoutJavascript () {
      $this->_with_javascript = false;
   }

   function withAnchor () {
      $this->_with_anchor = true;
   }

   function setFocusElementAnchor ($element) {
     $this->_focus_element_anchor = $element;
   }

   function getFocusElementAnchor () {
     return $this->_focus_element_anchor;
   }

   function setFocusElementOnLoad ($element) {
     $this->_focus_element_onload = $element;
   }

   function getFocusElementOnLoad () {
     return $this->_focus_element_onload;
   }

   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for setFocus on first input field
    */
   function getInfoForHeaderAsHTML () {
      $html  = '';
      if ($this->_with_javascript /*and !$this->_with_anchor*/ ) {
         $html .= '   <script type="text/javascript">'.LF;
         $html .= '      <!--'.LF;

         $current_user = $this->_environment->getCurrentUser();
         if ( $current_user->isAutoSaveOn()
              and $this->_environment->getCurrentFunction() == 'edit'
              and ( $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                    or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                    or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                    or $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                    or $this->_environment->getCurrentModule() == CS_SECTION_TYPE
                    or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                    or $this->_environment->getCurrentModule() == CS_DISCARTICLE_TYPE
                    or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
                    or $this->_environment->getCurrentModule() == CS_INSTITUTION_TYPE
                    or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
                    or $this->_environment->getCurrentModule() == CS_ANNOTATION_TYPE
                  )
            ) {
            $html .= LF;
            $html .= '         var timerID = null;'.LF;
            $html .= '         var timerRunning = false;'.LF;
            $html .= '         var startDate;'.LF;
            $html .= '         var startSecs;'.LF;

            global $symfonyContainer;

            $c_autosave_mode = $symfonyContainer->getParameter('commsy.autosave.mode');
            $c_autosave_limit = $symfonyContainer->getParameter('commsy.autosave.limit');

            $html .= '         var dispMode = '.$c_autosave_mode.';'.LF;
            $html .= '         var sessLimit = '.($c_autosave_limit*60).';'.LF;

            $form_element = $this->_form_elements->getFirst();
            while ($form_element) {
               if ($form_element['type'] == 'buttonbar') {
                  $html .= '         var breakCrit = "'.$form_element['labelSave'].'"'.';'.LF;
                  break;
               }
               $form_element = $this->_form_elements->getNext();
            }
         }

         $html .= LF;
         $html .= '         function setfocus() {'.LF;
         if ( $this->getFocusElementOnload() != '' ) {
           $html .= '           document.f.elements["'.$this->getFocusElementOnLoad().'"].focus();'.LF;
         } elseif ( $this->getFocusElementAnchor() != '' ) {
           $html .= '           location.hash="'.$this->getFocusElementAnchor().'";'.LF;
         } else {
           $html .= '           document.f.elements["'.$this->_getFirstInputFieldName().'"].focus();'.LF;
         }
         $html .= '         }'.LF;
         if (isset($this->_form) and !empty($this->_form)) {
            $html .= $this->_form->getInfoForHeaderAsHTML();
         }
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
      $onloads = array();
      $onloads[] = 'setfocus()';
      $current_user = $this->_environment->getCurrentUser();
      if ( $current_user->isAutoSaveOn()
           and $this->_environment->getCurrentFunction() == 'edit'
           and ( $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                 or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                 or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                 or $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                 or $this->_environment->getCurrentModule() == CS_SECTION_TYPE
                 or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                 or $this->_environment->getCurrentModule() == CS_DISCARTICLE_TYPE
                 or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
                 or $this->_environment->getCurrentModule() == CS_INSTITUTION_TYPE
                 or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
                 or $this->_environment->getCurrentModule() == CS_ANNOTATION_TYPE
               )
         ) {
         $onloads[] = 'startclock()';
      }
      if (isset($this->_form) and !empty($this->_form)) {
         $onloads[] = $this->_form->getInfoForBodyAsHTML();
      }
      if ($this->_with_javascript) {
         $html .= ' onload="'.implode(';',$onloads).'"';
      }
      return $html;
   }

  /** internal method to create errorbox if there are errors, INTERNAL
    * this method creates an errorbox with messages form the error array
    *
    * @author CommSy Development Group
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
         $error_string .= $error.BRLF;
      }
      $errorbox->setText($error_string);
      $html = '<div style="padding-left:30px; padding-bottom:5px; padding-top:0px;">'.$errorbox->asHTML().'</div>';

      return $html;
   }

   function setWithoutDescription () {
      $this->_with_description = false;
   }

   function getTitle () {
     $retour  = '';
     $retour .= $this->_form->getHeadline();
     $html = '';
      if ( ( $this->_environment->inPortal() or $this->_environment->inServer() )
          and $this->_environment->getCurrentModule() == 'configuration') {
       // link to configuration index
         $html = '<div class="actions" style="font-size: small; font-weight: normal;">'.LF;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'index',
                             '',
                             $this->_translator->getMessage('ADMIN_INDEX')).LF;
         $html .= '</div>'.LF;
     }
     $this->_display_title = false;
     return $html.$retour;
   }

   function setWithFormTitle () {
      $this->_with_form_title = true;
   }
}
?>