<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for a context
 * this class implements a context item.
 */
class cs_guide_item extends cs_context_item
{
    /** constructor: cs_server_item
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

    public function setAvailableLanguageArray($value)
    {
        $this->_addExtra('LANGUAGE_AVAILABLE', $value);
    }

    public function getAvailableLanguageArray(): array
    {
        $retour = [];
        if ($this->_issetExtra('LANGUAGE_AVAILABLE')) {
            $retour = $this->_getExtra('LANGUAGE_AVAILABLE');
        } elseif ($this->isServer()) {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getAvailableLanguages();
        } elseif ($this->isPortal()) {
            $server_item = $this->_environment->getServerItem();
            $retour = $server_item->getAvailableLanguageArray();
        }

        return $retour;
    }

    /** get url of a portal/server
     * this method returns the url of the portal/server
     * - without http(s)://
     * - without /commsy.php?....
     *
     * @return string url of a portal/server
     */
    public function getUrl()
    {
        return $this->_getValue('url');
    }

    /** set url of a portal
     * this method sets the url of the portal/server.
     *
     * @param string value url of the portal/server
     */
    public function setUrl($value)
    {
        $this->_setValue('url', $value, true);
    }
}
