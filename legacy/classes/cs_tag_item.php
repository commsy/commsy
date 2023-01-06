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

/** class for a tag
 * this class implements a commsy tag.
 */
class cs_tag_item extends cs_item
{
    private $_position_array = [];
    private $_position_old_array = [];
    /**
     * @var mixed|\cs_list
     */
    private $_children_list = null;
    private $_save_position_without_change = false;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment environment of CommSy
     */
    public function __construct($environment)
    {
        cs_item::__construct($environment);
        $this->_type = CS_TAG_TYPE;
    }

    /** sets the data of the item.
     *
     */
    public function _setItemData($data_array)
    {
        $this->_data = $data_array;

        // set father id
        if ('CS_TAG_ROOT' != $this->getTitle()) {
            $manager = $this->_environment->getTag2TagManager();
            $this->setPosition($manager->getFatherItemID($this->getItemID()));
            $this->_setOldPosition($manager->getFatherItemID($this->getItemID()));
            unset($manager);
        }

        return $this->isValid();
    }

    /** Checks and returns the data of the item.
     */
    public function _getItemData()
    {
        $item_array = [];
        if ($this->isValid()) {
            $item_array['title'] = $this->getTitle();

            return $item_array;
        } else {
            trigger_error('cs_tag_item: getItemData(): Invalid Data');
        }
    }

    // ###############################
    // for save item
    // and set it to the right place
    // in the tag tree / net
    // ###############################

    private function _getPosition()
    {
        return $this->_position_array[0];
    }

    public function getPosition()
    {
        return $this->_position_array[0];
    }

    public function setPosition($father, $place = '')
    {
        $this->_position_array[0]['father'] = $father; // only tree
        $this->_position_array[0]['place'] = $place;   // not net
    }

    private function _getOldPosition()
    {
        return $this->_position_array[0];
    }

    private function _setOldPosition($father, $place = '')
    {
        $this->_position_old_array[0]['father'] = $father; // only tree
        $this->_position_old_array[0]['place'] = $place;   // not net
    }

    private function _getPositionArray()
    {
        return $this->_position_array;
    }

    public function getPositionArray()
    {
        return $this->_position_array;
    }

    private function _setPositionArray($value)
    {
        $this->_position_array = $value;
    }

    private function _getOldPositionArray()
    {
        return $this->_position_old_array;
    }

    private function _setOldPositionArray($value)
    {
        $this->_position_old_array = $value;
    }

    private function _newLocation()
    {
        $retour = false;
        $position_array = $this->_getPositionArray();
        $position_old_array = $this->_getOldPositionArray();
        // only tree / not net
        if (isset($position_old_array[0])) {
            $diff = array_diff($position_array[0], $position_old_array[0]);
        } elseif (isset($position_array[0])) {
            $diff = $position_array[0];
        }
        if (!empty($diff)) {
            $retour = true;
        }

        return $retour;
    }

    /** checks the data of the item.
     */
    public function isValid()
    {
        $title = $this->getTitle();

        return !empty($title);
    }

    /** get title
     * this method returns the title of the tag.
     *
     * @return string title of the tag
     */
    public function getTitle()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return (string) $this->_getValue('title');
        }
    }

    /** set title
     * this method sets the title of the tag.
     *
     * @param string value title of the tag
     */
    public function setTitle($value)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    public function getChildrenList()
    {
        $retour = null;
        if (!isset($this->_children_list)) {
            $tag2tag_manager = $this->_environment->getTag2TagManager();
            $child_id_array = $tag2tag_manager->getChildrenItemIDArray($this->getItemID());
            if (isset($child_id_array) and !empty($child_id_array)) {
                $tag_manager = $this->_environment->getTagManager();
                $tag_manager->setIDArrayLimit($child_id_array);
                $tag_manager->select();
                $this->_children_list = $tag_manager->get();
            } else {
                $this->_children_list = new cs_list();
            }
        }
        $retour = $this->_children_list;

        return $retour;
    }

    public function save()
    {
        parent::save();

        // save new location in tag tree / net
        if ($this->_newLocation()) {
            $position_array = $this->_getPositionArray();
            $position_old_array = $this->_getOldPositionArray();
            $new = false;
            // only tree / not net
            if (isset($position_old_array[0])) {
                $diff = array_diff($position_array[0], $position_old_array[0]);
            } else {
                $diff = $position_array[0];
                $new = true;
            }
            $father = '';
            if (!empty($diff['father'])) {
                $father = $position_array[0]['father'];
            }
            $place = '';
            if (!empty($diff['place'])) {
                $place = $position_array[0]['place'];
            }
            $tag2tag_manager = $this->_environment->getTag2TagManager();
            if (!empty($father)) {
                // delete old position
                if (!$new) {
                    $tag2tag_manager->delete($position_old_array[0]['father'], $this->getItemID());
                }
                // insert new position
                $tag2tag_manager->insert($this->getItemID(), $father, $position_array[0]['place']);
            } elseif (!empty($place)) {
                // change position
                if (!$this->_save_position_without_change) {
                    $tag2tag_manager->change($this->getItemID(), $position_old_array[0]['father'], $place);
                } else {
                    $tag2tag_manager->changeUpdate($this->getItemID(), $place);
                }
            }
            unset($tag2tag_manager);
        }
    }

    public function saveMaterialLinkItemsByIDArray($array)
    {
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->saveLinkItemsMaterialToItem($array, $this);
    }

    public function saveRubricLinkItemsByIDArray($array, $rubric)
    {
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->saveLinkItemsRubricToItem($array, $this, $rubric);
    }

    public function setSavePositionWithoutChange($value)
    {
        $this->_save_position_without_change = $value;
    }
}
