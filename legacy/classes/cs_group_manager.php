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

class cs_group_manager extends cs_labels_manager
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment $environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

    /** resetLimits
     *  reset limits of this manager.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_type_limit = CS_GROUP_TYPE;
    }

    /** get an empty group item
     *  get an empty label (group) item.
     *
     *  @return cs_label_item a group label
     */
    public function getNewItem($label_type = '')
    {
        return new cs_group_item($this->_environment);
    }
}
