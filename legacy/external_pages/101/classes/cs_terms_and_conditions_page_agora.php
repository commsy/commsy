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

include_once('classes/cs_left_page.php');

/** Displays the "terms & conditions" form using the design of the custom AGORA portal theme.
 * @author CommSy Development Group
 */
class cs_terms_and_conditions_page_agora extends cs_left_page
{
    /** The only available constructor.
     * @param cs_environment environment the CommSy legacy environment
     */
    function __construct($environment)
    {
        parent::__construct($environment);
    }

    public function execute()
    {
        $form = $this->_class_factory->getClass(AGB_FORM, array('environment' => $this->_environment));

        // Load form data from postvars
        if (!empty($this->_post_vars)) {
            $form->setFormPost($this->_post_vars);
        }
        $form->prepareForm();
        $form->loadValues();

        // NOTE: the submitted form will be processed as usual (by `legacy/pages/agb_detail.php`)

        return $this->_show_form($form);
    }

    /** Overridden for the custom AGORA portal theme to provide a "terms & conditions" form view as HTML.
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