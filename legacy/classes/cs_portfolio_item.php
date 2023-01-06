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

/** class for a portfolio
 * this class implements a portfolio item.
 */
class cs_portfolio_item extends cs_item
{
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_PORTFOLIO_TYPE;
    }

    public function _setItemData($data_array)
    {
        // not yet implemented
        $this->_data = $data_array;
    }

    public function getTitle()
    {
        return $this->_getValue('title');
    }

    public function setTitle($value)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    public function getDescription()
    {
        return $this->_getValue('description');
    }

    public function setDescription($value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    public function save()
    {
        $portfolio_manager = $this->_environment->getPortfolioManager();
        $this->_save($portfolio_manager);
    }

    public function delete()
    {
        $manager = $this->_environment->getPortfolioManager();
        $this->_delete($manager);
    }

    public function getExternalViewer()
    {
        return $this->_getValue('externalViewer');
    }

    public function setExternalViewer($userIdArray)
    {
        $this->_setValue('externalViewer', $userIdArray);
    }

    public function setTemplate()
    {
        $this->_setValue('template', '1');
    }

    public function unsetTemplate()
    {
        $this->_unsetValue('template');
    }

    public function getTemplate()
    {
        return $this->_getValue('template');
    }

    public function isTemplate()
    {
        $flag = false;
        if (1 == $this->_getValue('template')) {
            $flag = true;
        }

        return $flag;
    }

    public function setExternalTemplate($userIdArray)
    {
        $this->_setValue('externalTemplate', $userIdArray);
    }

    public function getExternalTemplate()
    {
        return $this->_getValue('externalTemplate');
    }

    public function updateElastic()
    {
    }
}
