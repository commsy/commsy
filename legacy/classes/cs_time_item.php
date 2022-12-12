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

/** upper class of the label item.
 */
include_once 'classes/cs_label_item.php';
include_once 'functions/text_functions.php';

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 *
 * @author CommSy Development Group
 */
class cs_time_item extends cs_label_item
{
    /**
     * string - containing the context of the time label (school or uni).
     */
    public $_context;

    /** constructor: cs_label_item
     * the only available constructor, initial values for internal variables.
     *
     * @param string label_type type of the label
     *
     * @author CommSy Development Group
     */
    public function __construct($environment)
    {
        parent::__construct($environment, 'time');
    }

    /** sets the data of the item.
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array)
    {
        // not yet implemented
        $this->_data = $data_array;
        if (isset($data_array['name'])) {
            $this->_data['sorting'] = $data_array['name'];
        }

        return $this->isValid();
    }

    /** get sorting field content
     * this method returns the data in the sorting field.
     *
     * @return string content of the sorting field
     *
     * @author CommSy Development Group
     */
    public function getSortingFieldContent()
    {
        return $this->_getValue('sorting');
    }
}
