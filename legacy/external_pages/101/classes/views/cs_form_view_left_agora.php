<?PHP
/** include upper class of the form view
 */
global $symfonyContainer;
$environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
$classFactory = $environment->getClassFactory();
$classFactory->includeClass(FORM_LEFT_VIEW);

// TODO: remove all code from `cs_form_view_left` that hasn't been overridden

/** Overridden to implement a form view for the custom AGORA portal theme.
 * @author CommSy Development Group
 */
class cs_form_view_left_agora extends cs_form_view_left {

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

  /**
   * string - adds description infos if true
   */
   var $_description_text ='';

   var $_form_name = '';

    /** The only available constructor, initial values for internal variables.
     *
     * @param array params parameters in an array of this class
     */
    function __construct($params)
    {
        parent::__construct($params);
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

   public function setFormName ( $value ) {
      $this->_form_name = $value;
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

   /** set form form the form view
    * this method sets the form for the form view
    *
    * @param array value form elements
    */
   function setDescription ($description_text) {
      $this->_description = $description_text;
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
      $html .= '<!-- BEGIN OF FORM-ELEMENT: headline -->'."\n";

      $html .= '   <div class="form_view_plain_headline">'."\n";

      if (!empty($form_element['right'])) {
         $html .= '      <table border="0" cellspacing="0" cellspacing="0" width="100%" summary="Layout">'."\n";
         $html .= '         <tr>'."\n";
         $html .= '            <td class="view_title">'."\n";
         $html .= '               <b>'.$this->_text_as_html_short($form_element['label']).'</b>'."\n";
         if (!empty($form_element['description'])) {
             $html .= '               <span class="small">('.$this->_text_as_html_short($form_element['description']).')</span>'."\n";
         }
         $html .= '            </td>'."\n";
         if (!empty($form_element['right'])) {
             $html .= '            <td class="form_actions">'.$this->_text_as_html_short($form_element['right']).'</td>'."\n";
         }
         $html .= '         </tr>'."\n";
         $html .= '      </table>'."\n";
      } else {
       $html .= '<span class="personal" style="font-weight: bold;">'.LF;
         $html .= '      '.$form_element['label'].LF;
       $html .= '</span>'.LF;
         if (!empty($form_element['description'])) {
             $html .= '      <span class="small">('.$this->_text_as_html_short($form_element['description']).')</span>'."\n";
         }
      }
      $html .= '   </div>'."\n";
      if (!empty($this->_description)){
         $html .= '            </td>'.LF;
         $html .= '         </tr>'.LF;
         $html .= '         <tr>'.LF;
         $html .= '            <td>'.LF;
      }
      $html .= '<!-- END OF FORM-ELEMENT: headline -->'."\n";
      return $html;
   }

   /** get textline as HTML - internal, do not use
    * this method returns a string contains a textline in HMTL-Code
    *
    * @param array value form element: textline, see class cs_form
    *
    * @return string textline as HMTL
    */

   function _getTextLineAsHTML ($form_element) {
      $html  = '';
      if (!empty($form_element['value'])) {
         if ($form_element['isbold']) {
            $html .= '<span class="bold">'.$this->_text_as_html_short($form_element['value']).'</span>';
         } else {
            $html .= $this->_text_as_html_short($form_element['value']);
         }
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '         &nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
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
   function _getButtonAsHTML ($button_text, $button_name,$width='') {
      $html  = '';
      $html .= '<input type="submit" name="'.$button_name.'"';
      $html .= ' value="'.$this->_text_as_html_short($button_text).'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      if (!empty($width)){
         $text = ' width:'.$width.'em;';
      }else{
         $text ='';
      }
      $html .= ' style=" font-size:8pt; margin-top: 2px;'.$text.'"';
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
         $html .= '   '.$this->_getButtonAsHTML($form_element['labelSave'],$form_element['name'],$form_element['firstWidth']).LF;
      }
      if (!empty($form_element['labelSecondSave'])) {
         $html .= '   &nbsp;'.$this->_getButtonAsHTML($form_element['labelSecondSave'],$form_element['name']).LF;
      }
      if (!empty($form_element['labelCancel'])) {
         $html .= '   &nbsp;'.$this->_getButtonAsHTML($form_element['labelCancel'],$form_element['name'],$form_element['secondWidth']).LF;
      }
      if (!empty($form_element['labelDelete'])) {
         $html .= '                   '.$this->_getButtonAsHTML($form_element['labelDelete'],$form_element['name']).'&nbsp;'.LF;
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
      $html .= '&nbsp;'."\n";
      return $html;
   }



   /** get datetimefield as HTML - internal, do not use
    * this method returns a string contains a datetimefield in HMTL-Code
    *
    * @param array value form element: datetimefield, see class cs_form
    *
    * @return string datetimefield as HMTL
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
         $form_element['firstLabel'] = '<span class="bold">'.$form_element['firstLabel'].'</span>';
      }
      $html .= '         '.$this->_text_as_html_short($form_element['firstLabel']).'&nbsp;'.$this->_getTextFieldAsHTML($textfield);

      if ($form_element['horizontal']) {
         $html .= '         &nbsp;&nbsp;'."\n";
      } else {
         $html .= '<br />'."\n";
      }
      $textfield['name']  = $form_element['secondName'];
      $textfield['size']  = $form_element['secondFieldSize'];
      $textfield['maxlength']  = $form_element['secondFieldMaxLength'];
      $textfield['value'] = next($form_element['value']);
      if ($form_element['isSecondMandatory']) {
         $form_element['secondLabel'] .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
      }
      if (!empty($form_element['failure_element']) and in_array('1',$form_element['failure_element'])) {
         $form_element['secondLabel'] = '<span class="bold">'.$form_element['secondLabel'].'</span>';
      }
      if (!empty($form_element['second_field_type']) and $form_element['second_field_type'] == 'password') {
         $html .= '         '.$this->_text_as_html_short($form_element['secondLabel']).'&nbsp;'.$this->_getPasswordAsHTML($textfield);
      } else {
         $html .= '         '.$this->_text_as_html_short($form_element['secondLabel']).'&nbsp;'.$this->_getTextFieldAsHTML($textfield);
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
      $first = true;
      while ($option) {
         if ($first) {
            $first = false;
         } elseif (!$form_element['horizontal'] and !$first) {
            $html .= '<br />'."\n";
         }
         $html .= '         <input style="font-size:8pt;" type="radio" name="'.$form_element['name'].'"';
         $html .= ' value="'.$this->_text_as_form($option['value']).'"';
         if ($form_element['checked'] == $option['value'] or $form_element['checked'] == $option['text']) {
            $html .= ' checked';
         }
         $html .= ' tabindex="'.$this->_count_form_elements.'"';
         $html .= '/>'.$this->_text_as_html_short($option['text']);
         $html .= LF;
         $option = next($options);
      }
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
      $html .= '<input style="font-size:8pt;" type="file" name="'.$form_element['name'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= '/>';
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '         &nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name']);
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
   function _getCheckboxAsHTML ($form_element) {
      $html  = '';
      $html .= '<input type="checkbox" name="'.$form_element['name'].'" value="'.$this->_text_as_form($form_element['value']).'"';
      if ($form_element['ischecked']) {
         $html .= ' checked';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= ' style="font-size:8pt;margin-left:0px;"/>';
      $html .= '<span class="personal">'.$form_element['text'].'</span>';
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
      $html  = 'muss noch angepasst werden an den left-form-view';
      /*
      $options = $form_element['value'];
      $option = reset($options);
      if (!empty($form_element['columns'])) {
         $html .= '<table class="form_view_plain_checkboxgroup" summary="Layout">'."\n";
         $num_of_options = count($options);
         $width = floor(100/$form_element['columns']);
         $num_of_column = 1;
         if ($form_element['horizontal']) {
            while ($option) {
               if ($num_of_column == 1) {
                  $html .= '<tr>'."\n";
               }
               $option['name'] = $form_element['name'].'[]';
               if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                  $option['ischecked'] = true;
               } else {
                  $option['ischecked'] = false;
               }
               $html .= '<td width="'.$width.'%">'.$this->_getCheckboxAsHTML($option).'</td>'."\n";
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
                  $html .= '<tr>'."\n";
               }
               for ($j=0; $j<$form_element['columns'];$j++) {
                  $id = $i + ($j*$maximum);
                  if ($id<count($options)) {
                     $option = $options[$id];
                     $option['name'] = $form_element['name'].'[]';
                     if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
                        $option['ischecked'] = true;
                     } else {
                        $option['ischecked'] = false;
                     }
                     $html .= '<td width="'.$width.'%">'.$this->_getCheckboxAsHTML($option).'</td>'."\n";
                  }
               }
               if ($num_of_column == $form_element['columns'] or $i+1 == $maximum) {
                  $html .= '</tr>'."\n";
                  $num_of_column = 0;
               }
            }
         }
         $html .= '</table>'."\n";
      } else {
         while ($option) {
            $option['name'] = $form_element['name'].'[]';
            if (in_array($option['value'],$form_element['selected']) or in_array($option['text'],$form_element['selected'])) {
               $option['ischecked'] = true;
            } else {
               $option['ischecked'] = false;
            }
            $html .= '         '.$this->_getCheckboxAsHTML($option);
            if (!$form_element['horizontal'] and !empty($option)) {
               $html .= '<br />';
            }
            $html .= "\n";
            $option = next($options);
         }
      }
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '         &nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
      */
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
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"/>'.LF;
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
      $style ='font-size:8pt';
      if (isset($form_element['width']) and !empty($form_element['width'])){
         $style ='width:'.$form_element['width'].'em; font-size:8pt;';
      }
      $html .= '<select style="'.$style.'" name="'.$form_element['name'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      if ($form_element['multiple']) {
         $html .= ' multiple';
      }
      $html .= ' tabindex="'.$this->_count_form_elements.'"';

      // jQuery
      //$html .= $form_element['event'] ? " onChange='javascript:document.f.submit()'" : '';
      $html .= $form_element['event'] ? " id='submit_form'" : '';
      // jQuery
      $html .= '>'."\n";
      $options = $form_element['options'];
      $option = reset($options);
      while ($option) {
         if (!isset($option['value'])) {
            $option['value'] = $option['text'];
         }
         $html .= '            <option ';
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
      $html .= '         </select>'."\n";
      if ($form_element['name']=='label'){
           $element['type']      = 'textfield';
           $element['name']      = 'new_label';
           $element['value']     = '';
           $element['label']     = '';
           $element['example']   = '';
           $element['maxlength'] = 255;
           $element['size']      = 20;
           $element['mandatory'] = 'false';
           $html .= $this->_getTextFieldAsHTML($element);
      }
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
      }
      return $html;
   }

   /** get selectgroupbox as HTML - internal, do not use
    * this method returns a string contains selectboxes in HMTL-Code
    *
    * @param array value form element: selectboxgroup, see class cs_form
    *
    * @return string selectbox as HMTL
    */
   function _getSelectGroupAsHTML ($form_element) {
      $html  = '';
      for($i=0; $i<count($form_element['options']);$i++){
          $element['type']          = 'select';
          $element['name']          = $form_element['names4select'][$i];
          $element['options']       = $form_element['options'][$i];
          if(isset($form_element['selected'][$i])){
             $element['selected']      = (array)$form_element['selected'][$i];
          }else{
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
    */
   function _getPasswordAsHTML ($form_element) {
      $html  = '';
      $html .= '<input style="font-size:8pt;';
      if (isset($form_element['width']) and !empty($form_element['width'])) {
         $html .= ' width:'.$form_element['width'].'em;';
      }
      $html .= '" type="password" name="'.$form_element['name'].'"';
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"';
      if (isset($form_element['maxlength']) && !empty($form_element['maxlength'])) {
         $html .= ' maxlength="'.$form_element['maxlength'].'"';
      }
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= ' class="password"';
      $html .= '/>';
      // Passwort Securitycheck
      if($form_element['name'] == 'password'){
      	$auth_source_manager = $this->_environment->getAuthSourceManager();
      	$auth_source = $auth_source_manager->_performQuery();
	      $auth_source_item = $auth_source_manager->getItem($auth_source[0]['item_id']);
      	#$auth_source_manager = $this->_environment->getAuthSourceManager();
	      #$auth_source_item = $auth_source_manager->getItem($this->_environment->getCurrentUserItem()->getAuthSource());
	      if(!empty($auth_source_item) AND $auth_source_item->isPasswordSecureActivated()){
	      	$html .= '<div id="iSM"><ul class="weak"><li id="iWeak">zu leicht</li>
				<li id="iMedium">erlaubt</li><li id="iStrong">sicher</li></ul></div>';
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
      $html .= '<input style="font-size:8pt;';
      if (isset($form_element['width']) and !empty($form_element['width'])) {
         $html .= ' width:'.$form_element['width'].'em;';
      }
      $html .= '" type="text" name="'.$form_element['name'].'"';
      $html .= ' value="'.$this->_text_as_form($form_element['value']).'"';
      $html .= ' maxlength="'.$form_element['maxlength'].'"';
      $html .= ' size="'.$form_element['size'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= ' class="text"';
      $html .= '/>';
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '&nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name']).LF;
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
      $html .= '<textarea style="font-size:8pt;" name="'.$form_element['name'].'"';
      $html .= ' cols="'.$form_element['vsize'].'"';
      $html .= ' rows="'.$form_element['hsize'].'"';
      $html .= ' wrap="'.$form_element['wrap'].'"';
      $html .= ' tabindex="'.$this->_count_form_elements.'"';
      $html .= '">';
      $html .= $this->_text_as_form($form_element['value']);
      $html .= '</textarea>';
      $html .= "\n";
      return $html;
   }

   function _getTextAsHTML ($form_element) {
      $html  = '';
      if (!empty($form_element['anchor'])){
        $html='<a name="'.$form_element['anchor'].'"></a>';
     }
      if (!empty($form_element['value'])) {
       $html .= '<span class="personal">'.LF;
         if ($form_element['isbold']) {
            $html .= '<b>'.$form_element['value'].'<b>'.LF;
         } else {
            $html .= $form_element['value'].LF;
         }
       $html.= '</span>'.LF;
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
      if (isset($form_element_array[0]['text-align']) and $form_element_array[0]['text-align'] == 'right') {
         $html .= '   <div class="form_view_plain_formelement_right">'.LF;
      } else {
         $html .= '   <div class="form_view_plain_formelement" style="padding-bottom: 6px;">'.LF;
      }
      if (!empty($form_element_array[0]['label'])) {
         if (isset($form_element_array[0]['failure'])) {
            $label = '<span class="personal_bold">'.$form_element_array[0]['label'].'</span>';
         } else {
          $label = '<span class="personal">'.$form_element_array[0]['label'].'</span>';
       }
         $html .= $label;
         if (!empty($form_element_array[0]['mandatory'])) {
            $html.= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>'.LF;
         }
       $html .= BRLF;
      }

      // form element
      if (isset($form_element_array[0]['combine']) and $form_element_array[0]['combine'] == 'horizontal') {
         $horizontal = true;
         $html .= '<table class="form_view_plain_combine" summary="Layout">'.LF;
         $html .= '   <tr>'.LF;
      } else {
         $horizontal = false;
      }
      $first = true;
      foreach ($form_element_array as $form_element) {
         if ($first) {
            $first = false;
         } else {
            $html .= LF.'<!-- COMBINED FIELDS -->'.BRLF;
         }

         if ($horizontal) {
            if ($form_element['type']=='radio' and (isset($form_element['combine_direct']))){
               $html .= '      <td class="form_view_plain_combine">'.LF;
            }
            elseif (isset($form_element['text-align']) and $form_element['text-align'] == 'right') {
               $html .= '      <td class="right" >'.LF;
            } else {
               $html .= '      <td>'.LF;
            }
         }

         if (!empty($form_element['before_form_text'])) {
            $html .= '         <span class="personal">'.$form_element['before_form_text'].'</span>'.LF;
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
            $html .= '      </td>'.LF;
         }
      }
      if ($horizontal) {
         $html .= '   </tr>'.LF;
         $html .= '</table>'.LF;
      }

      // if buttonbar with delete button, delete button will be set into the descripiton field
      // see the _getButtonBarAsHTML() methode
      $html .= '</div>'.LF;
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
         $html .= '<div style="padding-right: 5px;"'.$this->_getErrorBoxAsHTML().'</div>'.LF;
      }
      $html .= '<!-- BEGIN OF FORM-VIEW PLAIN -->'.LF;
      $html .= '<form action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data"';
      if ( isset($this->_form_name) and !empty($this->_form_name) ) {
         $html .= ' name="'.$this->_form_name.'"';
      } else {
         $html .= ' name="f"';
      }
      $html .= '>'.LF;
      $html .= '<table class="form_view_plain" summary="Layout">'.LF;
      $html .= '   <tr>'."\n";
      $html .= '      <td class="form_view_plain">'.LF;

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
         $html .= '   <script type="text/javascript">'."\n";
         $html .= '      <!--'."\n";
         // jQuery
         //$html .= '         function setfocus() { document.f.'.$this->_getFirstInputFieldName().'.focus(); }'."\n";
         $html .= '         function setfocus() { jQuery("input[name=\''.$this->_getFirstInputFieldName().'\'], f").focus(); }'."\n";
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
      $params['width'] = '100%';
      $errorbox = $this->_class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $first = true;
      $error_string = '';
      foreach ($this->_error_array as $error) {
         $error_string .= $error.BRLF;
      }
      $errorbox->setText($error_string);
      return $errorbox->asHTML();
   }
}
?>