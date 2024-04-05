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

/** class for links
 * this class implements a link object.
 */
class cs_tag2tag_item
{
    /**
     * string - containing the type of the list resp. the type of the elements.
     */
    public $_type = null;

    /**
     * array - containing the elements of the list.
     */
    public $_data = [];

    /** constructor
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        $this->_environment = $environment;
        $this->_type = CS_TAG2TAG_TYPE;
    }

    /** is the type of the list = $type ?
     * this method returns a boolean expressing if type of the list is $type or not.
     *
     * @param string type string to compare with type of list (_type)
     *
     * @return bool true - type of this list is $type
     *              false - type of this list is not $type
     */
    public function isA($type)
    {
        return $this->_type == $type;
    }

    /** return the type of the object = link
     * this method returns the type of the object = link.
     *
     * @return string type of the object link
     *
     * @author CommSy Development Group
     */
    public function getType()
    {
        return $this->_type;
    }

    /** set the link id of the item
     * this method sets the link id of the item.
     *
     * @param int link_id of item
     */
    public function setLinkID($value)
    {
        $this->_data['link_id'] = (int) $value;
    }

    /** return the link id of the item
     * this method returns the link id of the item.
     *
     * @return int link_id of the item
     */
    public function getLinkID()
    {
        return $this->_getValue('link_id');
    }

    /** set the item id of the father item
     * this method sets the item id of the father item.
     *
     * @param int item_id of father item
     */
    public function setFatherItemID($value)
    {
        $this->_data['father_id'] = (int) $value;
    }

    /** return the item id of the father item
     * this method returns the item id of the father item.
     *
     * @return int item_id of the father item
     */
    public function getFatherItemID()
    {
        return $this->_getValue('father_id');
    }

    /** set the item id of the child item
     * this method sets the item id of the child item.
     *
     * @param int item_id of child item
     */
    public function setChildItemID($value)
    {
        $this->_data['child_id'] = (int) $value;
    }

    /** return the item id of the child item
     * this method returns the item id of the child item.
     *
     * @return int item_id of the child item
     */
    public function getChildItemID()
    {
        return $this->_getValue('child_id');
    }

    /** set the item_id of the creator
     * this method sets the item_id of the creator.
     *
     * @param int item_id of the creator
     */
    public function setCreatorItemID($value)
    {
        $this->_data['creator_id'] = (int) $value;
    }

    /** return the item_id of the creator
     * this method returns the item_id of the creator.
     *
     * @return int item_id of the creator
     */
    public function getCreatorItemID()
    {
        return $this->_getValue('creator_id');
    }

    /** set the item_id of the modifier
     * this method sets the item_id of the modifier.
     *
     * @param int item_id of the modifier
     */
    public function setModifierItemID($value)
    {
        $this->_data['modifier_id'] = (int) $value;
    }

    /** return the item_id of the modifier
     * this method returns the item_id of the modifier.
     *
     * @return int item_id of the modifier
     */
    public function getModifierItemID()
    {
        return $this->_getValue('modifier_id');
    }

    /** set the item_id of the deleter
     * this method sets the item_id of the deleter.
     *
     * @param int item_id of the deleter
     */
    public function setDeleterItemID($value)
    {
        $this->_data['deleter_id'] = (int) $value;
    }

    /** return the item_id of the deleter
     * this method returns the item_id of the deleter.
     *
     * @return int item_id of the deleter
     */
    public function getDeleterItemID()
    {
        return $this->_getValue('deleter_id');
    }

    public function setCreationDate($value)
    {
        $this->_data['creation_date'] = $value;
    }

    public function setModificationDate($value)
    {
        $this->_data['modification_date'] = $value;
    }

    public function setDeletionDate($value)
    {
        $this->_data['deletion_date'] = $value;
    }

    /** sets the sorting place.
     *
     * @param int sorting place
     */
    public function setSortingPlace($value)
    {
        $this->_data['sorting_place'] = (int) $value;
    }

    /** gets the sorting place.
     *
     * @param int sorting place
     */
    public function getSortingPlace()
    {
        return $this->_getValue('sorting_place');
    }

    /** set the context id of the link
     * this method sets the context id of the link.
     *
     * @param string context id of the link
     */
    public function setContextItemID($value)
    {
        $this->_data['context_id'] = (int) $value;
    }

    /** return the context id of the link, INTERNAL
     * this method returns the context id of the link.
     *
     * @return string context id of the link
     */
    public function getContextItemID()
    {
        return $this->_getValue('context_id');
    }

    /** return a value of the link, INTERNAL
     * this method returns a value of the link.
     *
     * @return string a value the link
     */
    public function _getValue($key)
    {
        if (!empty($this->_data[$key])) {
            $value = $this->_data[$key];
        } else {
            $value = '';
        }

        return $value;
    }

    public function save(): void
    {
        $manager = $this->_environment->getManager($this->_type);
        $manager->saveItem($this);
    }
}
