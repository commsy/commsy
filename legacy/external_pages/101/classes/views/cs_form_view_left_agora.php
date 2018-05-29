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
   function _getHeadLineAsHTML ($form_element)
   {
        // NOTE: ATM, this method doesn't support $form_element['right'] (class="form_actions"); see `cs_form_view_left.php->_getHeadLineAsHTML()`

        $headlineLabel = $form_element['label'];
        $headlineDescription = $this->_text_as_html_short($form_element['description']);

        $html = LF . <<<HTML
            <fieldset class="form-group">
              <div class="form-row">
                <legend class="col-form-label font-weight-bold">$headlineLabel</legend> 
HTML;

        if (!empty($headlineDescription)) {
            $html .= LF . <<<HTML
                <small id="headlineHelpBlock" class="form-text text-muted">$headlineDescription</small> 
HTML;
        }

        $html .= LF . <<<HTML
              </div>
            </fieldset>
HTML;

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
    * this method returns a string containing a button in HMTL-Code
    *
    * @param array value form element: button, see class cs_form
    *
    * @return string button as HMTL
    */
   function _getButtonAsHTML ($buttonText, $buttonName, $buttonWidth = '', $isPrimaryButton = false, $disableFormValidation = false)
   {
        // NOTE: ATM, this subclass ignores any given button width
        $width = ''; // (!empty($buttonWidth)) ? ' style="width:' . $buttonWidth . 'em;"' : '';

        $buttonLabel = $this->_text_as_html_short($buttonText);
        $additionalCSSClass = ($isPrimaryButton) ? ' btn-primary' : '';
        $novalidate = ($disableFormValidation) ? ' formnovalidate' : '';
        $html = <<<HTML
                <button type="submit" class="btn$additionalCSSClass" name="$buttonName" value="$buttonLabel"$novalidate$width>$buttonLabel</button>
HTML;

        return $html;
   }


   /** get buttonbar as HTML - internal, do not use
    * this method returns a string contains a buttonbar (save, cancel and delete) in HMTL-Code
    *
    * @param array value form element: buttonbar, see class cs_form
    *
    * @return string buttonbar as HMTL
    */

   function _getButtonBarAsHTML ($form_element)
   {
        $buttonName = $form_element['name'];

        $html = '';

        if (!empty($form_element['labelSave'])) {
            $html .= LF . $this->_getButtonAsHTML($form_element['labelSave'], $buttonName, $form_element['firstWidth'], true, false);
        }
        if (!empty($form_element['labelSecondSave'])) {
            $html .= LF . $this->_getButtonAsHTML($form_element['labelSecondSave'], $buttonName, '', false, false);
        }
        if (!empty($form_element['labelCancel'])) {
            $html .= LF . $this->_getButtonAsHTML($form_element['labelCancel'], $buttonName, $form_element['secondWidth'], false, true);
        }
        if (!empty($form_element['labelDelete'])) {
            $html .= LF . $this->_getButtonAsHTML($form_element['labelDelete'], $buttonName, '', false, false);
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

   function _getHiddenFieldAsHTML ($form_element)
   {
        $elementName = $form_element['name'];
        $elementValue = (isset($form_element['value'])) ? $this->_text_as_form($form_element['value']) : '';

        $html = LF . <<<HTML
            <input type="hidden" name="$elementName" value="$elementValue" />
HTML;

        return $html;
   }

   /** get selectbox as HTML - internal, do not use
    * this method returns a string containing an selectbox in HMTL-Code
    *
    * @param array value form element: selectbox, see class cs_form
    *
    * @return string selectbox as HMTL
    */
   function _getSelectAsHTML ($form_element)
   {
        $name = $form_element['name'];
        $id = $name . $form_element['id'];

        $multipleAttribute = '';
        if ($form_element['multiple']) {
            $name .= '[]';
            $multipleAttribute = ' multiple';
        }

        // NOTE: ATM, this subclass ignores any given select element width & size
        $sizeAttribute = ''; // ' size="' . $form_element['size'] . '"';
        $styleAttribute = '';
        /*
        if (isset($form_element['width']) && !empty($form_element['width'])) {
            $styleAttribute =' style="width:' . $form_element['width'] . 'em;"';
        }
        */

        $html = <<<HTML
                <select id="$id" name="$name" class="form-control"$sizeAttribute$styleAttribute$multipleAttribute>
HTML;

        $options = $form_element['options'];
        $option = reset($options);
        while ($option) {
            if (!isset($option['value'])) {
                $option['value'] = $option['text'];
            }

            $optionName = $this->_text_as_html_short($option['text']);
            $valueAttribute = '';
            $selectedAttribute = '';
            $disabledAttribute = '';
            if (isset($option['value'])) {
                if ($option['value'] === 'disabled') {
                    $disabledAttribute = ' disabled';
                } else {
                    $valueAttribute = ' value="' . $this->_text_as_form($option['value']) . '"';
                }

                if (in_array($option['value'], $form_element['selected'])) {
                    $selectedAttribute = ' selected';
                }
            }
            
            $html .= LF . <<<HTML
                  <option$valueAttribute$selectedAttribute$disabledAttribute>$optionName</option>
HTML;

            $option = next($options);
        }

        $html .= LF . <<<HTML
                </select>
HTML;

        if ($form_element['name'] === 'label') {
            $element['type']      = 'textfield';
            $element['name']      = 'new_label';
            $element['value']     = '';
            $element['label']     = '';
            $element['example']   = '';
            //$element['maxlength'] = 255;
            //$element['size']      = 20;
            $element['mandatory'] = 'false';
            $html .= LF . $this->_getTextFieldAsHTML($element);
        }
// TODO: handle `button_text` & `button_name`?
/*
        if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'])."\n";
        }
*/

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
   function _getTextFieldAsHTML ($form_element)
   {
        $formElementName = $form_element['name'];
        $formElementID = $formElementName . $form_element['id'];
        $formElementLabel = $form_element['label'];
        $formElementValue = $this->_text_as_form($form_element['value']);
        $formElementRequired = (!empty($form_element['mandatory'])) ? ' required' : '';
        $formElementDescription = (isset($form_element['description']) && !empty($form_element['description'])) ? $this->_text_as_html_short($form_element['description']) : '';

        $html = <<<HTML
                <input type="text" class="form-control" id="$formElementID" name="$formElementName" placeholder="$formElementLabel" value="$formElementValue"$formElementRequired />
HTML;

        if (!empty($formElementDescription)) {
            $html = <<<HTML
                <small id="{$formElementID}HelpBlock" class="form-text text-muted"><$formElementDescription</small> 
HTML;
        }

// TODO: handle `button_text` & `button_name`?
/*
      if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
         $html .= '&nbsp;';
         $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name']).LF;
      }
*/

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

   function _getTextAsHTML ($form_element)
   {
        $html = '';

        if (isset($form_element['anchor']) && !empty($form_element['anchor'])) {
            $anchor = $form_element['anchor'];
            $html .= <<<HTML
                <a name="$anchor"></a>
HTML;
     }

        if (isset($form_element['value']) && !empty($form_element['value'])) {
            $text = $form_element['value'];
            $elementID = $form_element['name'] . $form_element['id'];
            $additionalCSSClass = ($form_element['isbold']) ? ' font-weight-bold' : '';

            $html .= <<<HTML
                <span id="$elementID" class="personal$additionalCSSClass">$text</span>
HTML;

// TODO: handle `button_text` & `button_name`?
/*
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '         &nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'',false)."\n";
         }
*/
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
   function _getFormElementAsHTML ($form_element)
   {
        // prepare form element array for combined form elements
        $form_element_array = array();
        if (!isset($form_element[0]['type'])) {
            $form_element_array[] = $form_element;
        } else {
            $form_element_array = $form_element;
        }

        $firstFormElement = $form_element_array[0];
        $firstFormLabel = $firstFormElement['label'];
        $firstFormID = $firstFormElement['name'] . $firstFormElement['id'];

        $html = LF . <<<HTML
            <div class="form-group row">
HTML;

        // TODO: handle first element's alignment?
        /*
        $additionalContainerCSSClass = (isset($firstFormElement['text-align']) && $firstFormElement['text-align'] == 'right') ? ' justify-content-end' : '';
        $html = LF . <<<HTML
            <div class="container container-form-content$additionalContainerCSSClass">
HTML;
*/

        // element label
        $mainGridWidth = 12;
        if (!empty($firstFormLabel)) {
            $mainGridWidth = $mainGridWidth - 2;
            $additionalLabelCSSClass = (isset($firstFormElement['failure'])) ? ' font-weight-bold' : '';

            $requiredElementIndicator = $this->_translator->getMessage('MARK');
            $requiredElementHTML = (!empty($firstFormElement['mandatory'])) ? '<span class="required">' . $requiredElementIndicator . '</span>' : '';

            $html .= LF . <<<HTML
              <label for="$firstFormID" class="col-sm-2 col-form-label$additionalLabelCSSClass">$firstFormLabel$requiredElementHTML</label> 
HTML;
        }

        // form element
        $html .= LF . <<<HTML
              <div class="col-sm-$mainGridWidth">
HTML;

        foreach ($form_element_array as $form_element) {
            // TODO: handle `$form_element['text-align'] == 'right'` and `$form_element['combine_direct']`?

            if (isset($form_element['before_form_text']) && !empty($form_element['before_form_text'])) {
                $textPrefix = $form_element['before_form_text'];
                $html .= LF . <<<HTML
                <span class="personal">$textPrefix</span>
HTML;
            }

            switch ($form_element['type']) {
                case "textarea":
                    $html .= LF . $this->_getTextAreaAsHTML($form_element);
                    break;
                case "textfield":
                    $html .= LF . $this->_getTextFieldAsHTML($form_element);
                    break;
                case "password":
                    $html .= LF . $this->_getPasswordAsHTML($form_element);
                    break;
                case "select":
                    $html .= LF . $this->_getSelectAsHTML($form_element);
                    break;
                case "selectgroup":
                    $html .= LF . $this->_getSelectGroupAsHTML($form_element);
                    break;
                case "checkbox":
                    $html .= LF . $this->_getCheckboxAsHTML($form_element)."\n";
                    break;
                case "checkboxgroup":
                    $html .= $this->_getCheckboxGroupAsHTML($form_element);
                    break;
                case "file":
                    $html .= LF . $this->_getFileFieldAsHTML($form_element);
                    break;
                case "radio":
                    $html .= $this->_getRadioGroupAsHTML($form_element);
                    break;
                case "datetime":
                    $html .= $this->_getDateTimeFieldAsHTML($form_element);
                    break;
                case "emptyline":
                    $html .= LF . $this->_getEmptyLineAsHTML($form_element);
                    break;
                case "buttonbar":
                    $html .= $this->_getButtonBarAsHTML($form_element);
                    break;
                case "button":
                    $html .= $this->_getButtonAsHTML($form_element['button_text'], $form_element['name']);
                    break;
                case "textline":
                    $html .= LF . $this->_getTextLineAsHTML($form_element);
                    break;
                case "text":
                    $html .= LF . $this->_getTextAsHTML($form_element);
                    break;
                case "color_table":
                    $html .= LF . $this->_getColorTableAsHTML();
            }
        }

        $html .= LF . <<<HTML
              </div>
            </div>
HTML;

        return $html;
   }

   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML ()
   {
        $html  = '';

        // TODO: error box styling
        if (count($this->_error_array) > 0) {
            $html .= <<<HTML
          <div class="container container-errorbox">
            <div {$this->_getErrorBoxAsHTML()}</div>
          </div>
HTML;
        }

        $methodType = $this->_action_type;
        $formActionURL = $this->_action;
        $formName = (isset($this->_form_name) && !empty($this->_form_name)) ? $this->_form_name : 'f';

        $html .= <<<HTML
          <!-- FORM VIEW PLAIN -->
          <form method="$methodType" action="$formActionURL" name="$formName" enctype="multipart/form-data">
HTML;

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

        $html .= LF . <<<HTML
          </form>
HTML;

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