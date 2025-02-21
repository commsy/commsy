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

/**
 * Class for database connection to the database table "labels".
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_topic_manager extends cs_labels_manager
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

    public function resetLimits(): void
    {
        parent::resetLimits();
        $this->_type_limit = CS_TOPIC_TYPE;
    }

    public function getNewItem($label_type = ''): cs_topic_item
    {
        $topic = new cs_topic_item($this->_environment);
        $topic->setCreatorItem($this->_environment->getCurrentUser());

        return $topic;
    }
}
