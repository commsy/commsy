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

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_topic_manager extends cs_labels_manager
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        cs_labels_manager::__construct($environment);
    }

    /** resetLimits
     *  reset limits of this manager.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_type_limit = CS_TOPIC_TYPE;
        $this->_context_limit = $this->_environment->getCurrentContextID();
    }

    /** get an empty time item
     *  get an empty label_item.
     *
     *  @return cs_label_item a time label
     */
    public function getNewItem($label_type = '')
    {
        $item = new cs_topic_item($this->_environment);

        return $item;
    }
}
