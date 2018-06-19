<?PHP
/** include upper class of the form view
 */
global $symfonyContainer;
$environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
$classFactory = $environment->getClassFactory();
$classFactory->includeClass(FORM_LEFT_VIEW);

/** Overridden to implement a form view for the custom AGORA portal theme.
 * @author CommSy Development Group
 */
class cs_form_view_left_agora extends cs_form_view_left
{

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
    var $_description_text = '';

    var $_form_name = '';


    /** The only available constructor, initial values for internal variables.
     *
     * @param array params parameters in an array of this class
     */
    function __construct($params)
    {
        parent::__construct($params);
    }


    /** get headline as HTML - internal, do not use
     * this method returns a string contains a headline in HMTL-Code
     *
     * @param array value form element: headline, see class cs_form
     *
     * @return string headline as HMTL
     */
    function _getHeadLineAsHTML($form_element, $isBold = true)
    {
        // NOTE: ATM, this method doesn't support $form_element['right'] (class="form_actions"); see `cs_form_view_left.php->_getHeadLineAsHTML()`

        $headlineLabel = $form_element['label'];
        $headlineDescription = $this->_text_as_html_short($form_element['description']);
        $additionalCSSClass = ($isBold) ? ' font-weight-bold' : '';

        $html = LF . <<<HTML
            <fieldset class="form-group">
              <div class="form-row">
                <legend class="col-form-label$additionalCSSClass">$headlineLabel</legend> 
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


    /** get button as HTML - internal, do not use
     * this method returns a string containing a button in HMTL-Code
     *
     * @param array value form element: button, see class cs_form
     *
     * @return string button as HMTL
     */
    function _getButtonAsHTML($buttonText, $buttonName, $buttonWidth = '', $isPrimaryButton = false, $disableFormValidation = false)
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
    function _getButtonBarAsHTML($form_element)
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


    /** get hiddenfield as HTML - internal, do not use
     * this method returns a string contains an hiddenfield in HMTL-Code
     *
     * @param array value form element: hiddenfield, see class cs_form
     *
     * @return string hiddenfield as HMTL
     */
    function _getHiddenFieldAsHTML($form_element)
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
    function _getSelectAsHTML($form_element)
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
            $element['type'] = 'textfield';
            $element['name'] = 'new_label';
            $element['value'] = '';
            $element['label'] = '';
            $element['example'] = '';
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


    /** get passwordfield as HTML - internal, do not use
     * this method returns a string contains an passwordfield in HMTL-Code
     *
     * @param array value form element: passwordfield, see class cs_form
     *
     * @return string passwordfield as HMTL
     */
    function _getPasswordAsHTML($form_element)
    {
        $formElementName = $form_element['name'];

        // NOTE: for the default password field, we use a fixed `id="password"` attribute to have it work with corresponding CSS and JavaScript (see "passwort strength" below)
        $formElementID = ($formElementName === 'password') ? $formElementName : $formElementName . $form_element['id'];
        $additionalCSSClass = ($formElementName === 'password') ? ' bg-transparent' : '';

        $formElementLabel = $form_element['label'];
        $formElementValue = $this->_text_as_form($form_element['value']);
        $formElementRequired = (!empty($form_element['mandatory'])) ? ' required' : '';
        $showPasswordLinkTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_PASSWORD_LINK_TITLE_SHOW');

        $html = <<<HTML
                <input type="password" class="form-control$additionalCSSClass" id="$formElementID" name="$formElementName" placeholder="$formElementLabel" value="$formElementValue"$formElementRequired />
HTML;

        // passwort strength
        // TODO: instead use CommSy's own "traffic light" system for a passwort security check?
        if ($formElementName === 'password') {
            $html .= LF . <<<HTML
                <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/1.0/zxcvbn-async.min.js"></script>
                <div class="password-background"></div>
                <small id="{$formElementID}HelpBlock" class="d-flex justify-content-between form-text text-muted">
                  <a class="show-password" style="display: none" href="">$showPasswordLinkTitle</a>
                  <span class="strength"></span>
                </small> 
HTML;
        }

        return $html;
    }


    /** get textfield as HTML - internal, do not use
     * this method returns a string contains an textfield in HMTL-Code
     *
     * @param array value form element: textfield, see class cs_form
     *
     * @return string textfield as HMTL
     */
    function _getTextFieldAsHTML($form_element)
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
            $html .= LF . <<<HTML
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


    function _getTextAsHTML($form_element)
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
    function _getFormElementAsHTML($form_element)
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
        $mdGridWidth = 12;
        if (!empty($firstFormLabel)) {
            $mainGridWidth = $mainGridWidth - 2;
            $mdGridWidth = $mainGridWidth - 1;
            $additionalLabelCSSClass = (isset($firstFormElement['failure'])) ? ' font-weight-bold' : '';

            $requiredElementIndicator = $this->_translator->getMessage('MARK');
            $requiredElementHTML = (!empty($firstFormElement['mandatory'])) ? '<span class="required">' . $requiredElementIndicator . '</span>' : '';

            $html .= LF . <<<HTML
              <label for="$firstFormID" class="col-sm-2 col-md-3 col-lg-2 col-form-label$additionalLabelCSSClass">$firstFormLabel$requiredElementHTML</label> 
HTML;
        }

        // form element
        $html .= LF . <<<HTML
              <div class="col-sm-$mainGridWidth col-md-$mdGridWidth col-lg-$mainGridWidth">
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
                    $html .= LF . $this->_getCheckboxAsHTML($form_element) . "\n";
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
    function asHTML()
    {
        $html = '';

        if (count($this->_error_array) > 0) {
            $html .= LF . $this->_getErrorBoxAsHTML() . LF;
        }

        $methodType = $this->_action_type;
        $formActionURL = $this->_action;
        $formName = (isset($this->_form_name) && !empty($this->_form_name)) ? $this->_form_name : 'f';

        $html .= LF . <<<HTML
          <!-- Form -->
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
        foreach ($form_element_array as $form_element) {
            if (!isset($form_element[0]['type']) && $form_element['type'] === 'headline') {
                $html .= $this->_getHeadLineAsHTML($form_element, true);
            } elseif (!isset($form_element[0]['type']) && $form_element['type'] === 'text' && !empty($form_element['label']) && empty($form_element['value'])) {
                // treat text with a label but no value as a headline (which spans the entire grid)
                $html .= $this->_getHeadLineAsHTML($form_element, $form_element['isbold']);
            } else {
                $html .= $this->_getFormElementAsHTML($form_element);
            }
        }

        $html .= LF . <<<HTML
          </form>
HTML;

        return $html;
    }


    /** internal method to create errorbox if there are errors, INTERNAL
     * this method creates an errorbox with messages form the error array
     */
    function _getErrorBoxAsHTML()
    {
        $params = array();
        $params['environment'] = $this->_environment;
        $params['with_modifying_actions'] = true;
        $params['width'] = '100%';

        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes/views';

        include_once($externalIncludePath . '/cs_errorbox_view_agora.php');
        $errorbox = new cs_errorbox_view_agora($params);
        unset($params);

        $errorString = implode(BRLF, $this->_error_array);
        $errorbox->setText($errorString);

        return $errorbox->asHTML();
    }
}

?>