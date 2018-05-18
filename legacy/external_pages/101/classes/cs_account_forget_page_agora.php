<?PHP
include_once('classes/cs_account_forget_page.php');

/** Overridden for the custom AGORA portal theme (to provide a custom form view in `_show_form()`).
 * @author CommSy Development Group
 */
class cs_account_forget_page_agora extends cs_account_forget_page
{
    /** The only available constructor.
     * @param cs_environment environment the CommSy legacy environment
     */
    function __construct($environment)
    {
        parent::__construct($environment);
    }

    /** Overridden for the custom AGORA portal theme to provide a "forgotten account" form view as HTML.
     * @return string form view as HTML appropriate for the custom AGORA portal theme
     * @author CommSy Development Group
     */
    function _show_form($form, $formName = '')
    {
        $params = array();
        $params['environment'] = $this->_environment;
        $params['with_modifying_actions'] = true;
    
        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes/views';

        // include the custom form view
        include_once($externalIncludePath . '/cs_form_view_left_agora.php');
        $formView = new cs_form_view_left_agora($params);
        unset($params);

        if (!empty($formName)) {
            $formView->setFormName($formName);
        }
        include_once('functions/curl_functions.php');
        $params = $this->_environment->getCurrentParameterArray();
        $formView->setAction(curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $this->_environment->getCurrentParameterArray()));
        $formView->setForm($form);

        return $formView->asHTML();
    }
}

?>