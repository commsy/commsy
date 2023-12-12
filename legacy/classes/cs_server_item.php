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

/* upper class of the context item
 */

/** class for a context
 * this class implements a context item.
 */
class cs_server_item extends cs_guide_item
{
    /** constructor: cs_server_item
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment $environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_SERVER_TYPE;
    }

    public function isServer(): bool
    {
        return true;
    }

    /** get contact moderator of a room
     * this method returns a list of contact moderator which are linked to the room.
     *
     * @return cs_list a list of contact moderator (cs_label_item)
     */
    public function getContactModeratorList(): cs_list
    {
        $user_manager = $this->_environment->getUserManager();
        $mod_list = new cs_list();
        $mod_list->add($user_manager->getRootUser());

        return $mod_list;
    }
}
