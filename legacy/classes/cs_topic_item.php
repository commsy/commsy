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

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 *
 * @author CommSy Development Group
 */
class cs_topic_item extends cs_label_item
{
    /** constructor:
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment environment of CommSy
     */
    public function __construct($environment)
    {
        parent::__construct($environment, 'topic');
    }

    public function activatePath()
    {
        $this->_addExtra('PATH', 1);
    }

    public function deactivatePath()
    {
        $this->_addExtra('PATH', -1);
    }

    public function _getPathActive()
    {
        return $this->_getExtra('PATH');
    }

    public function isPathActive()
    {
        $retour = false;
        $path = $this->_getExtra('PATH');
        if (1 == $path) {
            $retour = true;
        }

        return $retour;
    }

    public function getPathItemList()
    {
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->setLinkedItemLimit($this);
        $link_manager->setSortingPlaceLimit();
        $link_manager->sortbySortingPlace();
        $link_manager->select();
        $link_item_list = $link_manager->get();

        $retour = new cs_list();

        if (!$link_item_list->isEmpty()) {
            $item = $link_item_list->getFirst();
            while ($item) {
                $retour->add($item->getLinkedItem($this));
                $item = $link_item_list->getNext();
            }
        }

        return $retour;
    }

    public function save(): void
    {
        $topic_manager = $this->_environment->getTopicManager();
        $this->_save($topic_manager);
        $this->_saveFiles();     // this must be done before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id
    }
}
