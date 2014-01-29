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
$this->includeClass(ERRORBOX_VIEW);

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
    * @param cs_item environment            commsy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_form_view ($params) {
      $this->cs_view($params);
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
      if ( !empty($_GET['iid']) ) {
         $this->current_iid = $_GET['iid'];
      } elseif ( !empty($_POST['iid']) ) {
         $this->current_iid = $_POST['iid'];
      } else {
         $this->current_iid = 'NEW';
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

   function _getExplanationAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: explanation -->'.LF;
      $style = '';
      $html .= $form_element['description'].LF;
      $html .= '<!-- END OF FORM-ELEMENT: explanation -->'.LF;
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
      $html .= '         <a name="'.$form_element['name'].'"></a>'.LF;
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
      $html .= '<!-- BEGIN OF FORM-ELEMENT: textline -->'.LF;
      $html .= '   <td colspan="3" style="border-bottom: none;">'.LF;
      $html .= '      '.$form_element['description'].LF;
      $html .= '   </td>'."\n";
      $html .= '<!-- END OF FORM-ELEMENT: textline -->'.LF;
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
   function _getImageButtonAsHTML ($form_element) {
      $html  = '';
      $html .= '<!-- BEGIN OF FORM-ELEMENT: Imagebutton -->'.LF;
      $html .= '<input type="image"'.LF;
      $html .=' src="'.$form_element['src'].'"'.LF;
      $html .=' name="'.$form_element['name'].'"'.LF;
      $html .=' width="'.$form_element['width'].'" height="'.$form_element['height'].'"'.LF;
      $html .= 'alt="'.$form_element['alt'].'"/>'.LF;
      $html .= '<!-- END OF FORM-ELEMENT: Imagebutton -->'.LF;
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
            $html .= '<br />'."\n";
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
   function _getButtonAsHTML ($button_text, $button_name, $width = '', $is_disabled = false, $style='', $font_size='10', $text_after='',$javascript='',$id='') {
      $html  = '';
      $html .= '<input type="submit" name="'.$button_name.'"';
      if(!empty($id)) {
         $html .= ' id="'.$id.'"';
      }
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
         $html .= $this->_getButtonAsHTML($form_element['labelSave'],$form_element['name'],'',$form_element['is_disabled'],$style,'','',$form_element['javascript'],$form_element['idSave'])."\n";
      }
      if (!empty($form_element['labelSecondSave'])) {
         $html .= $this->_getButtonAsHTML($form_element['labelSecondSave'],$form_element['name'],'',$form_element['is_disabled'],$style)."\n";
      }
      if (!empty($form_element['labelCancel'])) {
         $html .= $this->_getButtonAsHTML($form_element['labelCancel'],$form_element['name'],'',$form_element['is_disabled'],$style,'','','',$form_element['idCancel'])."\n";
      }

      $current_user = $this->_environment->getCurrentUser();
      $portal_user = $current_user->getRelatedPortalUserItem();
      if ( $this->_environment->getCurrentFunction() == 'edit'
           and !$current_user->isRoot()
           and isset($portal_user)
           and $portal_user->isAutoSaveOn()
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
         global $c_autosave_mode;
         if ( $c_autosave_mode == 1 ) {
            $currTime = time();
            global $c_autosave_limit;
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
         $html .= '                   '.$this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name'],'',$form_element['is_disabled'],$style,'','','',$form_element['idDelete']).'&nbsp;'."\n";
      } elseif ( empty($_GET['show_profile'])
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

      if(!$form_element['showOnlyDate']){
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
      //global $c_new_upload;
//      $with_flash = true;
      //http://www.uploadify.com/documentation/
//      $with_flash = false;
//      $session->getValue('flash')
      $val = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();

      $use_new_upload = false;
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->issetValue('flash')){
         if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')){
            $use_new_upload = true;
         }
      }

      // do not use new upload in case of room picture, user picuture, ...
      $module = $this->_environment->getCurrentModule();
      $fct = $this->_environment->getCurrentFunction();
      if(   isset($_GET['show_profile']) ||
            ($module == 'configuration' && $fct == 'room_options') ||
            ($module == 'group' && $fct == 'edit') ||
            ($module == 'user' && $fct == 'edit') ||
            ($module == 'material' && $fct == 'ims_import') ||
         ($module == 'date' && $fct == 'import')) {
         $use_new_upload = false;
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $portal_user = $current_user->getRelatedPortalUserItem();
      if ( !$current_user->isRoot()
           and isset($portal_user)
           and !$portal_user->isNewUploadOn()
         ) {
         $use_new_upload = false;
      }

      // do not use new upload if browsing with https
      $config_uload_with_ssl = $this->_environment->getConfiguration('c_enable_flash_upload_with_ssl');
      if ( ( $session->issetValue('https')
             and $session->getValue('https') == '1'
             and ( !isset($config_uload_with_ssl)
                   or !$config_uload_with_ssl
                 )
           )
           or
           ( isset($_SERVER['HTTPS'])
             and !empty($_SERVER['HTTPS'])
             and $_SERVER['HTTPS'] != 'off')
             and ( !isset($config_uload_with_ssl)
                   or !$config_uload_with_ssl
                 )
           ) {
#      if(   ($session->issetValue('https') && $session->getValue('https') == '1') ||
#            (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) {
         $use_new_upload = false;
      }
      unset($config_uload_with_ssl);
	  
	  // check operation system
	  $os = $this->_environment->getCurrentOperatingSystem();
	  if($os == 'iPad') {
	  	$use_new_upload = false;
	  }

      if ($use_new_upload){
         // this div holds the list of files, which upload is finished(+checkbox)
         $html .= '<div id="fileFinished"></div>';

         // prepare some values to send to the uploadify script
         global $c_virus_scan;
         global $c_virus_scan_cron;
         $session = $this->_environment->getSessionItem();
         $file_upload_rubric = $this->_environment->getCurrentModule();
         if($session->issetValue($file_upload_rubric . '_add_files')) {
            $file_array = $session->getValue($file_upload_rubric . '_add_files');
         } else {
            $file_array = array();
         }

         if($this->_environment->getCurrentModule() == 'todo' && $this->_environment->getCurrentFunction() == 'detail') {
            $target_module = "step";
         } else if($this->_environment->getCurrentModule() == 'discussion' && $this->_environment->getCurrentFunction() == 'detail') {
            $target_module = "discarticle";
         } else {
            $target_module = $this->_environment->getCurrentModule();
         }

         $scriptData = '';
         $scriptData .= '"cid"               : "' . $this->_environment->getCurrentContextID() . '",';
         $scriptData .= '"mod"               : "ajax",';
         $scriptData .= '"fct"               : "uploadify",';
         $scriptData .= '"c_virus_scan"         : "' . $c_virus_scan . '",';
         $scriptData .= '"c_virus_scan_cron"   : "' . $c_virus_scan_cron . '",';
         $scriptData .= '"file_upload_rubric"   : "' . $target_module . '",';
         $scriptData .= '"SID"               : "' . $session->getSessionID() . '",';
         $scriptData .= '"security_token"      : "' . getToken() . '"';

         /*
          * this object array specifies the uploadify error message translations
          * add more if needed
          *
          * type: uploadify error type
          * text: translation
          */
         $html .='<script type="text/javascript">';
         $html .='   var uploadify_errorLang = [';
         $html .='                              {"type" : "File Size", "text" : "' . $this->_translator->getMessage('ERROR_UPLOADIFY_FILE_SIZE') . '"}';
         $html .='                       ]';
         $html .='</script>';

         // define the buttons
         $selected_lang = $this->_translator->getSelectedLanguage();
         $button_browse = "javascript/jQuery/jquery.uploadify-v2.1.4/button_browse_" . $selected_lang . ".png";
         $button_upload = "javascript/jQuery/jquery.uploadify-v2.1.4/button_upload_" . $selected_lang . ".png";
         $button_abort = "javascript/jQuery/jquery.uploadify-v2.1.4/button_abort_" . $selected_lang . ".png";

         $html .= '<div id="fileQueue"></div>';
         $html .= '<input type="file" name="uploadify" id="uploadify" />';
         $html .= '<a id="uploadify_doUpload" href="javascript:$(\'#uploadify\').uploadifyUpload();"><img src="' . $button_upload . '"></a>&nbsp;';
         $html .= '<a id="uploadify_clearQuery" href="javascript:jQuery(\'#uploadify\').uploadifyClearQueue()"><img src="' . $button_abort . '"></a>';

         $html .= '&nbsp;'.$this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'125',false,'','','','','uploadify_fixSubmitButton');

         $html .='<script type="text/javascript">';
         $html .='$(document).ready(function() {';
         $html .='   $("#uploadify").uploadify({';
         $html .='      "uploader"       : "javascript/jQuery/jquery.uploadify-v2.1.4/uploadify.swf",';
         //$html .='      "script"         : "javascript/jQuery/jquery.uploadify-v2.1.4/uploadify.php",';
         $html .='      "script"       : "'.$this->_environment->getConfiguration('c_single_entry_point').'",';
         $html .='      "method"       : "GET",';
         $html .='      "folder"         : "javascript/jQuery/jquery.uploadify-v2.1.4/uploads",';
         $html .='      "scriptData"    : ({'.$scriptData.'}),';
         $html .='      "multi"          : true,';
         $html .='      "wmode"          : "transparent",';
         $html .='      "buttonImg"      : "' . $button_browse . '",';
         $html .='      "width"          : 160,';
         $html .='      "height"         : 25,';
         $html .='      "sizeLimit"      : '.$val.',';
         //$html .='      "buttonText"     : "'.$this->_translator->getMessage('COMMON_UPLOAD_SEARCH_BUTTON').'",';
         $html .='      "cancelImg"      : "images/commsyicons/16x16/delete.png",';
         $html .='      "onComplete"    : uploadify_onComplete,';
         $html .='      "onAllComplete"    : uploadify_onAllComplete,';
         $html .='      "onError"       : uploadify_onError';
         $html .='   });';

         $html .= 'jQuery(\'input[id="uploadify_fixSubmitButton"]\').attr(\'style\', \'display: none;\');';

         $html .= 'window.setTimeout("check_upload_form()",2000);';
         //$html .= 'check_upload_form()';

         $html .='});';
         $html .='</script>';
      }else{
         if ( !isset($form_element['multi_upload'])
              or !$form_element['multi_upload']
            ) {
            $html .= '<input type="file" name="'.$form_element['name'].'"';
            $html .= ' size="'.$form_element['size'].'"';
            $html .= ' tabindex="'.$this->_count_form_elements.'"';
            $this->_count_form_elements++;
            $html .= '/>';

            if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
               $html .= '&nbsp;'.$this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'125');
            }
         } else {
            $session_item = $this->_environment->getSessionItem();
            $session_id = $session_item->getSessionID();
            $context_item = $this->_environment->getCurrentContextItem();
            $color_array = $context_item->getColorArray();
            $backgroundcolor = hexdec($color_array['boxes_background']);
            $url = 'http://';
            $url .= $_SERVER['HTTP_HOST'];
            global $c_single_entry_point;
            $url .= str_replace($c_single_entry_point,'',$_SERVER['PHP_SELF']);
            $curl_upload = $url.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'upload',array('SID' => $session_id));
            $curl_upload = str_replace('commsy.phpcommsy.php','commsy.php',$curl_upload);
            $curl_end = $url.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$this->_environment->getCurrentParameterArray());
            $curl_end = str_replace('commsy.phpcommsy.php','commsy.php',$curl_end);
            $html .= '<div style="background: #F0F0F0; border: 1px dashed #B0B0B0; width: 600px">'.LF;
            $html .= '<applet name="postlet" code="Main.class" archive="applet/postlet.jar" width="600px" height="150">'.LF;
            $html .= '   <param name = "maxthreads"         value = "5" />'.LF;
            $html .= '   <param name = "language"           value = "'.$this->_environment->getSelectedLanguage().'" />'.LF;
            $html .= '   <param name = "type"               value = "application/x-java-applet;version=1.3.1" />'.LF;
            $html .= '   <param name = "destination"        value = "'.$curl_upload.'" />'.LF;
            $html .= '   <param name = "backgroundcolour"   value = "#F0F0F0" />'.LF;
            $html .= '   <param name = "tableheaderbackgroundcolour" value = "14079989" />'.LF;
            $html .= '   <param name = "tableheadercolour"  value = "0" />'.LF;
            $html .= '   <param name = "warnmessage"        value = "false" />'.LF;
            $html .= '   <param name = "autoupload"         value = "false" />'.LF;
            $html .= '   <param name = "helpbutton"         value = "false" />'.LF;
            #$html .= '   <param name = "fileextensions"     value = "Image Files,jpg,gif,jpeg" />'.LF;
            $html .= '   <param name = "endpage"            value = "'.$curl_end.'" />'.LF;
            #$html .= '   <param name = "helppage"           value = "http://www.postlet.com/help/?thisIsTheDefaultAnyway" />'.LF;
            $html .= '</applet>'.LF;
            $html .= '</div>'.LF;
         }
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
               if($form_element['is_disabled']){
                  $option['is_disabled'] = true;
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
                     if($form_element['is_disabled']){
                        $option['is_disabled'] = true;
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
               if($form_element['is_disabled']){
                  $option['is_disabled'] = true;
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
               if($form_element['is_disabled']){
                  $option['is_disabled'] = true;
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
            $html .= 'jQuery(document).ready(function(){jQuery("#MySortable").sortable();jQuery("#MySortable").disableSelection();});';
//
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
      // jQuery
      //$html .= $form_element['event'] ? " onChange='javascript:document.edit.submit()'" : '';
      $html .= $form_element['event'] ? " id='submit_form'" : '';
      // jQuery
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
         $auth_source_item = $auth_source_manager->getItem($this->_environment->getCurrentUserItem()->getAuthSource());
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
      if ( !empty($form_element['display'])
           and $form_element['display']
         ) {
         $with = 'width:85%; ';
         if ($this->_environment->getCurrentModule() == 'discarticle'){
            $with = 'width:80%; ';
         }
         $html .= '<input style="margin:3px 0px 2px 0px; '.$with.' font-size:12pt; font-weight:bold;" type="text" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form1($form_element['value']).'"';
         $html .= ' maxlength="'.$form_element['maxlength'].'"';
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
    */
   function _getTextAreaAsHTML ($form_element) {
      $html  = '';

      $form_element['value_for_output'] = '';
      $form_element['value_for_output_html'] = '';
      $form_element['value_for_output_html_security'] = '';
      $form_element['value_for_output_html_security_hidden'] = '';
      if ( !empty($form_element['value']) ) {
         $form_element['value_for_output'] = $this->_text_as_form($form_element['value']);
         $form_element['value_for_output_html'] = $this->_text_as_form_for_html_editor($form_element['value']);

         // value translations
         $value = str_replace('<!-- KFC TEXT -->','',$form_element['value_for_output_html']);

         // security KFC (hidden)
         $hidden_value = str_replace('"','COMMSY_QUOT',$value);
         $hidden_value = str_replace('&','COMMSY_AMPERSEND',$hidden_value);
         $form_element['value_for_output_html_security_hidden'] = $hidden_value;
         unset($hidden_value);

         // security KFC
         $values = array();
         preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u',$value,$values);
         if ( !empty($values[1]) ) {
            $hash = $values[1];
            $temp_text = str_replace('<!-- KFC TEXT '.$hash.' -->','',$value);
            global $c_enable_htmltextarea_security;
            if ( isset($c_enable_htmltextarea_security)
                 and !empty($c_enable_htmltextarea_security)
                 and $c_enable_htmltextarea_security
               ) {
               include_once('functions/security_functions.php');
               if ( getSecurityHash($temp_text) != $hash ) {
                  $value = $this->_environment->getTextConverter()->text_as_html_long($temp_text);
                  $value = '<!-- KFC TEXT '.getSecurityHash($value).' -->'.$value.'<!-- KFC TEXT '.getSecurityHash($value).' -->';
               }
            }
         } elseif ( !strstr($value,'<!-- KFC TEXT') ) {
            include_once('functions/security_functions.php');
            $value = '<!-- KFC TEXT '.getSecurityHash($value).' -->'.$value.'<!-- KFC TEXT '.getSecurityHash($value).' -->';
         }

         // this is for migration of texts not insert with an HTML editor
         $value = str_replace("\n\n",'<br/><br/>',$value);
         $form_element['value_for_output_html_security'] = $value;
         unset($value);
      }
      $form_element['tabindex'] = $this->_count_form_elements;
      $this->_count_form_elements++;

      if ( $form_element['with_html_area'] ) {
         include_once('functions/misc_functions.php');
         $html = plugin_hook_output_all('getTextAreaAsHTML',$form_element);
      }

      if ( empty($html) ) {
         $vsize = '';
         $normal = '<textarea style="width:98%" name="'.$form_element['name'].'"';
   #      $normal .= ' cols="'.$form_element['vsize'].'"';
         $normal .= ' rows="'.$form_element['hsize'].'"';
   #      $normal .= ' wrap="'.$form_element['wrap'].'"';
         $normal .= ' tabindex="'.$form_element['tabindex'].'"';
         if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
            $normal .= ' disabled="disabled"';
         }
         $normal .= '>';

         $specialTextArea = false;
         global $c_html_textarea;
         if (isset($c_html_textarea) and $c_html_textarea) {
            $specialTextArea = true;
         }
         $normal .= $form_element['value_for_output'];
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
        if ( !isset($c_html_textarea)
             or !$c_html_textarea
             or !$form_element['with_html_area']
             or !$with_htmltextarea
           ) {
           $html .= $normal;
        } elseif ( $current_browser != 'msie'
                   and $current_browser != 'firefox'
                   and $current_browser != 'netscape'
                   and $current_browser != 'mozilla'
                   and $current_browser != 'camino'
                   and $current_browser != 'opera'
                   and $current_browser != 'safari'
               ) {
            $html .= $normal;
        } else {
           $session = $this->_environment->getSessionItem();
           if ($session->issetValue('javascript')) {
              $javascript = $session->getValue('javascript');
              if ($javascript == 1) {
                 include_once('classes/cs_html_textarea.php');
                 $html_area = new cs_html_textarea();
                 $html .= $html_area->getAsHTML( $form_element['name'],
                                                 $form_element['value_for_output_html'],
                                                 $form_element['hsize']+10,
                                                 $html_status,
                                                 $this->_count_form_elements,
                                                 $vsize
                                               );
                 // hidden field for HTML editor corrections
                 // to check if a post field is a textarea
                 $html .= LF.$this->_getHiddenFieldasHTML(array('name' => $form_element['name'].'_is_textarea', 'value' => '1'));
              } else {
                 $html .= $normal;
              }
           } else {
              $html .= $normal;
           }
         }
      } else {
         $html .= LF.$this->_getHiddenFieldasHTML(array('name' => $form_element['name'].'_is_textarea', 'value' => '1'));
         if ( !empty($form_element['value_for_output_html_security_hidden']) ) {
            $html .= LF.$this->_getHiddenFieldasHTML(array('name' => $form_element['name'].'_fck_hidden', 'value' => $form_element['value_for_output_html_security_hidden']));
         }
      }
      return $html;
   }


   function _getRoomLogoAsHTML ($form_element) {
      $retour = '';
      if (empty($form_element['filename']) and !empty($form_element['value'])){
         $form_element['filename'] = $form_element['value'];
      }
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
            $disc_manager = $this->_environment->getDiscManager();
            if ($disc_manager->existsFile($form_element['filename'])){
               $image_array = getimagesize($disc_manager->getFilePath().$form_element['filename']);
               $pict_width = $image_array[0];
            }
            $retour .= '<img alt="Picture" src="'.$curl.'" style="height:4em;"/>'.LF;
            $check_box_element = array();
            $check_box_element['type'] = 'checkbox';
            $check_box_element['name'] = 'delete_'.$form_element['name'];
            $check_box_element['value'] = '';
            $check_box_element['ischecked'] = false;
            $check_box_element['text'] = $this->_translator->getMessage('LOGO_DELETE_OPTION');
            $retour .= $this->_getCheckBoxAsHTML($check_box_element);
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
            if ( isset($form_element['width']) and !empty($form_element['width']) ){
               $style = ' style="height:'.$form_element['width'].';"';
            }$retour .= '<img alt="Picture" src="'.$curl.'"'.$style.'/>'.LF;
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
                    and ( ( $form_element_array[0]['type'] == 'textarea'
                            and $form_element_array[0]['full_width']
                          )
#                          or ( $form_element_array[0]['type'] == 'subheadline' )
                        )
                  ) {
                  $html .= '      <td class="key" colspan="2" style="width: 100%; ">';
         } elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'explanation'){
                  $html .= '      <td colspan="2" style="width: 100%; padding-top:10px; padding-bottom:10px;">';
         }elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titlefield'){
                  $html .= '      <td class="key" colspan="2" style="width: 100%; "><div id ="form_title">';
         } elseif (isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'titletext'){
                  $html .= '      <td class="infoborder" class="key" colspan="2" style="width: 100%; "><div>';
         } elseif ( isset($form_element_array[0]['type'])
                      and $form_element_array[0]['type'] == 'emptyline'
                  ) {
                  $colspan = 2;
                  if ( !empty($form_element_array[0]['line_right'])
                       and $form_element_array[0]['line_right']
                     ) {
                     $colspan++;
                  }
                  $html .= '      <td class="infoborder" colspan="'.$colspan.'" style="width: 100%; ">';
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
#          and !(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'subheadline')
          and !(isset($form_element_array[0]['type']) and $form_element_array[0]['type'] == 'explanation')
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
      $show_drop_down_in_form = false;
      foreach ($form_element_array as $form_element) {
         if ($form_element['type'] == 'titlefield') {
            $html .= '         '.$this->_getTitleFieldAsHTML($form_element);
            if ($first) {
               $first = false;
            }
         }elseif ($form_element['type'] == 'titletext') {
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
                 $html .= '<div style="padding-left:5px; margin-top:3px; vertical-align:bottom;">';
                 $text = '<div class="bold" style="padding:0px 0px 0px 0px;">'.$form_element['example'].':</div>';
                 $html .='<img id="toggle'.$form_element['name'].'" src="images/more.gif"/>';
                 $html .= $title;
                 $html .= '<div id="creator_information'.$form_element['name'].'">'.LF;
                 $html .= '<div style="padding:0px 0px 5px 0px;">'.LF;
                 $html .= '<div class="form_formatting_checkbox_box">'.LF;
                 $show_drop_down = true;
                 if(isset($form_element['show_drop_down']) and $form_element['show_drop_down']){
                    $show_drop_down_in_form = true;
                 }
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
            }elseif ($form_element['type'] == 'imagebutton') {
               $html .= $this->_getImageButtonAsHTML($form_element);
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
            }elseif ($form_element['type'] == 'explanation') {
               $html .= $this->_getExplanationAsHTML($form_element);
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
                  $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name'],$form_element['width'],$disabled,'',$form_element['font_size'],$form_element['text_after'],$form_element['javascript'],$form_element['element_id']);
               } else {
                  $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['name'],'',$disabled,'',$form_element['font_size'],$form_element['text_after'],$form_element['javascript'],$form_element['element_id']);
               }
            } elseif ($form_element['type'] == 'text') {
               $html .= '         '.$this->_getTextAsHTML($form_element);
            } elseif ($form_element['type'] == 'color_table') {
               $html .= '         '.$this->_getColorTableAsHTML();
            } elseif ($form_element['type'] == 'image') {
               $html .= '         '.$this->_getImageAsHTML($form_element);
            }elseif ($form_element['type'] == 'room_logo') {
               $html .= '         '.$this->_getRoomLogoAsHTML($form_element);
            }
            if ( isset($form_element['combine']) and $form_element['combine'] == 'vertical') {
               $html .= '</div><div style="padding-top: 3px;">';
            }

            /** TODO: remove this if not longer used ***********************/
            // add info text if browsing with https
            $config_uload_with_ssl = $this->_environment->getConfiguration('c_enable_flash_upload_with_ssl');
            if ( $form_element['type'] == 'file'
                 and ( !isset($config_uload_with_ssl)
                       or !$config_uload_with_ssl
                     )
               ) {
#            if($form_element['type'] == 'file') {
               $session = $this->_environment->getSessionItem();
               if(   ($session->issetValue('https') && $session->getValue('https') == '1') ||
                     (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) {
                  //$html .= LF;
                  $html .= '<span class="key">' . $this->_translator->getMessage('COMMON_UPLOAD_OLD_HTTPS') . '</span><br>' . LF;
               }
            }

            /***************************************************************/
         }
      }
      if ($show_drop_down){
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         if($show_drop_down_in_form){
            $html .='<script type="text/javascript">initTextFormatingInformation("'.$drop_down_name.'",true)</script>';
         } else {
            $html .='<script type="text/javascript">initTextFormatingInformation("'.$drop_down_name.'",false)</script>';
         }
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
      $toggle_id = rand(0,1000000);
      if ( isset($form_element['help_text']) ){
         $show_text = $form_element['help_text'];
      }
      global $c_html_textarea;
      $html = '';
      $item = $this->_environment->getCurrentContextItem();
      $with_htmltextarea = $item->withHtmlTextArea();
      include_once('functions/misc_functions.php');
      if ( plugin_hook_method_active('getTextAreaAsHTML') ) {
         $with_htmltextarea = true;
      }
      $text = '';
      if ( !isset($c_html_textarea)
           or !$c_html_textarea
           or !$form_element['with_html_area']
           or !$with_htmltextarea
         ) {
         $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
         $html .= '<div style="padding-top:5px;">';
         $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
         $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
         $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
         $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
         
         // plugins
         $plugin_text = plugin_hook_output_all('getTextFormatingInformationAsHTML','',BRLF);
         if ( !empty($plugin_text) ) {
            $text .= $plugin_text;
         }
         
         //$html .='<img id="toggle'.$item->getItemID().'" src="images/more.gif"/>';
         $html .='<img id="toggle'.$toggle_id.'" src="images/more.gif"/>';
         $html .= $title;
         //$html .= '<div id="creator_information'.$item->getItemID().'">'.LF;
         $html .= '<div id="creator_information'.$toggle_id.'">'.LF;
         $html .= '<div style="padding:2px;">'.LF;
         $html .= '<div id="form_formatting_box" style="width:97%">'.LF;
         $html .= $text;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }else{
         $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_SHORT');
         $html .= '<div style="padding-top:0px;">';
         $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
         $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
         
         // plugins
         $plugin_text = plugin_hook_output_all('getTextFormatingInformationAsHTML','',BRLF);
         if ( !empty($plugin_text) ) {
            $text .= $plugin_text;
         }
         
         //$html .='<img id="toggle'.$item->getItemID().'" src="images/more.gif"/>';
         $html .='<img id="toggle'.$toggle_id.'" src="images/more.gif"/>';
         $html .= $title;
         //$html .= '<div id="creator_information'.$item->getItemID().'">'.LF;
         $html .= '<div id="creator_information'.$toggle_id.'">'.LF;
         $html .= '<div style="padding:2px;">'.LF;
         $html .= '<div id="form_formatting_box" style="width:97%">'.LF;
         $html .= $text;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      //$html .='<script type="text/javascript">initTextFormatingInformation("'.$item->getItemID().'",false);</script>';
      $html .='<script type="text/javascript">initTextFormatingInformation("'.$toggle_id.'",false);</script>';
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

    function getSearchText (){
       if (empty($this->_search_text)){
        $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_ROOM');
       }
       return $this->_search_text;
    }


  function _getSearchAsHTML () {
     $html  = '';
     $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="get" name="indexform">'.LF;
     $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
     $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
     $html .= '   <input type="hidden" name="SID" value="'.$this->_environment->getSessionItem()->getSessionID().'"/>'.LF;
     $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
     $html .= '   <input type="hidden" name="selrubric" value="'.$this->_environment->getCurrentModule().'"/>'.LF;
     $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:220px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     } else {
        $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     }
     $html .= '</form>';
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


         $temp_array = array();
         foreach ($form_element_array as $form_element) {
            $temp_array[] = $form_element;
         }


         $html .='<div style="width:100%;">'.LF;
         $html .='<div style="height:30px;">'.LF;

         if ( $this->_environment->getCurrentFunction() == 'mail' ) {
            $html .='<div class="content_display_width" style="width:100%">'.LF;
            $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('COMMON_MAIL_FORM_TITLE').'</h2>';
         } else {
            $html .= '<div style="float:right; width:28%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
            $html .= $this->_getSearchAsHTML();
            $html .= '</div>'.LF;
            $html .='<div class="content_display_width" style="width:70%">'.LF;
            $temp_mod_func = mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') . '_' . mb_strtoupper($this->_environment->getCurrentFunction(), 'UTF-8');
            $tempMessage = "";
            switch( $temp_mod_func  )
            {
               case 'ANNOUNCEMENT_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_ANNOUNCEMENT_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_ANNOUNCEMENT_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_ANNOUNCEMENT_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/announcement.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/announcement.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'MATERIAL_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_MATERIAL_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_MATERIAL_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_MATERIAL_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/material.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/material.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'DATE_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_DATE_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_DATE_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_DATE_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/date.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/date.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'TODO_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_TODO_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_TODO_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_TODO_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/todo.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/todo.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'GROUP_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_GROUP_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_GROUP_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_GROUP_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'USER_EDIT':
                  $tempMessage = $this->_item->getFullname();
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/user.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/user.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'TOPIC_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_TOPIC_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_TOPIC_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_TOPIC_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/topic.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/topic.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'INSTITUTION_EDIT':
                  $tempMessage = $this->_translator->getMessage('COMMON_INSTITUTION_EDIT');
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'DISCUSSION_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_DISCUSSION_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_DISCUSSION_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_DISCUSSION_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/discussion.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/discussion.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'DISCARTICLE_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_DISCARTICLE_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_DISCARTICLE_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_DISCARTICLE_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/discussion.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/discussion.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'SECTION_EDIT':
                  //$tempMessage = $this->_translator->getMessage('COMMON_SECTION_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_SECTION_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_SECTION_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/material.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/material.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'ACCOUNT_PASSWORD':      // Password ändern
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_PASSWORD_FORM_TITLE');
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'ACCOUNT_PREFERENCES':   // Benutzer, Einstellungen ändern
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_PREFERENCES_FORM_TITLE');
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'ACCOUNT_STATUS':        // Status ändern (Portal)
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_STATUS_FORM_TITLE');
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
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
               case 'CONFIGURATION_COLOR':   // Farbkonfiguration
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_COLOR_FORM_TITLE');
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
               case 'PROJECT_EDIT': // Raumeröffnungen (Portal)
                  //$tempMessage = $this->_translator->getMessage('COMMON_ROOM_EDIT_FORM_TITLE');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_ROOM_EDIT_FORM_TITLE');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_ROOM_EDIT_FORM_TITLE');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
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
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/material.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/material.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'ANNOTATION_EDIT':      //
                  $tempMessage = $this->_translator->getMessage('COMMON_ANNOTATION_EDIT');
                  break;
               case 'STEP_EDIT':      //
                  //$tempMessage = $this->_translator->getMessage('COMMON_STEP_EDIT');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_STEP_EDIT');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_STEP_EDIT');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/todo.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/todo.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'ACCOUNT_EDIT':      //
                  $tempMessage = $this->_translator->getMessage('ACCOUNT_EDIT');
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               case 'MYROOM_EDIT': // room edit in myroom
                  //$tempMessage = $this->_translator->getMessage('COMMON_ROOM_EDIT_FORM_TITLE');
                  if($this->current_iid != 'NEW'){
                     $tempMessage = $this->_translator->getMessage('COMMON_ROOM_EDIT_FORM_TITLE');
                  } else {
                     $tempMessage = $this->_translator->getMessage('COMMON_NEW_ROOM_EDIT_FORM_TITLE');
                  }
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  } else {
                     $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
                  }
                  break;
               default:                      // "Bitte Messagetag-Fehler melden ..."
                  $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_form_view(".__LINE__.") ";
                  break;
            }
            $html .= '<h2 class="pagetitle">' . $tempMessage . '</h2>';
         }
         $html .='</div>'.LF;
         $html .='<div style="width:100%; clear:both;">'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
#         $html .= '</div>';

      $html .= '<form id="edit" style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="edit">'."\n";

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
      $html .='<div style="float:right; width:28%; margin-top:0px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      if ($user->isUser() and $funct !='info_text_form_edit' and $funct !='info_text_edit'){
         $html .='<div id="commsy_panels_form" style="width:250px;">'.LF;
         $html .= '<input id="right_box_option" type="hidden" style="font-size:8pt;" name="right_box_option" value=""/>';
         $html .= ''.LF;
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
            if ($room->withBuzzwords()
                and (
                   $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                   or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                   or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                   or ($this->_environment->getCurrentModule() == CS_MATERIAL_TYPE AND $this->_environment->getCurrentFunction() == 'edit')
                   or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                )
            ){
               $html .= $this->_getBuzzwordBoxAsHTML();
            }
            if ($room->withTags()
                and (
                   $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                   or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                   or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                   or ($this->_environment->getCurrentModule() == CS_MATERIAL_TYPE AND $this->_environment->getCurrentFunction() == 'edit')
                   or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                )
            ){
               $session_item = $this->_environment->getSessionItem();
               $with_javascript = false;
               if($session_item->issetValue('javascript')){
                  if($session_item->getValue('javascript') == "1"){
                     $with_javascript = true;
                  }
               }
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                 $with_javascript = false;
              }
               // UMSTELLUNG MUSEUM
               if($with_javascript){
                  $html .= $this->_getTagBoxAsHTMLWithJavaScript();
               } else {
                  $html .= $this->_getTagBoxAsHTML();
               }
            }
            if ($this->_environment->getCurrentModule() != 'account' and
                $this->_environment->getCurrentModule() != CS_PROJECT_TYPE and
                $this->_environment->getCurrentModule() != CS_COMMUNITY_TYPE and
                $this->_environment->getCurrentModule() != CS_DISCARTICLE_TYPE and
                $this->_environment->getCurrentModule() != CS_STEP_TYPE and
                $this->_environment->getCurrentModule() != CS_SECTION_TYPE and
                $this->_environment->getCurrentModule() != CS_MYROOM_TYPE and
                $this->_environment->getCurrentModule() != CS_TAG_TYPE and
                $room->withNetnavigation() and
                $this->_environment->getCurrentFunction() == 'edit'

            ){
               $html .= $this->_getAllLinkedItemsAsHTML($netnavigation_array);
            }
         }
         $rubric_info_array = $room->getUsageInfoFormArray();
         if (!is_array($rubric_info_array)) {
            $rubric_info_array = array();
         }
         $room = $this->_environment->getCurrentContextItem();
         $info_text = $room->getUsageInfoTextForRubricForm($this->_environment->getCurrentModule());
         if (!(in_array($this->_environment->getCurrentModule().'_no', $rubric_info_array)) and
             !strstr($info_text, $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')) and
             !strstr($info_text, $this->_translator->getMessage('USAGE_INFO_COMING_SOON')) and
             !empty($info_text)

         ){
            if (!$this->_environment->inPortal()){
               $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
               $html .= $this->_getRubricFormInfoAsHTML($this->_environment->getCurrentModule());
               $html .='</div>'.LF;
            }
         }
         $html .='</div>'.LF;
      }
      $html .= '</div>'.LF;

      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:100%; ';
      }else{
         $width = 'width:70%; ';
      }
      $html .='<div style="'.$width.'margin-top:5px; vertical-align:bottom;">'.LF;
      $html .= '<!-- BEGIN OF FORM-VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type'])
              and $form_element['type'] == 'titlefield'
              and $form_element['display']
            ) {
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
                  $text = '   <tr>'.LF;
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
         $html .= '<span class="required" style="font-size:16pt;">*</span> <span class="key" style="font-weight:normal;">'.$this->_translator->getMessage('COMMON_MANDATORY_FIELDS').'</span> '.$buttonbartext;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
      }
      if ($this->_environment->getCurrentModule() != 'tag' and $this->_environment->getCurrentModule() != 'buzzwords' ){
         $html .= '</div>'.LF;
      }
      $html .= '</form>'.BRLF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both; width:100%;">&nbsp;'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }





   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=10, $maxsize=20, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getBuzzwordColorLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=30, $maxsize=70, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }



   function _getBuzzwordBoxAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      $buzzword_ids = array();
      if ($session->issetValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids')){
         $buzzword_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
      }
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->reset();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_list = $buzzword_manager->getItemList($buzzword_ids);
      $buzzword_entry = $buzzword_list->getFirst();
      $item_id_array = array();
      while($buzzword_entry){
         $item_id_array[] = $buzzword_entry->getItemID();
         $buzzword_entry = $buzzword_list->getNext();
      }
      if ( isset($item_id_array[0]) ){
         $links_manager = $this->_environment->getLinkManager();
         $count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array,'buzzword');
      }
      $count_buzzword_ids = count($buzzword_ids);
      $html  = '';
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
        $html_text = '* ';
      }
      $html = '<div class="right_box" style="margin-bottom:1px;">'.LF;
      $color = $current_context->getColorArray();
      if ($error_display){
         $html .= '<div class="right_box_title" style="color:'.$color['warning'].';">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_BUZZWORDS').' ('.$count_buzzword_ids.')</div>';
      }else{
         $html .= '<div class="right_box_title">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_BUZZWORDS').' ('.$count_buzzword_ids.')</div>';
      }
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<div>'.LF;
      if ($buzzword_list ->isEmpty()) {
         $html .= '   <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
      }else{
         $buzzword_entry = $buzzword_list->getFirst();
         while($buzzword_entry){
            $count = 0;
            if ( isset($count_array[$buzzword_entry->getItemID()]) ){
                $count = $count_array[$buzzword_entry->getItemID()];
            }
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword_entry->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword_entry->getName()).LF;
            $title .= '</span> ';
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $title,$title).LF;
           $buzzword_entry = $buzzword_list->getNext();
         }
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      if ($session->issetValue('javascript')) {
         $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
            $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH').'</a>'.LF;
         }else{
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH').'"/>';
         }
      }else{
         $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH').'"/>';
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      unset($current_context);
      return $html;
   }


   function getTagColorLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=40, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function _getTagBoxAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      $tag_ids = array();
      if ($session->issetValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids')){
         $tag_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
      }
      $count_tag_ids = count($tag_ids);
      $tag_manager = $this->_environment->getTagManager();
      $tag_manager->reset();
      $tag_manager->setContextLimit($this->_environment->getCurrentContextID());
      $tag_list = $tag_manager->getItemList($tag_ids);
      $tag_entry = $tag_list->getFirst();
      $item_id_array = array();
      while($tag_entry){
         $item_id_array[] = $tag_entry->getItemID();
         $tag_entry = $tag_list->getNext();
      }
      $html  = '';
      $html .= '<div style="margin-bottom:1px;" class="right_box">'.LF;
      $error_display = false;
      if ( isset($this->_error_array) and !empty($this->_error_array) ){
         foreach ($this->_error_array as $error){
            if ($error == $this->_translator->getMessage('COMMON_ERROR_TAG_ENTRY')){
               $error_display = true;
            }
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $html_text = '';
      if ($current_context->isTagMandatory()){
        $html_text = '* ';
      }
      $color = $current_context->getColorArray();
      if ($error_display){
         $html .= '<div class="right_box_title" style="color:'.$color['warning'].';">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_tag_ids.')</div>';
      }else{
         $html .= '<div class="right_box_title">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_tag_ids.')</div>';
      }
      $html .= '<div class="right_box_main" >'.LF;

      $text = '';
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      $tag_manager = $this->_environment->getTagManager();
      $tag_item = $tag_list->getFirst();
      if ( isset ($tag_item) ){
         $params = $this->_environment->getCurrentParameterArray();
         while( $tag_item ){
            $text .= '<div style="margin-bottom:5px;">';
            $count_all = 1;
            $shown_tag_array = $tag2tag_manager->getFatherItemIDArray($tag_item->getItemID());
            $i = 1;
            if( !empty($shown_tag_array) ) {
               $count_all = count($shown_tag_array);
               $shown_tag_array = array_reverse($shown_tag_array);
               foreach( $shown_tag_array as $shown_tag ){
                  $father_tag_item = $tag_manager->getItem($shown_tag);
                  $count = $count_all - $i + 1;
                  $ebene = $i-1;
                  $font_size = round(13 - (($count*0.2)+$count));
                  $font_weight = 'normal';
                  $font_style = 'normal';
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
                  $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
                  if (($ebene*15) <= 30){
                     $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
                  }else{
                     $text .= '<div style="padding-left:40px; color:'.$color.'; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
                  }
                  $params['seltag'] = 'yes';
                  if ( isset($father_tag_item) ) {
                     $params['seltag_'.($count_all-$i)] = $father_tag_item->getItemID();
                  }
                  $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:'.$color.'"').LF;
                  $text .= '- '.$title_link;
                  $text .= '</div>';
                  $i++;
               }
            }
            $params['seltag'] = 'yes';
            $params['seltag_'.($count_all-1)] = $tag_item->getItemID();
            $count = $count_all - $i + 1;
            $ebene = $i-1;
            $font_size = 13;
            $font_weight = 'normal';
            $font_style = 'normal';
            $font_color = 20 + $this->getTagColorLogarithmic($count);
            $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             'index',
                             $params,
                             $this->_text_as_html_short($tag_item->getTitle()),
                             $this->_text_as_html_short($tag_item->getTitle()),
                             '',
                             '',
                             '',
                             '',
                             '',
                             'style="color:'.$color.'"').LF;
            $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
            $text .= '- '.$title_link;
            $text .= '</div>';
            $text .= '</div>';
            $tag_item = $tag_list->getNext();
         }

      }
      if ( empty($text) ){
         $html .= '   <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
      }else{
         $html .= $text;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      if ($session->issetValue('javascript')) {
         $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
            $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'</a>'.LF;
         }else{
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'"/>';
         }
      }else{
         $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'"/>';
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }

   function _getTagBoxAsHTMLWithJavascript(){
      // MUSEUM
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      $tag_ids = array();
      if ($session->issetValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids')){
         $tag_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
      }
      $count_tag_ids = count($tag_ids);
      $tag_manager = $this->_environment->getTagManager();
      $tag_manager->reset();
      $tag_manager->setContextLimit($this->_environment->getCurrentContextID());
      $tag_list = $tag_manager->getItemList($tag_ids);
      $tag_entry = $tag_list->getFirst();
      $item_id_array = array();
      while($tag_entry){
         $item_id_array[] = $tag_entry->getItemID();
         $tag_entry = $tag_list->getNext();
      }
      $html  = '';
      $html .= '<div style="margin-bottom:1px;" class="right_box">'.LF;
      $error_display = false;
      if ( isset($this->_error_array) and !empty($this->_error_array) ){
         foreach ($this->_error_array as $error){
            if ($error == $this->_translator->getMessage('COMMON_ERROR_TAG_ENTRY')){
               $error_display = true;
            }
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $html_text = '';
      if ($current_context->isTagMandatory()){
        $html_text = '* ';
      }
      $color = $current_context->getColorArray();
      if ($error_display){
         $html .= '<div class="right_box_title" style="color:'.$color['warning'].';">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_tag_ids.')</div>';
      }else{
         $html .= '<div class="right_box_title">'.$html_text.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_tag_ids.')</div>';
      }
      $html .= '<div class="right_box_main" >'.LF;

      $text = '';
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      $tag_manager = $this->_environment->getTagManager();
      $tag_item = $tag_list->getFirst();
      if ( isset ($tag_item) ){
         $params = $this->_environment->getCurrentParameterArray();
         $text .= '<div id="tag_tree"><ul>';
         while( $tag_item ){
            $count_all = 1;
            $shown_tag_array = $tag2tag_manager->getFatherItemIDArray($tag_item->getItemID());
            $i = 1;
            if( !empty($shown_tag_array) ) {
               $count_all = count($shown_tag_array);
               $shown_tag_array = array_reverse($shown_tag_array);
               foreach( $shown_tag_array as $shown_tag ){
                  $father_tag_item = $tag_manager->getItem($shown_tag);
                  $count = $count_all - $i + 1;
                  $ebene = $i-1;
                  $font_size = round(13 - (($count*0.2)+$count));
                  $font_weight = 'normal';
                  $font_style = 'normal';
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
                  #$color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
                  $color = '#545454';
                  $params['seltag'] = 'yes';
                  if ( isset($father_tag_item) ) {
                     $params['seltag_'.($count_all-$i)] = $father_tag_item->getItemID();
                  }
                  $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:'.$color.'"').LF;
                  $link = curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params);
                  $text .= '<li id="' . $father_tag_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:'.$color.'; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">'.LF;
                  $text .= $title_link;
                  $text .= '<ul>'.LF;
                  $i++;
               }
            }
            $params['seltag'] = 'yes';
            $params['seltag_'.($count_all-1)] = $tag_item->getItemID();
            $count = $count_all - $i + 1;
            $ebene = $i-1;
            $font_size = 13;
            $font_weight = 'normal';
            $font_style = 'normal';
            $font_color = 20 + $this->getTagColorLogarithmic($count);
            $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             'index',
                             $params,
                             $this->_text_as_html_short($tag_item->getTitle()),
                             $this->_text_as_html_short($tag_item->getTitle()),
                             '',
                             '',
                             '',
                             '',
                             '',
                             'style="color:#000000; font-weight:bold;"').LF;
            $link = curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params);
            $text .= '<li id="' . $tag_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:bold;">'.LF;
            $text .= $title_link;
            for ($index = 1; $index < $i; $index++) {
               $text .= '</li></ul>'.LF;
            }
            $tag_item = $tag_list->getNext();
         }
         $text .= '</li></ul></div>';
      }
      if ( empty($text) ){
         $html .= '   <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
      }else{
         $html .= $text;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      if ($session->issetValue('javascript')) {
         $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
            $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'</a>'.LF;
         }else{
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'"/>';
         }
      }else{
         $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'"/>';
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }

   function _getAllLinkedItemsAsHTML ($spaces=0) {
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $html .= '<div style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $connections = $this->getRubricConnections();

      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $session = $this->_environment->getSessionItem();
      $attached_ids = array();
      if ($session->issetValue('cid'.$this->_environment->getCurrentContextID().'_linked_items_index_selected_ids')){
         $attached_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_linked_items_index_selected_ids');
      }
      $count_linked_items = count($attached_ids);
      $item_manager = $this->_environment->getItemManager();
      $linked_items = $item_manager->getItemList($attached_ids);
      if ($this->_environment->inCommunityRoom() and $this->_environment->getCurrentModule() == CS_USER_TYPE){
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_INSTITUTIONS').' ('.$count_linked_items.')</div>';
      }elseif($this->_environment->getCurrentModule() == CS_USER_TYPE){
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_GROUPS').' ('.$count_linked_items.')</div>';
      }else{
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_ENTRIES').' ('.$count_linked_items.')</div>';
      }
      $html .='     <div class="right_box_main">     '.LF;
      if ($linked_items->isEmpty()) {
         $html .= '  <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'&nbsp;</div>'.LF;
      } else {
         $html .='     <ul style="list-style-type: circle; font-size:8pt; list-style-position:inside; margin:0px; padding:0px;">'.LF;
         $linked_item = $linked_items->getFirst();
         while($linked_item){
            $fragment = '';    // there is no anchor defined by default
            $type = $linked_item->getItemType();
            if ($type == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($linked_item->getItemID());
               $type = $label_item->getLabelType();
            }
            $manager = $this->_environment->getManager($type);
            $item = $manager->getItem($linked_item->getItemID());
            $text = getRubricMessageTageName($type);
            $text .= ' - '.$item->getTitle();
            $html .= '   <li  style="padding-left:5px; list-style-type:none;">';

            $type = $item->getType();
            if ($type =='label'){
               $type = $item->getLabelType();
            }
            switch ( mb_strtoupper($type, 'UTF-8') )
               {
                  case 'ANNOUNCEMENT':
                     $img = 'images/commsyicons/netnavigation/announcement.png';
                     break;
                  case 'DATE':
                     $img = 'images/commsyicons/netnavigation/date.png';
                     break;
                  case 'DISCUSSION':
                     $img = 'images/commsyicons/netnavigation/discussion.png';
                     break;
                  case 'GROUP':
                     $img = 'images/commsyicons/netnavigation/group.png';
                     break;
                  case 'INSTITUTION':
                     $img = '';
                     break;
                  case 'MATERIAL':
                     $img = 'images/commsyicons/netnavigation/material.png';
                     break;
                  case 'PROJECT':
                     $img = '';
                     break;
                  case 'TODO':
                     $img = 'images/commsyicons/netnavigation/todo.png';
                     break;
                  case 'TOPIC':
                     $img = 'images/commsyicons/netnavigation/topic.png';
                     break;
                  case 'USER':
                     $img = 'images/commsyicons/netnavigation/user.png';
                     break;
                  default:
                     $img = '';
                     break;
               }
            $html .= '<img src="' . $img . '" style="padding-right:3px;" title="' . $text . '"/>';

            if ($type == CS_USER_TYPE){
               $html .= '<a title="'.$text.'">'.$this->_text_as_html_short(chunkText($item->getFullName(),35)).'</a>';
            }else{
               if($item->isNotActivated() and !($item->getCreatorID() == $current_user->getItemID() or $current_user->isModerator())){
                     $html .= '<a title="'.$text.'" class="disabled">'.$this->_text_as_html_short(chunkText($item->getTitle(),35)).'</a>';
               } else {
                  $html .= '<a title="'.$text.'">'.$this->_text_as_html_short(chunkText($item->getTitle(),35)).'</a>';
               }
            }
            $html .= '</li>'.LF;
            $linked_item = $linked_items->getNext();
         }
         $html .= '</ul>'.LF;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $params = $this->_environment->getCurrentParameterArray();

      if ($session->issetValue('javascript')) {
         $javascript = $session->getValue('javascript');
         if ($javascript == 1) {
            if ($this->_environment->inCommunityRoom() and $this->_environment->getCurrentModule() == CS_USER_TYPE){
               $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_INSTITUTION_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_INSTITUTION_ATTACH').'</a>'.LF;
            }elseif($this->_environment->getCurrentModule() == CS_USER_TYPE){
               $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_GROUP_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_GROUP_ATTACH').'</a>'.LF;
            }else{
               $html .= '<a href="javascript:right_box_send(\'edit\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'\');">'.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'</a>'.LF;
            }
         }else{
            if ($this->_environment->inCommunityRoom() and $this->_environment->getCurrentModule() == CS_USER_TYPE){
               $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_INSTITUTION_ATTACH').'"/>';
            }elseif($this->_environment->getCurrentModule() == CS_USER_TYPE){
               $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_GROUP_ATTACH').'"/>';
            }else{
               $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'"/>';
            }
         }
      }else{
         if ($this->_environment->inCommunityRoom() and $this->_environment->getCurrentModule() == CS_USER_TYPE){
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_INSTITUTION_ATTACH').'"/>';
         }elseif($this->_environment->getCurrentModule() == CS_USER_TYPE){
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_GROUP_ATTACH').'"/>';
         }else{
            $html .= '<input id="right_box_option" type="submit" style="font-size:8pt;" name="right_box_option" value="'.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'"/>';
         }
      }


      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .='      </div>';
      return $html;
   }

  function _getRubricFormInfoAsHTML($act_rubric){
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubricForm($act_rubric);
      $html='';
      $html .= '<div style="margin-bottom:1px;">'.LF;
      $html .= '<div style="position:relative; top:12px;">'.LF;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<img src="images/commsyicons_msie6/usage_info_3.gif"/>';
      } else {
         $html .= '<img src="images/commsyicons/usage_info_3.png"/>';
      }
      $html .= '</div>'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_text_as_html_short($room->getUsageInfoHeaderForRubricForm($act_rubric)).'</div>';
      $html .= '<div class="usage_info">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      if (strstr($info_text, 'COMMON_MESSAGETAG_ERROR')
          or strstr($info_text, $this->_translator->getMessage('USAGE_INFO_COMING_SOON'))
      ){
         $html = '';
      }
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
            global $c_autosave_mode;
            $html .= '         var dispMode = '.$c_autosave_mode.';'.LF;
            global $c_autosave_limit;
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
            // jQuery
            //$html .= '           document.edit.elements["'.$this->getFocusElementOnLoad().'"].focus();'.LF;
            $html .= '           jQuery("input[name=\''.$this->getFocusElementOnLoad().'\'], edit").focus();'.LF;
            // jQuery
         } elseif ( $this->getFocusElementAnchor() != '' ) {
            // jQuery todo
            $html .= '           location.hash="'.$this->getFocusElementAnchor().'";'.LF;
            // jQuery todo
         } else {
            // jQuery
            //$html .= '           document.edit.elements["'.$this->_getFirstInputFieldName().'"].focus();'.LF;
            $html .= '           jQuery("input[name=\''.$this->_getFirstInputFieldName().'\'], edit").focus();'.LF;
            // jQuery
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
      global $class_factory;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
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