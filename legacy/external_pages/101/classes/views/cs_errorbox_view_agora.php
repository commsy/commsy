<?PHP
/** include upper class of the errorbox view
 */
global $symfonyContainer;
$environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
$classFactory = $environment->getClassFactory();
$classFactory->includeClass(ERRORBOX_VIEW);

/** Overridden to implement an errorbox view for the custom AGORA portal theme.
 * @author CommSy Development Group
 */
class cs_errorbox_view_agora extends cs_errorbox_view
{
    private $_with_login = false;
    var $_width = false;

    /** The only available constructor, initial values for internal variables.
     *
     * @param array params parameters in an array of this class
     */
    function __construct($params)
    {
        if (!empty($params['width'])) {
            $this->_width = $params['width'];
        }
        parent::__construct($params);
        $this->setTitle($this->_translator->getMessage('ERRORBOX_TITLE'));
    }

    function setWidth($value)
    {
        $this->_width = $value;
    }

    function setWithLogin ()
    {
        $this->_with_login = true;
    }

    /** Get the errorbox as HTML.
     * @return string errorbox view as HTML
     * @author CommSy Development Group
     */
    function asHTML ()
    {
        // NOTE: ATM, this subclass ignores the `_width` and `_with_login` properties; see `cs_errorbox_view.php->asHTML()`
        $width = '';
        
        $html = <<<HTML
          <!-- Error Box -->
          <div class="container container-errorbox p-3 mt-4 mb-4 bg-danger text-white"$width>
HTML;

        if (!empty($this->_title)) {
            $html .= LF . <<<HTML
            <div class="row">
              <div class="col-sm-12">
                <span class="font-weight-bold" name="title">{$this->_title}</span>
HTML;

            if (!empty($this->_description)) {
                $html .= LF . <<<HTML
                  <span name="description">({$this->_description})</span>
HTML;
            }

        $html .= LF . <<<HTML
              </div>
            </div>
HTML;
        }

        if (!empty($this->_text)) {
            $html .= LF . <<<HTML
            <div class="row">
              <div class="col-sm-12">
                <span class="personal" name="text">{$this->_text}</span>
              </div>
            </div>
HTML;
        }
                
        $html .= LF . <<<HTML
          </div>
HTML;

        return $html;
    }
}
?>